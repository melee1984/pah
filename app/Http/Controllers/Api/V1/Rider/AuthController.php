<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Services\RiderApiService;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', Rule::in(['ios', 'android', 'web'])],
            'app_version' => ['nullable', 'string', 'max:30'],
            'push_token' => ['nullable', 'string', 'max:4096'],
        ]);

        $user = User::query()
            ->where('email', Str::lower($validated['email']))
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided rider credentials are incorrect.',
            ], 401);
        }

        $rider = $this->riders->riderForUser($user);
        $account = $this->riders->accountStatus($user, $rider);

        if (! $rider || ! $account['allowed']) {
            return response()->json([
                'message' => $account['message'],
                'account_status' => $account['status'],
                'capabilities' => [
                    'can_go_online' => false,
                    'can_accept_offers' => false,
                    'can_start_delivery' => false,
                ],
            ], 403);
        }

        $deviceKey = $validated['device_id'] ?? (string) Str::uuid();
        $token = $user->createToken(
            "rider:{$deviceKey}",
            ['rider:*'],
            now()->addDays(30),
        );

        $deviceReference = (string) Str::uuid();
        DB::table('rider_api_devices')->updateOrInsert(
            [
                'rider_id' => $rider->id,
                'device_key' => $deviceKey,
            ],
            [
                'reference' => $deviceReference,
                'personal_access_token_id' => $token->accessToken->id,
                'push_token' => isset($validated['push_token'])
                    ? Crypt::encryptString($validated['push_token'])
                    : null,
                'platform' => $validated['platform'] ?? null,
                'device_model' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $device = DB::table('rider_api_devices')
            ->where('rider_id', $rider->id)
            ->where('device_key', $deviceKey)
            ->first();

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
            'account_status' => 'approved',
            'rider' => $this->riders->riderData($user, $rider),
            'device' => $this->deviceData($device),
            'capabilities' => [
                'can_go_online' => true,
                'can_accept_offers' => true,
                'can_start_delivery' => true,
            ],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $device = DB::table('rider_api_devices')
            ->where('personal_access_token_id', $currentToken->id)
            ->first();
        $deviceKey = $device?->device_key ?? (string) Str::uuid();
        $newToken = $user->createToken(
            "rider:{$deviceKey}",
            ['rider:*'],
            now()->addDays(30),
        );

        if ($device) {
            DB::table('rider_api_devices')
                ->where('id', $device->id)
                ->update([
                    'personal_access_token_id' => $newToken->accessToken->id,
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $currentToken->delete();

        return response()->json([
            'access_token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $newToken->accessToken->expires_at?->toISOString(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        DB::table('rider_api_devices')
            ->where('personal_access_token_id', $token->id)
            ->update([
                'revoked_at' => now(),
                'personal_access_token_id' => null,
                'updated_at' => now(),
            ]);

        $token->delete();

        return response()->json(['message' => 'This rider device has been logged out.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $rider = $this->riders->riderForUser($request->user());

        if ($rider) {
            DB::table('rider_api_devices')
                ->where('rider_id', $rider->id)
                ->update([
                    'revoked_at' => now(),
                    'personal_access_token_id' => null,
                    'updated_at' => now(),
                ]);
        }

        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All rider devices have been logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $rider = $this->riders->riderForUser($request->user());
        $account = $this->riders->accountStatus($request->user(), $rider);

        return response()->json([
            'account_status' => $account['status'],
            'rider' => $rider ? $this->riders->riderData($request->user(), $rider) : null,
            'capabilities' => [
                'can_go_online' => $account['allowed'],
                'can_accept_offers' => $account['allowed'],
                'can_start_delivery' => $account['allowed'],
            ],
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $devices = DB::table('rider_api_devices')
            ->where('rider_id', $rider->id)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (object $device) => [
                ...$this->deviceData($device),
                'is_current' => (int) $device->personal_access_token_id === (int) $currentTokenId,
            ]);

        return response()->json(['devices' => $devices]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $this->validateDevice($request);
        $rider = $this->riders->rider($request);
        $tokenId = $request->user()->currentAccessToken()->id;

        DB::table('rider_api_devices')->updateOrInsert(
            ['rider_id' => $rider->id, 'device_key' => $validated['device_id']],
            [
                'reference' => (string) Str::uuid(),
                'personal_access_token_id' => $tokenId,
                'push_token' => isset($validated['push_token'])
                    ? Crypt::encryptString($validated['push_token'])
                    : null,
                'platform' => $validated['platform'],
                'device_model' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $device = DB::table('rider_api_devices')
            ->where('rider_id', $rider->id)
            ->where('device_key', $validated['device_id'])
            ->first();

        return response()->json([
            'message' => 'Rider device registered.',
            'device' => $this->deviceData($device),
        ], 201);
    }

    public function updateDevice(Request $request, string $device): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);
        $rider = $this->riders->rider($request);
        $record = DB::table('rider_api_devices')
            ->where('rider_id', $rider->id)
            ->where('reference', $device)
            ->first();
        abort_if(! $record, 404);

        $updates = ['updated_at' => now(), 'last_seen_at' => now()];
        if (array_key_exists('push_token', $validated)) {
            $updates['push_token'] = $validated['push_token']
                ? Crypt::encryptString($validated['push_token'])
                : null;
        }
        if (array_key_exists('device_name', $validated)) {
            $updates['device_model'] = $validated['device_name'];
        }
        if (array_key_exists('app_version', $validated)) {
            $updates['app_version'] = $validated['app_version'];
        }

        DB::table('rider_api_devices')->where('id', $record->id)->update($updates);

        return response()->json([
            'message' => 'Rider device updated.',
            'device' => $this->deviceData(
                DB::table('rider_api_devices')->where('id', $record->id)->first(),
            ),
        ]);
    }

    public function revokeDevice(Request $request, string $device): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $record = DB::table('rider_api_devices')
            ->where('rider_id', $rider->id)
            ->where('reference', $device)
            ->first();
        abort_if(! $record, 404);

        if ($record->personal_access_token_id) {
            DB::table('personal_access_tokens')
                ->where('id', $record->personal_access_token_id)
                ->delete();
        }

        DB::table('rider_api_devices')->where('id', $record->id)->update([
            'personal_access_token_id' => null,
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Rider device revoked.']);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', Rule::in(['login', 'registration', 'recovery'])],
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'destination' => [
                'required',
                'string',
                Rule::when(
                    $request->input('channel') === 'email',
                    ['email'],
                    ['regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
                ),
            ],
        ]);

        if ($validated['channel'] === 'sms' && ! config('services.semaphore.key')) {
            return response()->json([
                'message' => 'The rider SMS verification provider is not configured.',
            ], 503);
        }

        $code = (string) random_int(100000, 999999);
        $reference = (string) Str::uuid();

        DB::table('rider_api_otp_challenges')->insert([
            'reference' => $reference,
            'purpose' => $validated['purpose'],
            'channel' => $validated['channel'],
            'destination' => Str::lower($validated['destination']),
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            if ($validated['channel'] === 'email') {
                Mail::raw(
                    "Your Pahatud Rider verification code is {$code}. It expires in 10 minutes.",
                    fn ($message) => $message
                        ->to($validated['destination'])
                        ->subject('Pahatud Rider verification code'),
                );
            } else {
                Http::asForm()
                    ->timeout(10)
                    ->post(config('services.semaphore.url'), [
                        'apikey' => config('services.semaphore.key'),
                        'number' => $validated['destination'],
                        'message' => "Your Pahatud Rider verification code is {$code}. It expires in 10 minutes.",
                        'sendername' => config('services.semaphore.sender'),
                    ])
                    ->throw();
            }
        } catch (Throwable $exception) {
            DB::table('rider_api_otp_challenges')->where('reference', $reference)->delete();

            throw $exception;
        }

        return response()->json([
            'message' => 'Verification code sent.',
            'challenge_id' => $reference,
            'expires_in_seconds' => 600,
        ], 202);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required', 'digits:6'],
        ]);
        $challenge = DB::table('rider_api_otp_challenges')
            ->where('reference', $validated['challenge_id'])
            ->first();

        if (! $challenge || $challenge->verified_at || now()->greaterThan($challenge->expires_at)) {
            return response()->json(['message' => 'This verification challenge is invalid or expired.'], 422);
        }

        if ($challenge->attempts >= 5) {
            return response()->json(['message' => 'Too many verification attempts.'], 429);
        }

        if (! hash_equals($challenge->code_hash, hash('sha256', $validated['code']))) {
            DB::table('rider_api_otp_challenges')->where('id', $challenge->id)->increment('attempts');

            return response()->json(['message' => 'The verification code is incorrect.'], 422);
        }

        $verificationToken = Str::random(80);
        DB::table('rider_api_otp_challenges')->where('id', $challenge->id)->update([
            'verification_token_hash' => hash('sha256', $verificationToken),
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Verification code confirmed.',
            'verification_token' => $verificationToken,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->merge([
            'purpose' => 'recovery',
            'channel' => 'email',
            'destination' => $request->input('email'),
        ]);

        return $this->sendOtp($request);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'verification_token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:7', 'max:72', 'confirmed'],
        ]);
        $challenge = DB::table('rider_api_otp_challenges')
            ->where('reference', $validated['challenge_id'])
            ->where('purpose', 'recovery')
            ->where('destination', Str::lower($validated['email']))
            ->whereNotNull('verified_at')
            ->first();

        if (
            ! $challenge
            || ! $challenge->verification_token_hash
            || ! hash_equals(
                $challenge->verification_token_hash,
                hash('sha256', $validated['verification_token']),
            )
        ) {
            return response()->json(['message' => 'The password reset authorization is invalid.'], 422);
        }

        $user = User::query()->where('email', Str::lower($validated['email']))->first();
        abort_if(! $user, 422, 'The password reset authorization is invalid.');

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $user->tokens()->delete();
        DB::table('rider_api_otp_challenges')->where('id', $challenge->id)->delete();

        return response()->json(['message' => 'Rider password reset successfully.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDevice(Request $request): array
    {
        return $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'push_token' => ['nullable', 'string', 'max:4096'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
            'device_name' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function deviceData(object $device): array
    {
        return [
            'id' => $device->reference,
            'device_id' => $device->device_key,
            'platform' => $device->platform,
            'device_name' => $device->device_model,
            'app_version' => $device->app_version,
            'push_registered' => (bool) $device->push_token,
            'last_seen_at' => $device->last_seen_at,
            'revoked_at' => $device->revoked_at,
        ];
    }
}
