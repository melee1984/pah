<?php

namespace App\Http\Controllers\Merchant;

use App\Coupon;
use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Partners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function create(): View
    {
        return view('merchant.pages.coupons.form', ['coupon' => new Coupon(['active' => true])]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        Coupon::create($this->data($request));

        return redirect()->route('merchant.dashboard.promotions.index')->with('success', 'Discount coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorizeCoupon($coupon);

        return view('merchant.pages.coupons.form', compact('coupon'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->authorizeCoupon($coupon);
        $coupon->update($this->data($request));

        return redirect()->route('merchant.dashboard.promotions.index')->with('success', 'Discount coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorizeCoupon($coupon);
        $coupon->delete();

        return redirect()->route('merchant.dashboard.promotions.index')->with('success', 'Discount coupon deleted successfully.');
    }

    private function data(CouponRequest $request): array
    {
        return array_merge($request->safe()->except('partner_id'), [
            'partner_id' => $this->partnerId(),
            'active' => $request->boolean('active'),
            'valid_at' => $request->input('valid_until'),
        ]);
    }

    private function authorizeCoupon(Coupon $coupon): void
    {
        abort_unless((int) $coupon->partner_id === $this->partnerId(), 404);
    }

    private function partnerId(): int
    {
        $partnerId = Partners::query()->where('user_id', Auth::id())->value('id');
        abort_unless($partnerId, 403, 'A merchant account is required.');

        return (int) $partnerId;
    }
}
