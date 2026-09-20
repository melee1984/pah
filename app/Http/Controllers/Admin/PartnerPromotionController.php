<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PartnerPromotion;
use App\Partners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerPromotionController extends Controller
{
    public function index(): View
    {
        $promotions = PartnerPromotion::query()
            ->with('partner:id,restaurant_name,slug')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);

        return view('dashboard.pages.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('dashboard.pages.promotions.form', [
            'promotion' => new PartnerPromotion(['active' => true, 'sort_order' => 0]),
            'partners' => $this->partners(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image_path'] = $this->storeImage($request);
        $data['active'] = $request->boolean('active');

        PartnerPromotion::create($data);

        return redirect()->route('dashboard.promotions.index')
            ->with('success', 'Promotion created successfully.');
    }

    public function edit(PartnerPromotion $promotion): View
    {
        return view('dashboard.pages.promotions.form', [
            'promotion' => $promotion,
            'partners' => $this->partners(),
        ]);
    }

    public function update(Request $request, PartnerPromotion $promotion): RedirectResponse
    {
        $data = $this->validated($request, $promotion);
        $data['active'] = $request->boolean('active');
        $oldImagePath = null;

        if ($request->hasFile('image')) {
            $oldImagePath = $promotion->image_path;
            $data['image_path'] = $this->storeImage($request);
        }

        $promotion->update($data);

        if ($oldImagePath) {
            $this->deleteImage($oldImagePath);
        }

        return redirect()->route('dashboard.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    public function destroy(PartnerPromotion $promotion): RedirectResponse
    {
        $imagePath = $promotion->image_path;
        $promotion->delete();
        $this->deleteImage($imagePath);

        return redirect()->route('dashboard.promotions.index')
            ->with('success', 'Promotion deleted successfully.');
    }

    private function validated(Request $request, ?PartnerPromotion $promotion = null): array
    {
        return $request->validate([
            'partner_id' => [
                'required',
                'integer',
                Rule::exists('partners', 'id')->where('active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'link_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image' => [Rule::requiredIf(! $promotion), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                Rule::when($request->filled('starts_at'), ['after_or_equal:starts_at']),
            ],
        ]);
    }

    private function partners()
    {
        return Partners::query()
            ->where('active', true)
            ->orderBy('restaurant_name')
            ->get(['id', 'restaurant_name', 'slug']);
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
