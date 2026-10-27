<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\StatementAccount;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
    * View 
    * @return [type] [description]
    */
    public function today() 
    {	
		return view('merchant.pages.reports.today');        
    }

    /*
    * View 
    * @return [type] [description]
    */
    public function salesReport() 
    {	
		return view('merchant.pages.reports.salesReport');        
    }
    /*
    * View 
    * @return [type] [description]
    */
    public function soa()
    {
        $statements = StatementAccount::query()
            ->where('partner_id', Auth::user()->merchant->id)
            ->whereIn('status', [StatementAccount::STATUS_PUBLISHED, StatementAccount::STATUS_ISSUED_LEGACY])
            ->latest('issued_at')
            ->paginate(20);

		return view('merchant.pages.reports.soa', compact('statements'));
    }

    public function statement(StatementAccount $statement)
    {
        abort_unless((int) $statement->partner_id === (int) Auth::user()->merchant->id, 404);
        abort_unless($statement->isPublished(), 404);
        $statement->load(['partner', 'items']);

        return view('merchant.pages.reports.statement', compact('statement'));
    }
}
