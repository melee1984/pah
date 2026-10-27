<?php

namespace App\Http\Controllers\Agent;

use App\AgentStatementAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatementAccountController extends Controller
{
    public function index(Request $request): View
    {
        $statements = $request->user('agent')
            ->statements()
            ->where('status', AgentStatementAccount::STATUS_PUBLISHED)
            ->latest('issued_at')
            ->paginate(20);

        return view('agent.statements.index', compact('statements'));
    }

    public function show(Request $request, AgentStatementAccount $statement): View
    {
        abort_unless((int) $statement->agent_id === (int) $request->user('agent')->getKey(), 404);
        abort_unless($statement->isPublished(), 404);
        $statement->load(['agent', 'items']);

        return view('agent.statements.show', compact('statement'));
    }
}
