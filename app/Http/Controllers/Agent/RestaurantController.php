<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
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
