<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\RestaurantEnrollmentDocument;
use App\Services\RestaurantEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $restaurants = $request->user('agent')
            ->restaurants()
            ->with(['enrollmentDocuments', 'enrollmentContact'])
            ->withCount('orders')
            ->withSum(['agentCommissions as commission_total' => fn ($query) => $query->earned()], 'commission_amount')
            ->latest()
            ->paginate(15);

        return view('agent.restaurants.index', compact('restaurants'));
    }

    public function create(): View
    {
        return view('agent.restaurants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('users'), 503, 'The Pahatud partner user schema is unavailable.');

        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:75'],
            'lastname' => ['required', 'string', 'max:75'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('partners', 'email'),
                Rule::unique('users', 'email'),
            ],
            'mobile' => ['required', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'business_structure' => ['required', Rule::in(['sole_proprietorship', 'corporation', 'partnership', 'cooperative'])],
            'enrolling_as' => [
                'required',
                Rule::in(['owner', 'authorized_representative']),
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === 'owner' && $request->input('business_structure') !== 'sole_proprietorship') {
                        $fail('For this business structure, enroll as an authorized representative and provide an authorization document.');
                    }
                },
            ],
            'registered_business_name' => ['required', 'string', 'max:255'],
            'tin' => ['required', 'string', 'max:30'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'payout_account_name' => ['required', 'string', 'max:255'],
            ...collect([...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'])
                ->mapWithKeys(fn ($type) => [$type => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ]])->all(),
        ]);

        $result = app(RestaurantEnrollmentService::class)->enroll(
            $request->user('agent'),
            $validated,
            $request,
        );

        if (! $result['mail_sent']) {
            return redirect()
                ->route('agent.restaurants.index')
                ->with('warning', 'The restaurant and partner account were created, but the invitation email could not be delivered. Please contact Pahatud support.');
        }

        return redirect()
            ->route('agent.restaurants.index')
            ->with('success', 'Restaurant enrollment submitted and an account setup invitation was sent to '.$validated['email'].'.');
    }
}
