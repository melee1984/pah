<?php

namespace App\Http\Middleware;

use App\Agent;
use App\Coupon;
use App\Model\Orders\Orders;
use App\MerchantApplication;
use App\Model\Rider\Rider;
use App\Partners;
use App\PartnerPromotion;
use App\RiderApplication;
use App\Services\AdminCityScope;
use App\StatementAccount;
use Closure;
use Auth;

class isAdmin
{
    public function __construct(private readonly AdminCityScope $cityScope) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            if(auth()->user()->isAdmin()) {
                $request->attributes->set(
                    'can_manage_managers',
                    auth()->user()->hasAnyRole(['admin', 'super']),
                );

                foreach ($request->route()?->parameters() ?? [] as $parameter) {
                    match (true) {
                        $parameter instanceof Partners => $this->cityScope->authorizePartner($parameter),
                        $parameter instanceof MerchantApplication => $this->cityScope->authorizeMerchantApplication($parameter),
                        $parameter instanceof Agent => $this->cityScope->authorizeAgent($parameter),
                        $parameter instanceof Rider => $this->cityScope->authorizeRider($parameter),
                        $parameter instanceof RiderApplication => $this->cityScope->authorizeRiderApplication($parameter),
                        $parameter instanceof Orders => $this->cityScope->authorizePartner($parameter->partner),
                        $parameter instanceof Coupon => $this->cityScope->authorizePartner($parameter->partner),
                        $parameter instanceof PartnerPromotion => $this->cityScope->authorizePartner($parameter->partner),
                        $parameter instanceof StatementAccount => $this->cityScope->authorizePartner($parameter->partner),
                        default => null,
                    };
                }

                return $next($request);
            }
        }
        return redirect('/');
    }
}
