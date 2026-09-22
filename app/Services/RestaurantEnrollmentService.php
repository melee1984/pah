<?php

namespace App\Services;

use App\Agent;
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
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class RestaurantEnrollmentService
{
    /**
     * @return array{restaurant: Partners, invitation: RestaurantInvitation, mail_sent: bool}
     */
    public function enroll(Agent $agent, array $validated, Request $request): array
    {
        $storedPaths = [];

        try {
            [$restaurant, $invitation, $plainToken] = DB::transaction(function () use ($agent, $validated, $request, &$storedPaths) {
                $user = $this->createPartnerUser($validated, $request);
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
                    'percentage' => config('agent.default_commission_percentage', 20),
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

                $plainToken = Str::random(64);
                $invitation = RestaurantInvitation::query()->create([
                    'user_id' => $user->getKey(),
                    'restaurant_id' => $restaurant->getKey(),
                    'email' => $validated['email'],
                    'token_hash' => hash('sha256', $plainToken),
                    'expires_at' => now()->addHours(config('agent.restaurant_invitation_expire_hours')),
                ]);

                return [$restaurant, $invitation, $plainToken];
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        $invitation->setRelation('restaurant', $restaurant);
        $mailSent = true;

        try {
            Mail::to($invitation->email)->send(new RestaurantInvitationMail(
                $invitation,
                $plainToken,
                trim($validated['firstname'].' '.$validated['lastname']),
            ));
        } catch (Throwable $exception) {
            $mailSent = false;
            Log::error('Restaurant invitation email could not be sent.', [
                'restaurant_id' => $restaurant->getKey(),
                'invitation_id' => $invitation->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }

        app(RestaurantApplicationNotifier::class)->send(
            $restaurant,
            'Application submitted. Uploaded documents are pending verification; missing documents may be added from the restaurant account.',
        );

        return ['restaurant' => $restaurant, 'invitation' => $invitation, 'mail_sent' => $mailSent];
    }

    private function createPartnerUser(array $validated, Request $request): User
    {
        $attributes = [
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(64)),
        ];

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
        if (Schema::hasTable('roles') && Role::query()->where('name', 'partner')->where('guard_name', 'web')->exists()) {
            $user->assignRole('partner');
        }
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
