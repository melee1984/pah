<?php

namespace App\Http\Controllers;

use App\SystemIncident;
use Illuminate\View\View;

class SystemStatusController extends Controller
{
    public function __invoke(): View
    {
        $activeQuery = SystemIncident::query()->active();

        return view('pages.status', [
            'overallStatus' => SystemIncident::overallStatus($activeQuery),
            'activeIncidents' => $activeQuery->latest('updated_at')->get(),
            'resolvedIncidents' => SystemIncident::query()
                ->resolved()
                ->latest('resolved_at')
                ->latest('updated_at')
                ->paginate(10),
        ]);
    }
}
