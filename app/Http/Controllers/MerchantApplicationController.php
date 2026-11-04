<?php

namespace App\Http\Controllers;

use App\Agent;
use App\City;
use App\Mail\MerchantApplicationReceivedMail;
use App\Partners;
use App\RestaurantInvitation;
use App\Services\RestaurantEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MerchantApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $referralCode = $this->normalizeReferralCode($request->query('ref'));
        $referralAgent = $referralCode === '' ? null : Agent::query()
            ->where('referral_code', $referralCode)
            ->where('active', true)
            ->first();

        return view('merchant-program.register', [
            'cities' => City::query()->active()->orderBy('name')->get(),
            'commissionPercentage' => (float) config('agent.pahatud_commission_percentage', 20),
            'services' => Partners::SERVICES,
            'referralCode' => $referralAgent?->referral_code ?? $referralCode,
            'referralAgent' => $referralAgent,
        ]);
    }

    public function terms(): View
    {
        return view('merchant-program.terms');
    }

    public function store(Request $request): RedirectResponse
    {
        $referralCode = $this->normalizeReferralCode($request->input('referral_code'));
        $request->merge([
            'referral_code' => $referralCode === '' ? null : $referralCode,
            'city' => trim((string) $request->input('city')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'referral_code' => [
                'nullable',
                'string',
                'max:20',
                Rule::exists('agents', 'referral_code')->where(fn ($query) => $query->where('active', true)),
            ],
            'business_name' => ['required', 'string', 'max:255'],
            'registered_business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('partners', 'email'), Rule::unique('users', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => [
                'required',
                'string',
                'max:120',
                Rule::exists('cities', 'name')->where('active', true),
            ],
            'business_structure' => ['required', Rule::in(['sole_proprietorship', 'corporation', 'partnership', 'cooperative'])],
            'cuisine' => ['nullable', 'string', 'max:120'],
            'branch_count' => ['required', 'integer', 'min:1', 'max:500'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', Rule::in(array_keys(Partners::SERVICES))],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'business_description' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
            'merchant_terms' => ['accepted'],
        ], [
            'email.unique' => 'This email is already registered. Use the registration link request below, or sign in if your account is ready.',
            'referral_code.exists' => 'Enter a valid referral code from an active Pahatud agent.',
            'city.exists' => 'Select an active city.',
            'services.required' => 'Choose at least one service you want to offer.',
            'services.min' => 'Choose at least one service you want to offer.',
            'merchant_terms.accepted' => 'You must accept the Merchant Terms and Conditions before submitting your application.',
        ]);

        $referralAgent = blank($validated['referral_code'] ?? null) ? null : Agent::query()
            ->where('referral_code', $validated['referral_code'])
            ->where('active', true)
            ->firstOrFail();

        [$application, $invitationMail] = DB::transaction(function () use ($validated, $referralAgent, $request) {
            $application = Partners::query()->create([
                'agent_id' => $referralAgent?->getKey(),
                'restaurant_name' => $validated['business_name'],
                'registered_business_name' => $validated['registered_business_name'],
                'owner_name' => $validated['owner_name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'telephone' => $validated['telephone'] ?? null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'business_structure' => $validated['business_structure'],
                'cuisine' => $validated['cuisine'] ?? null,
                'branch_count' => $validated['branch_count'],
                'services' => $validated['services'],
                'website' => $validated['website'] ?? null,
                'description' => $validated['business_description'] ?? null,
                'percentage' => config('agent.pahatud_commission_percentage', 20),
                'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
                'recruitment_status' => Partners::STATUS_PENDING,
                'application_status' => 'pending_review',
                'recruitment_submitted_at' => now(),
                'active' => false,
                'account_type_id' => 1,
                'addup' => config('agent.default_commission_addup', true),
                'slug' => $this->uniqueSlug($validated['business_name']),
                'search_string' => Str::lower(trim($validated['business_name'].' '.$validated['city'])),
            ]);

            $invitationMail = app(RestaurantEnrollmentService::class)->provisionApplication($application, $request, false);

            return [$application, $invitationMail];
        });

        $mailSent = true;
        try {
            Mail::to($application->email)->send(new MerchantApplicationReceivedMail($application, $invitationMail->invitationUrl));
        } catch (Throwable $exception) {
            $mailSent = false;
            Log::error('Merchant application receipt email could not be delivered.', [
                'merchant_application_id' => $application->id,
                'email' => $application->email,
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('merchant.register.success')
            ->with('merchant_application_completed', true)
            ->with('merchant_application_mail_sent', $mailSent)
            ->with('merchant_application_email', $application->email);
    }

    public function success(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('merchant_application_completed')) {
            return redirect()->route('merchant.register');
        }

        return view('merchant-program.success', [
            'email' => $request->session()->get('merchant_application_email'),
            'mailSent' => $request->session()->get('merchant_application_mail_sent', false),
        ]);
    }

    public function requestLink(): View
    {
        return view('merchant-program.request-link');
    }

    public function resendLink(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($validated['email']));
        $key = 'merchant-registration-link:'.hash('sha256', $email);
        $message = 'If this email has an unfinished merchant registration, we will send a new link. Check your inbox and spam folder. If you already set your password, sign in.';

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('status', $message);
        }
        RateLimiter::hit($key, 60);

        try {
            $mail = DB::transaction(function () use ($email, $request) {
                $application = Partners::query()
                    ->where(fn ($query) => $query->where('application_source', Partners::APPLICATION_SOURCE_REGISTRATION)->orWhereNotNull('agent_id'))
                    ->where('email', $email)->lockForUpdate()->first();
                if (! $application) {
                    return null;
                }
                if (! $application->user_id) {
                    $invitationMail = app(RestaurantEnrollmentService::class)->provisionApplication($application, $request, false);

                    return new MerchantApplicationReceivedMail($application, $invitationMail->invitationUrl);
                }
                $invitation = RestaurantInvitation::query()->where('restaurant_id', $application->id)->lockForUpdate()->first();
                if (! $invitation || $invitation->accepted_at !== null || $invitation->email !== $application->email || $invitation->user_id !== $application->user_id) {
                    return null;
                }
                $token = Str::random(64);
                $invitation->update([
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addHours(config('agent.restaurant_invitation_expire_hours')),
                ]);

                return new MerchantApplicationReceivedMail($application, route('restaurant.invitation.show', $token));
            });
            if ($mail) {
                Mail::to($mail->application->email)->send($mail);
            }
        } catch (Throwable $exception) {
            Log::error('Merchant registration link could not be sent.', ['exception' => $exception->getMessage()]);

            return back()->withErrors(['email' => 'We could not send a registration link right now. Please try again shortly.']);
        }

        return back()->with('status', $message);
    }

    private function normalizeReferralCode(mixed $code): string
    {
        return mb_strtoupper(trim((string) $code));
    }

    private function uniqueSlug(string $businessName): string
    {
        $base = Str::slug($businessName) ?: 'merchant';
        $slug = $base;
        $suffix = 2;

        while (Partners::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
