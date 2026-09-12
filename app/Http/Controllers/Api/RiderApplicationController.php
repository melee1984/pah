<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRiderApplicationRequest;
use App\Http\Requests\SubmitRiderApplicationRequest;
use App\Http\Requests\UpdateRiderEmergencyContactRequest;
use App\Http\Requests\UpdateRiderPayoutAccountRequest;
use App\Http\Requests\UpdateRiderPersonalRequest;
use App\Http\Requests\UpdateRiderVehicleRequest;
use App\Http\Requests\UploadRiderApplicationDocumentRequest;
use App\RiderApplication;
use App\RiderApplicationDocument;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RiderApplicationController extends Controller
{
    public function create(CreateRiderApplicationRequest $request): JsonResponse
    {
        [$plainToken, $tokenHash] = $this->newAccessToken();

        $application = RiderApplication::create([
            'reference' => (string) Str::uuid(),
            'access_token_hash' => $tokenHash,
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'status' => RiderApplication::STATUS_DRAFT,
        ]);

        return response()->json([
            'message' => 'Rider application draft created.',
            'application' => $this->applicationData($application),
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function store(SubmitRiderApplicationRequest $request): JsonResponse
    {
        $reference = (string) Str::uuid();
        $directory = "rider-applications/{$reference}";
        $validated = $request->validated();
        [$plainToken, $tokenHash] = $this->newAccessToken();

        try {
            $application = DB::transaction(function () use ($request, $validated, $reference, $directory, $tokenHash) {
                $application = RiderApplication::create([
                    'reference' => $reference,
                    'access_token_hash' => $tokenHash,
                    'full_name' => $validated['full_name'],
                    'email' => Str::lower($validated['email']),
                    'mobile' => $validated['mobile'],
                    'password' => $validated['password'],
                    'birth_date' => $validated['birth_date'],
                    'home_address' => $validated['home_address'],
                    'profile_photo_path' => $this->storeFile($request->file('profile_photo'), $directory),
                    'emergency_contact_name' => $validated['emergency_contact_name'],
                    'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
                    'emergency_contact_mobile' => $validated['emergency_contact_mobile'],
                    'government_id_path' => $this->storeFile($request->file('government_id'), $directory),
                    'drivers_license_path' => $this->storeFile($request->file('drivers_license'), $directory),
                    'vehicle_registration_path' => $this->storeFile($request->file('vehicle_registration'), $directory),
                    'vehicle_type' => $validated['vehicle_type'],
                    'vehicle_make_model' => $validated['vehicle_make_model'],
                    'vehicle_plate_number' => Str::upper($validated['vehicle_plate_number']),
                    'vehicle_color' => $validated['vehicle_color'],
                    'payout_method' => $validated['payout_method'],
                    'payout_account_name' => $validated['payout_account_name'],
                    'payout_account_number' => $validated['payout_account_number'],
                    'status' => RiderApplication::STATUS_PENDING,
                    'submitted_at' => now(),
                ]);

                $this->createCompatibilityDocuments($application, $request);

                return $application;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->deleteDirectory($directory);

            throw $exception;
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your rider application has been submitted and is now under review.',
            'application' => [
                'id' => $application->reference,
                'reference' => $application->reference,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at->toISOString(),
                'progress' => [
                    'application_submitted' => 'complete',
                    'identity_and_documents_review' => 'pending',
                    'rider_account_activated' => 'pending',
                ],
            ],
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function current(Request $request): JsonResponse
    {
        return response()->json([
            'application' => $this->applicationData($this->applicationForRequest($request)),
        ]);
    }

    public function show(Request $request, string $application): JsonResponse
    {
        return response()->json([
            'application' => $this->applicationData(
                $this->applicationForRequest($request, $application),
            ),
        ]);
    }

    public function updatePersonal(
        UpdateRiderPersonalRequest $request,
        string $application,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $riderApplication->update($request->validated());

        return $this->updatedResponse($riderApplication, 'Personal details saved.');
    }

    public function updateEmergencyContact(
        UpdateRiderEmergencyContactRequest $request,
        string $application,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $validated = $request->validated();
        $riderApplication->update([
            'emergency_contact_name' => $validated['name'],
            'emergency_contact_relationship' => $validated['relationship'],
            'emergency_contact_mobile' => $validated['mobile'],
        ]);

        return $this->updatedResponse($riderApplication, 'Emergency contact saved.');
    }

    public function updateVehicle(
        UpdateRiderVehicleRequest $request,
        string $application,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $validated = $request->validated();
        $riderApplication->update([
            'vehicle_type' => $validated['type'],
            'vehicle_make_model' => $validated['make_model'],
            'vehicle_plate_number' => Str::upper($validated['plate_number']),
            'vehicle_color' => $validated['color'],
        ]);

        return $this->updatedResponse($riderApplication, 'Vehicle details saved.');
    }

    public function updatePayoutAccount(
        UpdateRiderPayoutAccountRequest $request,
        string $application,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $validated = $request->validated();
        $riderApplication->update([
            'payout_method' => $validated['method'],
            'payout_account_name' => $validated['account_name'],
            'payout_account_number' => $validated['account_number'],
        ]);

        return $this->updatedResponse($riderApplication, 'Payout account saved.');
    }

    public function uploadDocument(
        UploadRiderApplicationDocumentRequest $request,
        string $application,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $validated = $request->validated();
        $file = $request->file('file');
        $directory = "rider-applications/{$riderApplication->reference}";
        $path = $this->storeFile($file, $directory);
        $existing = $riderApplication->documents()
            ->where('type', $validated['type'])
            ->first();

        try {
            $document = DB::transaction(function () use ($riderApplication, $validated, $file, $path) {
                return $riderApplication->documents()->updateOrCreate(
                    ['type' => $validated['type']],
                    [
                        'reference' => (string) Str::uuid(),
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ],
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($existing && $existing->path !== $path) {
            Storage::disk('local')->delete($existing->path);
        }

        return response()->json([
            'message' => 'Document saved.',
            'document' => $this->documentData($document),
        ], $existing ? 200 : 201);
    }

    public function deleteDocument(
        Request $request,
        string $application,
        string $document,
    ): JsonResponse {
        $riderApplication = $this->editableApplication($request, $application);
        $riderDocument = $riderApplication->documents()
            ->where('reference', $document)
            ->firstOrFail();

        $path = $riderDocument->path;
        $riderDocument->delete();
        Storage::disk('local')->delete($path);

        return response()->json([
            'message' => 'Document removed.',
        ]);
    }

    public function submit(Request $request, string $application): JsonResponse
    {
        $riderApplication = $this->applicationForRequest($request, $application);

        if ($riderApplication->status !== RiderApplication::STATUS_DRAFT) {
            return $this->statusConflict('Only draft applications can be submitted.');
        }

        return $this->submitForReview($riderApplication);
    }

    public function resubmit(Request $request, string $application): JsonResponse
    {
        $riderApplication = $this->applicationForRequest($request, $application);

        if ($riderApplication->status !== RiderApplication::STATUS_REVISIONS_REQUIRED) {
            return $this->statusConflict('Only applications requiring revisions can be resubmitted.');
        }

        return $this->submitForReview($riderApplication);
    }

    public function status(Request $request, string $application): JsonResponse
    {
        $riderApplication = $this->applicationForRequest($request, $application);

        return response()->json([
            'application' => [
                'id' => $riderApplication->reference,
                'status' => $riderApplication->status,
                'review_notes' => $riderApplication->review_notes,
                'submitted_at' => $riderApplication->submitted_at?->toISOString(),
                'updated_at' => $riderApplication->updated_at?->toISOString(),
                'progress' => $this->progressData($riderApplication),
            ],
        ]);
    }

    public function sendActivation(Request $request, string $application): JsonResponse
    {
        $riderApplication = $this->applicationForRequest($request, $application);

        if ($riderApplication->status !== RiderApplication::STATUS_APPROVED) {
            return $this->statusConflict('Only approved rider applications can be activated.');
        }

        $code = (string) random_int(100000, 999999);
        $reference = (string) Str::uuid();
        DB::table('rider_api_otp_challenges')->insert([
            'reference' => $reference,
            'purpose' => 'activation',
            'channel' => 'email',
            'destination' => $riderApplication->email,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::raw(
            "Your Pahatud Rider activation code is {$code}. It expires in 10 minutes.",
            fn ($message) => $message
                ->to($riderApplication->email)
                ->subject('Activate your Pahatud Rider account'),
        );

        return response()->json([
            'message' => 'Rider activation code sent.',
            'challenge_id' => $reference,
            'expires_in_seconds' => 600,
        ], 202);
    }

    public function confirmActivation(Request $request, string $application): JsonResponse
    {
        $validated = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required', 'digits:6'],
        ]);
        $riderApplication = $this->applicationForRequest($request, $application);

        if ($riderApplication->status !== RiderApplication::STATUS_APPROVED) {
            return $this->statusConflict('Only approved rider applications can be activated.');
        }

        $challenge = DB::table('rider_api_otp_challenges')
            ->where('reference', $validated['challenge_id'])
            ->where('purpose', 'activation')
            ->where('destination', $riderApplication->email)
            ->first();

        if (
            ! $challenge
            || $challenge->verified_at
            || now()->greaterThan($challenge->expires_at)
            || ! hash_equals($challenge->code_hash, hash('sha256', $validated['code']))
        ) {
            return response()->json(['message' => 'The rider activation code is invalid or expired.'], 422);
        }

        $user = DB::transaction(function () use ($riderApplication, $challenge) {
            $user = User::query()->where('email', $riderApplication->email)->first();

            if (! $user) {
                [$firstName, $lastName] = $this->splitName($riderApplication->full_name);
                $attributes = [
                    'email' => $riderApplication->email,
                    'password' => $riderApplication->getRawOriginal('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('users', 'name')) {
                    $attributes['name'] = $riderApplication->full_name;
                }
                if (Schema::hasColumn('users', 'firstname')) {
                    $attributes['firstname'] = $firstName;
                }
                if (Schema::hasColumn('users', 'lastname')) {
                    $attributes['lastname'] = $lastName;
                }
                if (Schema::hasColumn('users', 'mobile')) {
                    $attributes['mobile'] = $riderApplication->mobile;
                }
                if (Schema::hasColumn('users', 'api_token')) {
                    $attributes['api_token'] = Str::random(80);
                }

                $user = User::query()->findOrFail(DB::table('users')->insertGetId($attributes));
            }

            DB::table('rider')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'name' => $riderApplication->full_name,
                    'date_join' => now(),
                    'mobile' => $riderApplication->mobile,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            DB::table('rider_api_otp_challenges')->where('id', $challenge->id)->update([
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Pahatud Rider account activated. You can now sign in.',
            'account_status' => 'approved',
            'user_id' => (string) $user->id,
        ], 201);
    }

    private function submitForReview(RiderApplication $application): JsonResponse
    {
        $this->validateApplicationIsComplete($application);

        $application->update([
            'status' => RiderApplication::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Your rider application has been submitted and is now under review.',
            'application' => $this->applicationData($application->fresh()),
        ]);
    }

    private function validateApplicationIsComplete(RiderApplication $application): void
    {
        $requiredFields = [
            'full_name',
            'mobile',
            'birth_date',
            'home_address',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_mobile',
            'vehicle_type',
            'vehicle_make_model',
            'vehicle_plate_number',
            'vehicle_color',
            'payout_method',
            'payout_account_name',
            'payout_account_number',
        ];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (blank($application->getAttribute($field))) {
                $errors[$field][] = 'This field must be completed before submission.';
            }
        }

        $uploadedTypes = $application->documents()->pluck('type');

        foreach (RiderApplicationDocument::TYPES as $type) {
            if (! $uploadedTypes->contains($type)) {
                $errors["documents.{$type}"][] = 'This document must be uploaded before submission.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function applicationForRequest(
        Request $request,
        ?string $reference = null,
    ): RiderApplication {
        /** @var RiderApplication $application */
        $application = $request->attributes->get('rider_application');

        abort_if(
            $reference !== null && ! hash_equals($application->reference, $reference),
            404,
        );

        return $application;
    }

    private function editableApplication(Request $request, string $reference): RiderApplication
    {
        $application = $this->applicationForRequest($request, $reference);

        abort_unless(
            in_array($application->status, [
                RiderApplication::STATUS_DRAFT,
                RiderApplication::STATUS_REVISIONS_REQUIRED,
            ], true),
            409,
            'This application cannot be edited in its current status.',
        );

        return $application;
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationData(RiderApplication $application): array
    {
        $application->loadMissing('documents');

        return [
            'id' => $application->reference,
            'status' => $application->status,
            'email' => $application->email,
            'personal' => [
                'full_name' => $application->full_name,
                'mobile' => $application->mobile,
                'birth_date' => $application->birth_date?->format('Y-m-d'),
                'home_address' => $application->home_address,
            ],
            'emergency_contact' => [
                'name' => $application->emergency_contact_name,
                'relationship' => $application->emergency_contact_relationship,
                'mobile' => $application->emergency_contact_mobile,
            ],
            'vehicle' => [
                'type' => $application->vehicle_type,
                'make_model' => $application->vehicle_make_model,
                'plate_number' => $application->vehicle_plate_number,
                'color' => $application->vehicle_color,
            ],
            'payout_account' => [
                'method' => $application->payout_method,
                'account_name' => $application->payout_account_name,
                'masked_account_number' => $this->maskAccountNumber($application->payout_account_number),
            ],
            'documents' => $application->documents
                ->map(fn (RiderApplicationDocument $document) => $this->documentData($document))
                ->values(),
            'review_notes' => $application->review_notes,
            'submitted_at' => $application->submitted_at?->toISOString(),
            'updated_at' => $application->updated_at?->toISOString(),
            'progress' => $this->progressData($application),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentData(RiderApplicationDocument $document): array
    {
        return [
            'id' => $document->reference,
            'type' => $document->type,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'uploaded_at' => $document->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function progressData(RiderApplication $application): array
    {
        return [
            'application_submitted' => $application->submitted_at ? 'complete' : 'pending',
            'identity_and_documents_review' => match ($application->status) {
                RiderApplication::STATUS_APPROVED => 'complete',
                RiderApplication::STATUS_REVISIONS_REQUIRED,
                RiderApplication::STATUS_REJECTED,
                RiderApplication::STATUS_SUSPENDED,
                RiderApplication::STATUS_EXPIRED_DOCUMENTS => $application->status,
                default => 'pending',
            },
            'rider_account_activated' => 'pending',
        ];
    }

    private function updatedResponse(
        RiderApplication $application,
        string $message,
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'application' => $this->applicationData($application->fresh()),
        ]);
    }

    private function statusConflict(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 409);
    }

    /**
     * @return array{string, string}
     */
    private function newAccessToken(): array
    {
        $plainToken = Str::random(80);

        return [$plainToken, hash('sha256', $plainToken)];
    }

    private function storeFile(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'local');

        if (! $path) {
            throw new RuntimeException('The rider application document could not be stored.');
        }

        return $path;
    }

    private function createCompatibilityDocuments(
        RiderApplication $application,
        SubmitRiderApplicationRequest $request,
    ): void {
        $files = [
            'profile_photo' => [$request->file('profile_photo'), $application->profile_photo_path],
            'government_id' => [$request->file('government_id'), $application->government_id_path],
            'drivers_license' => [$request->file('drivers_license'), $application->drivers_license_path],
            'vehicle_registration' => [$request->file('vehicle_registration'), $application->vehicle_registration_path],
        ];

        foreach ($files as $type => [$file, $path]) {
            $application->documents()->create([
                'reference' => (string) Str::uuid(),
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        $visibleLength = min(4, strlen($accountNumber));

        return str_repeat('•', max(0, strlen($accountNumber) - $visibleLength))
            .substr($accountNumber, -$visibleLength);
    }

    /**
     * @return array{string, string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
