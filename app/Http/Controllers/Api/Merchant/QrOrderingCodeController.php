<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\PartnerLocation;
use App\PartnerLocationTable;
use App\QrOrderingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrOrderingCodeController extends Controller
{
    public function index(PartnerLocation $partnerLocation, Request $request): JsonResponse
    {
        $this->authorizeLocation($partnerLocation, $request);

        return response()->json([
            'status' => 1,
            'qr_codes' => $this->codes($partnerLocation),
        ]);
    }

    public function store(PartnerLocation $partnerLocation, Request $request): JsonResponse
    {
        $this->authorizeLocation($partnerLocation, $request);
        $validated = $request->validate([
            'dining_table_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $table = null;
        if (! empty($validated['dining_table_id'])) {
            $table = PartnerLocationTable::query()
                ->whereKey($validated['dining_table_id'])
                ->where('partner_location_id', $partnerLocation->id)
                ->firstOrFail();
        }

        $code = QrOrderingCode::query()
            ->where('partner_location_id', $partnerLocation->id)
            ->when(
                $table,
                fn ($query) => $query->where('dining_table_id', $table->id),
                fn ($query) => $query->whereNull('dining_table_id')
            )
            ->first() ?? new QrOrderingCode;

        $code->fill([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partnerLocation->partner_id,
            'partner_location_id' => $partnerLocation->id,
            'dining_table_id' => $table?->id,
            'label' => $validated['label'] ?? $table?->name ?? 'Branch QR',
            'expires_at' => $validated['expires_at'] ?? null,
            'active' => true,
        ])->save();

        return response()->json([
            'status' => 1,
            'message' => $code->wasRecentlyCreated
                ? 'QR ordering code created.'
                : 'QR ordering code regenerated.',
            'qr_code' => $this->payload($code->fresh(['diningTable'])),
        ], $code->wasRecentlyCreated ? 201 : 200);
    }

    public function update(
        PartnerLocation $partnerLocation,
        QrOrderingCode $qrOrderingCode,
        Request $request
    ): JsonResponse {
        $this->authorizeLocation($partnerLocation, $request);
        $this->authorizeCode($partnerLocation, $qrOrderingCode);
        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ]);

        $qrOrderingCode->update($validated);

        return response()->json([
            'status' => 1,
            'message' => 'QR ordering code updated.',
            'qr_code' => $this->payload($qrOrderingCode->fresh(['diningTable'])),
        ]);
    }

    private function authorizeLocation(PartnerLocation $partnerLocation, Request $request): void
    {
        $user = $request->user('api') ?: $request->user();

        abort_unless($user, 401);
        abort_unless(
            $user->merchant && (int) $user->merchant->id === (int) $partnerLocation->partner_id,
            403
        );
    }

    private function authorizeCode(
        PartnerLocation $partnerLocation,
        QrOrderingCode $qrOrderingCode
    ): void {
        abort_unless(
            (int) $qrOrderingCode->partner_location_id === (int) $partnerLocation->id,
            404
        );
    }

    private function codes(PartnerLocation $partnerLocation)
    {
        return $partnerLocation->qrOrderingCodes()
            ->with('diningTable')
            ->orderBy('label')
            ->get()
            ->map(fn (QrOrderingCode $code) => $this->payload($code))
            ->values();
    }

    private function payload(QrOrderingCode $code): array
    {
        return [
            'id' => $code->id,
            'uuid' => $code->uuid,
            'label' => $code->label,
            'active' => $code->active,
            'expires_at' => $code->expires_at,
            'branch_id' => $code->partner_location_id,
            'partner_id' => $code->partner_id,
            'ordering_url' => $code->orderingUrl(),
            'table' => $code->diningTable ? [
                'id' => $code->diningTable->id,
                'name' => $code->diningTable->name,
            ] : null,
            'menu_url' => route('api.qr.menu', [
                'uuid' => $code->uuid,
                'branchId' => $code->partner_location_id,
            ]),
        ];
    }
}
