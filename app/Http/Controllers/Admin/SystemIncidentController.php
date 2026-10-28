<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SystemIncident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemIncidentController extends Controller
{
    public function index(): View
    {
        $activeQuery = SystemIncident::query()->active();

        return view('dashboard.pages.system-status.index', [
            'overallStatus' => SystemIncident::overallStatus($activeQuery),
            'incidents' => SystemIncident::query()->latest('updated_at')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateIncident($request);
        $validated['created_by'] = auth()->id();
        $validated['resolved_at'] = $validated['status'] === SystemIncident::STATUS_RESOLVED ? now() : null;

        SystemIncident::query()->create($validated);

        return back()->with('success', 'System issue created successfully.');
    }

    public function update(Request $request, SystemIncident $systemIncident): RedirectResponse
    {
        $validated = $this->validateIncident($request);
        $validated['resolved_at'] = match ($validated['status']) {
            SystemIncident::STATUS_RESOLVED => $systemIncident->resolved_at ?? now(),
            default => null,
        };

        $systemIncident->update($validated);

        return back()->with('success', 'System issue updated successfully.');
    }

    private function validateIncident(Request $request): array
    {
        $request->merge([
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in([
                SystemIncident::TYPE_ISSUE,
                SystemIncident::TYPE_MAINTENANCE,
            ])],
            'status' => ['required', Rule::in([
                SystemIncident::STATUS_INVESTIGATING,
                SystemIncident::STATUS_IN_PROGRESS,
                SystemIncident::STATUS_RESOLVED,
            ])],
        ]);
    }
}
