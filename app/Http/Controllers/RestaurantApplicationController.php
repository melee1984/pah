<?php

namespace App\Http\Controllers;

use App\Partners;
use App\RestaurantEnrollmentDocument;
use App\RestaurantInvitation;
use App\Services\RestaurantApplicationNotifier;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantApplicationController extends Controller
{
    public function showAgent(Request $request, Partners $restaurant): View
    {
        $this->authorizeAgent($request, $restaurant);

        return $this->render($restaurant, true);
    }

    public function showMerchant(Request $request): View
    {
        return $this->render($this->merchantRestaurant($request), false);
    }

    public function updateAgent(Request $request, Partners $restaurant): RedirectResponse
    {
        $this->authorizeAgent($request, $restaurant);

        return $this->update($request, $restaurant);
    }

    public function updateMerchant(Request $request): RedirectResponse
    {
        return $this->update($request, $this->merchantRestaurant($request));
    }

    public function uploadAgent(Request $request, Partners $restaurant): RedirectResponse
    {
        $this->authorizeAgent($request, $restaurant);

        return $this->upload($request, $restaurant);
    }

    public function uploadMerchant(Request $request): RedirectResponse
    {
        return $this->upload($request, $this->merchantRestaurant($request));
    }

    public function documentAgent(Request $request, Partners $restaurant, RestaurantEnrollmentDocument $document)
    {
        $this->authorizeAgent($request, $restaurant);

        return $this->serveDocument($restaurant, $document);
    }

    public function documentMerchant(Request $request, RestaurantEnrollmentDocument $document)
    {
        return $this->serveDocument($this->merchantRestaurant($request), $document);
    }

    private function serveDocument(Partners $restaurant, RestaurantEnrollmentDocument $document)
    {
        abort_unless($document->partner_id === $restaurant->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    private function render(Partners $restaurant, bool $agentView): View
    {
        $restaurant->load(['enrollmentDocuments', 'agent']);
        $contact = User::query()->find($restaurant->user_id);
        $documents = $restaurant->enrollmentDocuments->keyBy('document_type');

        return view($agentView ? 'agent.restaurants.show' : 'merchant.pages.application', compact('restaurant', 'contact', 'documents'));
    }

    private function update(Request $request, Partners $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:75'],
            'lastname' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:255', Rule::unique('partners', 'email')->ignore($restaurant->id),
                Rule::unique('users', 'email')->ignore($restaurant->user_id)],
            'mobile' => ['required', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'business_structure' => ['required', Rule::in(['sole_proprietorship', 'corporation', 'partnership', 'cooperative'])],
            'enrolling_as' => ['required', Rule::in(['owner', 'authorized_representative'])],
            'registered_business_name' => ['required', 'string', 'max:255'],
            'tin' => ['required', 'string', 'max:30'],
            'business_registration_number' => ['required', 'string', 'max:100'],
            'payout_account_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['business_structure'] !== 'sole_proprietorship' && $validated['enrolling_as'] === 'owner') {
            return back()->withInput()->withErrors(['enrolling_as' => 'Choose authorized representative for this business structure.']);
        }

        if ($validated['email'] !== $restaurant->email
            && RestaurantInvitation::query()->where('restaurant_id', $restaurant->id)->whereNull('accepted_at')->exists()) {
            return back()->withInput()->withErrors(['email' => 'The invitation is still pending. Update the email after the restaurant contact sets up the account.']);
        }

        $changed = DB::transaction(function () use ($restaurant, $validated) {
            $restaurant->fill(collect($validated)->except(['firstname', 'lastname'])->all());
            $user = User::query()->findOrFail($restaurant->user_id);
            $attributes = ['email' => $validated['email']];
            foreach (['firstname', 'lastname', 'mobile'] as $field) {
                if (Schema::hasColumn('users', $field)) {
                    $attributes[$field] = $validated[$field];
                }
            }
            if (Schema::hasColumn('users', 'name')) {
                $attributes['name'] = trim($validated['firstname'].' '.$validated['lastname']);
            }
            $user->forceFill($attributes);

            if (! $restaurant->isDirty() && ! $user->isDirty()) {
                return false;
            }

            $restaurant->application_status = 'pending_review';
            $restaurant->active = false;
            $restaurant->verified_at = null;
            $restaurant->save();
            $user->save();
            $restaurant->enrollmentDocuments()->where('status', 'approved')->update([
                'status' => 'pending_verification', 'reviewed_at' => null,
            ]);

            return true;
        });

        if ($changed) {
            app(RestaurantApplicationNotifier::class)->send($restaurant,
                'Application details were updated and returned to pending review. Previously approved documents need re-verification.');
        }

        return back()->with('success', $changed ? 'Application details updated.' : 'No changes were needed.');
    }

    private function upload(Request $request, Partners $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(array_keys(RestaurantEnrollmentDocument::LABELS))],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validated['document_type'] === 'authorization_document' && $restaurant->enrolling_as !== 'authorized_representative') {
            return back()->withErrors(['document_type' => 'An authorization document is only needed for an authorized representative.']);
        }

        $type = $validated['document_type'];
        $existing = $restaurant->enrollmentDocuments()->where('document_type', $type)->first();
        $oldPath = $existing?->file_path;
        $path = $request->file('document')->store('restaurant-enrollment/'.$restaurant->id, 'local');

        try {
            DB::transaction(function () use ($restaurant, $existing, $type, $path, $request) {
                $document = $existing ?: new RestaurantEnrollmentDocument(['document_type' => $type]);
                $document->restaurant()->associate($restaurant);
                $document->fill([
                    'file_path' => $path,
                    'original_name' => $request->file('document')->getClientOriginalName(),
                    'status' => 'pending_verification',
                    'remarks' => null,
                    'expires_at' => null,
                    'reviewed_at' => null,
                ])->save();

                $restaurant->forceFill(['application_status' => 'pending_review', 'active' => false, 'verified_at' => null])->save();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        app(RestaurantApplicationNotifier::class)->send($restaurant,
            RestaurantEnrollmentDocument::LABELS[$type].' uploaded. Status: Pending Verification.');

        return back()->with('success', 'Document uploaded and sent for verification.');
    }

    private function authorizeAgent(Request $request, Partners $restaurant): void
    {
        abort_unless($restaurant->agent_id === $request->user('agent')?->id, 404);
    }

    private function merchantRestaurant(Request $request): Partners
    {
        return Partners::query()->where('user_id', $request->user()->id)->whereNotNull('agent_id')->firstOrFail();
    }
}
