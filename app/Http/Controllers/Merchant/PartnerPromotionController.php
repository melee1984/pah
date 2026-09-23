<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Coupon;
use App\PartnerPromotion;
use App\Partners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerPromotionController extends Controller
{
    public function index(): View
    {
        $partnerId = $this->partnerId();
        $promotions = PartnerPromotion::query()
            ->where('partner_id', $partnerId)
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);
        $coupons = Coupon::query()
            ->where('partner_id', $partnerId)
            ->latest()
            ->paginate(15, ['*'], 'coupon_page');

        return view('merchant.pages.promotions.index', compact('promotions', 'coupons'));
    }

    public function create(): View
    {
        return view('merchant.pages.promotions.form', [
            'promotion' => new PartnerPromotion(['active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['partner_id'] = $this->partnerId();
        $data['image_path'] = $this->storeImage($request);
        $data['active'] = $request->boolean('active');
        $data['approval_status'] = PartnerPromotion::APPROVAL_PENDING;

        PartnerPromotion::create($data);

        return redirect()->route('merchant.dashboard.promotions.index')
            ->with('success', 'Promotion submitted for administrator approval.');
    }

    public function edit(PartnerPromotion $promotion): View
    {
        $this->authorizePromotion($promotion);

        return view('merchant.pages.promotions.form', compact('promotion'));
    }

    public function update(Request $request, PartnerPromotion $promotion): RedirectResponse
    {
        $this->authorizePromotion($promotion);
        $data = $this->validated($request, $promotion);
        $data['active'] = $request->boolean('active');
        $data['approval_status'] = PartnerPromotion::APPROVAL_PENDING;
        $data['approved_at'] = null;
        $data['approved_by'] = null;
        $oldImagePath = null;

        if ($request->hasFile('image')) {
            $oldImagePath = $promotion->image_path;
            $data['image_path'] = $this->storeImage($request);
        }

        $promotion->update($data);

        if ($oldImagePath) {
            $this->deleteImage($oldImagePath);
        }

        return redirect()->route('merchant.dashboard.promotions.index')
            ->with('success', 'Promotion updated and resubmitted for administrator approval.');
    }

    public function destroy(PartnerPromotion $promotion): RedirectResponse
    {
        $this->authorizePromotion($promotion);
        $imagePath = $promotion->image_path;
        $promotion->delete();
        $this->deleteImage($imagePath);

        return redirect()->route('merchant.dashboard.promotions.index')
            ->with('success', 'Promotion deleted successfully.');
    }

    private function validated(Request $request, ?PartnerPromotion $promotion = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'link_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image' => [Rule::requiredIf(! $promotion), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), ['after_or_equal:starts_at'])],
        ]);
    }

    private function partnerId(): int
    {
        $partnerId = Partners::query()->where('user_id', Auth::id())->value('id');
        abort_unless($partnerId, 403, 'A merchant account is required.');

        return (int) $partnerId;
    }

    private function authorizePromotion(PartnerPromotion $promotion): void
    {
        abort_unless((int) $promotion->partner_id === $this->partnerId(), 404);
    }

    private function storeImage(Request $request): string
    {
        $image = $request->file('image');
        $directory = public_path('uploads/promotions');
        File::ensureDirectoryExists($directory);
        $filename = Str::uuid().'.'.$image->extension();
        $image->move($directory, $filename);

        return 'uploads/promotions/'.$filename;
    }

    private function deleteImage(string $imagePath): void
    {
        $promotionsRoot = realpath(public_path('uploads/promotions'));
        $fullPath = realpath(public_path($imagePath));

        if ($promotionsRoot && $fullPath && str_starts_with($fullPath, $promotionsRoot.DIRECTORY_SEPARATOR)) {
            File::delete($fullPath);
        }
    }
}
