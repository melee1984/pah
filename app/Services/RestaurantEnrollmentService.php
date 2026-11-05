<?php

namespace App\Services;

use App\Agent;
use App\Mail\MerchantTemporaryPasswordMail;
use App\Mail\RestaurantInvitationMail;
use App\Partners;
use App\RestaurantEnrollmentDocument;
use App\RestaurantInvitation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class RestaurantEnrollmentService
{
    /**
     * @return array{restaurant: Partners, mail_sent: bool}
     */
    public function enroll(Agent $agent, array $validated, Request $request): array
    {
        $storedPaths = [];
        $temporaryPassword = Str::password(12, symbols: false);

        try {
            [$restaurant, $user] = DB::transaction(function () use ($agent, $validated, $request, $temporaryPassword, &$storedPaths) {
                $user = $this->createPartnerUser($validated, $request, $temporaryPassword, true);
                $restaurant = $agent->restaurants()->create([
                    'user_id' => $user->getKey(),
                    'restaurant_name' => $validated['restaurant_name'],
                    'email' => $validated['email'],
                    'mobile' => $validated['mobile'],
                    'telephone' => $validated['telephone'] ?? null,
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'description' => $validated['description'] ?? null,
                    'slug' => $this->uniqueSlug($validated['restaurant_name']),
                    'search_string' => Str::lower(trim($validated['restaurant_name'].' '.$validated['city'])),
                    'active' => false,
                    'account_type_id' => 1, // restaurant
                    'percentage' => config('agent.pahatud_commission_percentage', 15),
                    'addup' => config('agent.default_commission_addup', true),
                    'business_structure' => $validated['business_structure'],
                    'enrolling_as' => $validated['enrolling_as'],
                    'registered_business_name' => $validated['registered_business_name'],
                    'tin' => $validated['tin'],
                    'business_registration_number' => $validated['business_registration_number'] ?? null,
                    'payout_account_name' => $validated['payout_account_name'],
                    'application_status' => 'pending_review',
                ]);

                foreach ([...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'] as $type) {
                    $file = $validated[$type] ?? null;

                    if (! $file) {
                        continue;
                    }

                    $path = $file->store('restaurant-enrollment/'.$restaurant->id, 'local');

                    if (! $path) {
                        throw new RuntimeException('The restaurant enrollment document could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $restaurant->enrollmentDocuments()->create([
                        'document_type' => $type,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'status' => 'pending_verification',
                        'remarks' => null,
                        'expires_at' => null,
                        'reviewed_at' => null,
                    ]);
                }

                $this->assignPartnerRole($user);

                return [$restaurant, $user];
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        $mailSent = true;

        try {
            Mail::to($restaurant->email)->send(new MerchantTemporaryPasswordMail(
                $user,
                $restaurant,
                $temporaryPassword,
            ));
        } catch (Throwable $exception) {
            $mailSent = false;
            Log::error('Merchant temporary password email could not be sent.', [
                'restaurant_id' => $restaurant->getKey(),
                'user_id' => $user->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }

        app(RestaurantApplicationNotifier::class)->send(
            $restaurant,
            'Application submitted. Uploaded documents are pending verification; missing documents may be added from the restaurant account.',
        );

        return ['restaurant' => $restaurant, 'mail_sent' => $mailSent];
    }

    /** Call inside a transaction; lock existing application rows before provisioning. */
    public function provisionApplication(Partners $restaurant, Request $request, bool $sendMail = true): ?RestaurantInvitationMail
    {
        if ($restaurant->user_id) {
            return null;
        }

        if (User::query()->where('email', $restaurant->email)->exists()) {
            throw ValidationException::withMessages([
                'review' => 'This email already belongs to a user account. Update the application to use a unique merchant email before account setup.',
            ]);
        }

        $names = preg_split('/\s+/', trim($restaurant->owner_name ?? ''), 2);
        $user = $this->createPartnerUser([
            'firstname' => Str::substr($names[0] ?? '', 0, 75),
            'lastname' => Str::substr($names[1] ?? '', 0, 75),
            'email' => $restaurant->email,
            'mobile' => $restaurant->mobile,
        ], $request);
        Role::findOrCreate('partner', 'web');
        $user->assignRole('partner');
        $restaurant->update(['user_id' => $user->getKey()]);

        $plainToken = Str::random(64);
        $invitation = RestaurantInvitation::query()->create([
            'user_id' => $user->getKey(),
            'restaurant_id' => $restaurant->getKey(),
            'email' => $restaurant->email,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(config('agent.restaurant_invitation_expire_hours')),
        ]);
        $invitation->setRelation('restaurant', $restaurant);
        $mail = new RestaurantInvitationMail(
            $invitation, $plainToken, $restaurant->owner_name ?? $restaurant->restaurant_name,
        );
        if ($sendMail) {
            Mail::to($restaurant->email)->send($mail);
        }

        return $mail;
    }

    private function createPartnerUser(
        array $validated,
        Request $request,
        ?string $password = null,
        bool $mustChangePassword = false,
    ): User
    {
        $attributes = [
            'email' => $validated['email'],
            'password' => Hash::make($password ?? Str::random(64)),
        ];

        if (Schema::hasColumn('users', 'must_change_password')) {
            $attributes['must_change_password'] = $mustChangePassword;
        }
        if ($mustChangePassword && Schema::hasColumn('users', 'temporary_password_created_at')) {
            $attributes['temporary_password_created_at'] = now();
        }

        if (Schema::hasColumn('users', 'name')) {
            $attributes['name'] = trim($validated['firstname'].' '.$validated['lastname']);
        }
        if (Schema::hasColumn('users', 'firstname')) {
            $attributes['firstname'] = $validated['firstname'];
        }
        if (Schema::hasColumn('users', 'lastname')) {
            $attributes['lastname'] = $validated['lastname'];
        }
        if (Schema::hasColumn('users', 'mobile')) {
            $attributes['mobile'] = $validated['mobile'];
        }
        if (Schema::hasColumn('users', 'ip_address')) {
            $attributes['ip_address'] = $request->ip();
        }
        if (Schema::hasColumn('users', 'api_token')) {
            $attributes['api_token'] = Str::random(80);
        }

        $user = new User;
        $user->forceFill($attributes)->save();

        return $user;
    }

    private function assignPartnerRole(User $user): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        Role::findOrCreate('partner', 'web');
        $user->assignRole('partner');
    }

    private function uniqueSlug(string $restaurantName): string
    {
        $slugBase = Str::slug($restaurantName) ?: 'restaurant';
        $slug = $slugBase;
        $suffix = 2;

        while (Partners::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix++;
        }

        return $slug;
    }
}
