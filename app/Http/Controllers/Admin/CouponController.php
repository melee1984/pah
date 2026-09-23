<?php

namespace App\Http\Controllers\Admin;

use App\Coupon;
use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Partners;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::query()->withUsageCount()->with('partner:id,restaurant_name')->latest()->paginate(20);

        return view('dashboard.pages.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('dashboard.pages.coupons.form', [
            'coupon' => new Coupon(['active' => true]),
            'partners' => $this->partners(),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        Coupon::create($this->data($request));

        return redirect()->route('dashboard.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('dashboard.pages.coupons.form', [
            'coupon' => $coupon,
            'partners' => $this->partners(),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->data($request));

        return redirect()->route('dashboard.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('dashboard.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    private function data(CouponRequest $request): array
    {
        return array_merge($request->validated(), [
            'active' => $request->boolean('active'),
            'valid_at' => $request->input('valid_until'),
        ]);
    }

    private function partners()
    {
        return Partners::query()->where('active', true)->orderBy('restaurant_name')->get(['id', 'restaurant_name']);
    }
}
