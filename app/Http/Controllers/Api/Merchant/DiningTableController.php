<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\PartnerLocation;
use App\PartnerLocationTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiningTableController extends Controller
{
    public function index(PartnerLocation $partnerLocation, Request $request): JsonResponse
    {
        $this->authorizeLocation($partnerLocation, $request);

        return response()->json([
            'status' => 1,
            'tables' => $this->tables($partnerLocation),
        ]);
    }

    public function store(PartnerLocation $partnerLocation, Request $request): JsonResponse
    {
        $this->authorizeLocation($partnerLocation, $request);
        $validated = $this->validateTable($request);

        $table = $partnerLocation->diningTables()->create($validated);

        return response()->json([
            'status' => 1,
            'message' => 'Dining table added.',
            'table' => $table,
            'tables' => $this->tables($partnerLocation),
        ], 201);
    }

    public function update(
        PartnerLocation $partnerLocation,
        PartnerLocationTable $diningTable,
        Request $request
    ): JsonResponse {
        $this->authorizeLocation($partnerLocation, $request);
        $this->authorizeTable($partnerLocation, $diningTable);

        $diningTable->update($this->validateTable($request, $diningTable));

        return response()->json([
            'status' => 1,
            'message' => 'Dining table updated.',
            'table' => $diningTable->fresh(),
            'tables' => $this->tables($partnerLocation),
        ]);
    }

    public function destroy(
        PartnerLocation $partnerLocation,
        PartnerLocationTable $diningTable,
        Request $request
    ): JsonResponse {
        $this->authorizeLocation($partnerLocation, $request);
        $this->authorizeTable($partnerLocation, $diningTable);
        $diningTable->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Dining table deleted.',
            'tables' => $this->tables($partnerLocation),
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

    private function authorizeTable(
        PartnerLocation $partnerLocation,
        PartnerLocationTable $diningTable
    ): void {
        abort_unless(
            (int) $diningTable->partner_location_id === (int) $partnerLocation->id,
            404
        );
    }

    private function validateTable(
        Request $request,
        ?PartnerLocationTable $diningTable = null
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('partner_location_tables', 'name')
                    ->where('partner_location_id', $request->route('partnerLocation')->id)
                    ->ignore($diningTable?->id),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'active' => ['required', 'boolean'],
            'is_available' => ['required', 'boolean'],
        ]);
    }

    private function tables(PartnerLocation $partnerLocation)
    {
        return $partnerLocation->diningTables()
            ->orderBy('name')
            ->get(['id', 'partner_location_id', 'name', 'capacity', 'active', 'is_available']);
    }
}
