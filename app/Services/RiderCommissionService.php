<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiderCommissionService
{
    public function commissionCentavos(object $delivery): int
    {
        $percentage = isset($delivery->commission_percentage)
            ? (float) $delivery->commission_percentage
            : (float) config('rider.pahatud_commission_percentage', 20);

        return max(0, (int) round((int) $delivery->earnings_centavos * ($percentage / 100)));
    }

    public function ensureSufficientWallet(int $riderId, object $delivery): void
    {
        $requiredCentavos = $this->commissionCentavos($delivery);
        $availableCentavos = $this->availableCentavos($riderId);

        abort_if(
            $availableCentavos < $requiredCentavos,
            409,
            sprintf(
                'Insufficient wallet balance. At least ₱%s is required to accept this booking.',
                number_format($requiredCentavos / 100, 2),
            ),
        );
    }

    public function deductForCompletedDelivery(object $delivery): void
    {
        $commissionCentavos = $this->commissionCentavos($delivery);

        if ($commissionCentavos === 0) {
            return;
        }

        $wallet = DB::table('rider_api_wallets')
            ->where('rider_id', $delivery->rider_id)
            ->lockForUpdate()
            ->first();
        abort_if(! $wallet, 409, 'The rider wallet could not be found.');

        $alreadyDeducted = DB::table('rider_api_wallet_transactions')
            ->where('related_type', 'delivery')
            ->where('related_reference', $delivery->reference)
            ->where('type', 'pahatud_commission')
            ->exists();

        if ($alreadyDeducted) {
            return;
        }

        $newBalanceCentavos = $this->creditAmountToCentavos($wallet->credit_amount) - $commissionCentavos;
        DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
            'credit_amount' => $newBalanceCentavos / 100,
            'updated_at' => now(),
        ]);
        DB::table('rider_api_wallet_transactions')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $delivery->rider_id,
            'type' => 'pahatud_commission',
            'amount_centavos' => -$commissionCentavos,
            'balance_after_centavos' => $newBalanceCentavos,
            'description' => 'Pahatud delivery commission',
            'related_type' => 'delivery',
            'related_reference' => $delivery->reference,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function availableCentavos(int $riderId): int
    {
        $wallet = DB::table('rider_api_wallets')
            ->where('rider_id', $riderId)
            ->lockForUpdate()
            ->first();

        return $wallet ? $this->creditAmountToCentavos($wallet->credit_amount) : 0;
    }

    private function creditAmountToCentavos(mixed $creditAmount): int
    {
        return (int) round((float) $creditAmount * 100);
    }
}
