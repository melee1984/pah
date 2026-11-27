<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\PaymentMethod;
use App\QuickPayCheckout;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickPayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'quickpay';
    }

    public function availableFor(int $partnerId): bool
    {
        return PaymentMethod::quickPayKey($partnerId) !== null;
    }

    public function mode(int $partnerId): bool
    {
        $key = PaymentMethod::quickPayKey($partnerId);
        abort_unless($key, 422, 'QuickPay is unavailable for this restaurant.');

        return str_starts_with($key, 'qp_live_');
    }

    public function refresh(QuickPayCheckout $checkout): void
    {
        $key = PaymentMethod::quickPayKey($checkout->partner_id);
        abort_unless($key, 503, 'QuickPay credentials for this payment are unavailable.');
        abort_unless($checkout->livemode === str_starts_with($key, 'qp_live_'), 503, $checkout->livemode
            ? 'This payment was created in live mode. Configure the merchant’s live QuickPay key to verify it.'
            : 'This payment was created in test mode. Configure the merchant’s test QuickPay key to verify it.');

        $client = Http::baseUrl('https://quickpayph.com')->withToken($key)->asJson()->acceptJson()
            ->connectTimeout(3)->timeout(8)->withoutRedirecting();

        $payload = $checkout->request_body;
        if (! $checkout->provider_id && in_array($payload['channel'] ?? null, ['qrph', 'banks'], true)) {
            // Guest return URLs contain QR ordering identifiers.
            $loggedPayload = $payload;
            foreach (['success_url', 'cancel_url'] as $field) {
                if (isset($loggedPayload[$field])) {
                    $loggedPayload[$field] = '[redacted]';
                }
            }

            Log::info('QuickPay checkout request payload.', [
                'checkout_id' => $checkout->id,
                'payload' => $loggedPayload,
            ]);
        }

        try {
            $response = $checkout->provider_id
                ? $client->get('/api/v1/checkouts/'.rawurlencode($checkout->provider_id))
                : $client->withHeader('Idempotency-Key', $checkout->request_key)->post('/api/v1/checkouts', $payload);
        } catch (ConnectionException $exception) {
            $this->logFailure($checkout, 'connection_error', ['message' => $exception->getMessage()]);
            abort(503, 'QuickPay could not be reached. Check this payment again before trying another method.');
        }

        $this->logResponse($checkout, $response->status(), $response->json());

        if (! $checkout->provider_id && $response->status() === 400) {
            $message = $this->rejectionMessage($response->json('error'), $response->json('message'));
            if ($message !== null) {
                $checkout->update(['status' => 'rejected', 'rejection_message' => $message, 'guest_snapshot' => null]);

                return;
            }
        }

        abort_unless($response->successful(), 503, 'QuickPay could not confirm this payment. Please check again.');
        $data = $response->json();
        abort_unless(is_array($data)
            && ($data['object'] ?? null) === 'checkout'
            && is_string($data['id'] ?? null)
            && strlen($data['id']) <= 150
            && $data['id'] !== ''
            && (! $checkout->provider_id || $checkout->provider_id === $data['id'])
            && ($data['reference'] ?? null) === $checkout->request_body['reference']
            && ($data['amount'] ?? null) === $checkout->amount_cents
            && ($data['currency'] ?? null) === 'PHP'
            && ($data['livemode'] ?? null) === $checkout->livemode
            && in_array($data['status'] ?? null, ['open', 'paid', 'expired'], true), 502, 'QuickPay returned details that could not be verified.');

        $url = $data['url'] ?? null;
        abort_if($data['status'] === 'open' && ! self::isHttps($url), 502, 'QuickPay returned an invalid checkout link.');

        $qrPayload = $data['qr']['emv'] ?? null;
        $qrPayload = is_string($qrPayload) && $qrPayload !== '' && strlen($qrPayload) <= 1024
            ? $qrPayload : null;

        $checkout->update([
            'provider_id' => $data['id'],
            'url' => self::isHttps($url) ? $url : $checkout->url,
            'qr_payload' => $qrPayload ?? $checkout->qr_payload,
            'qr_image' => $this->image($data['qr']['image'] ?? null) ?? $checkout->qr_image,
            'guest_snapshot' => $data['status'] === 'expired' ? null : $checkout->guest_snapshot,
            'expires_at' => is_string($data['expires_at'] ?? null) && strlen($data['expires_at']) <= 255 ? $data['expires_at'] : null,
            'status' => match ($data['status']) {
                'paid' => 'confirmed',
                'expired' => 'expired',
                default => 'pending',
            },
        ]);
    }

    private function rejectionMessage(mixed $code, mixed $message): ?string
    {
        $limit = is_string($message) && preg_match('/^The (minimum|maximum) amount is (₱[0-9,.]{1,24})\.$/uD', $message, $matches)
            ? $matches : [];

        return match ($code) {
            'amount_too_small' => ($limit[1] ?? null) === 'minimum'
                ? 'QuickPay requires a minimum payment of '.$limit[2].'. Choose another payment method.'
                : 'The amount is below your QuickPay minimum. Choose another payment method.',
            'amount_too_large' => ($limit[1] ?? null) === 'maximum'
                ? 'QuickPay accepts payments up to '.$limit[2].'. Choose another payment method.'
                : 'The amount exceeds your QuickPay maximum. Choose another payment method.',
            'channel_not_offered' => 'The configured payment channel is unavailable on this QuickPay account. Choose another payment method.',
            'invalid_input', 'amount_required' => 'QuickPay rejected the payment details. Review the bill amount or choose another payment method.',
            default => null,
        };
    }

    private static function isHttps(mixed $url): bool
    {
        return is_string($url) && strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function image(mixed $image): ?string
    {
        if (self::isHttps($image)) {
            return $image;
        }

        return is_string($image) && strlen($image) <= 500000
            && preg_match('~^data:image/png;base64,[A-Za-z0-9+/]+={0,2}$~D', $image)
            ? $image : null;
    }

    private function logResponse(QuickPayCheckout $checkout, int $httpStatus, mixed $body): void
    {
        $channel = $checkout->request_body['channel'] ?? null;
        if (! in_array($channel, ['qrph', 'banks'], true)) {
            return;
        }

        $data = is_array($body) ? $body : [];
        Log::info('QuickPay gateway response.', [
            'checkout_id' => $checkout->id,
            'provider_id' => $data['id'] ?? $checkout->provider_id,
            'channel' => $channel,
            'operation' => $checkout->provider_id ? 'status' : 'create',
            'http_status' => $httpStatus,
            'quickpay_status' => $data['status'] ?? null,
            'error' => $data['error'] ?? null,
            'message' => $data['message'] ?? null,
            'has_qr_emv' => filled($data['qr']['emv'] ?? null),
            'has_qr_image' => filled($data['qr']['image'] ?? null),
        ]);
    }

    private function logFailure(QuickPayCheckout $checkout, string $error, array $context = []): void
    {
        $channel = $checkout->request_body['channel'] ?? null;
        if (in_array($channel, ['qrph', 'banks'], true)) {
            Log::warning('QuickPay gateway request failed.', [
                'checkout_id' => $checkout->id,
                'channel' => $channel,
                'operation' => $checkout->provider_id ? 'status' : 'create',
                'error' => $error,
            ] + $context);
        }
    }
}
