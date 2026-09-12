<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\RiderApplication;
use App\RiderApplicationDocument;
use App\Services\RiderApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function profile(Request $request): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $application = $this->application($request);

        return response()->json([
            'profile' => [
                ...$this->riders->riderData($request->user(), $rider),
                'home_address' => $application?->home_address,
                'birth_date' => $application?->birth_date?->format('Y-m-d'),
                'photo_configured' => (bool) $request->user()->avatar,
                'account_status' => 'approved',
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:250'],
            'mobile' => ['sometimes', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
            'home_address' => ['sometimes', 'string', 'max:1000'],
        ]);
        $rider = $this->riders->rider($request);
        $userUpdates = [];
        $riderUpdates = ['updated_at' => now()];

        if (array_key_exists('name', $validated)) {
            $riderUpdates['name'] = $validated['name'];
        }
        if (array_key_exists('mobile', $validated)) {
            $riderUpdates['mobile'] = $validated['mobile'];
            $userUpdates['mobile'] = $validated['mobile'];
        }

        DB::table('rider')->where('id', $rider->id)->update($riderUpdates);
        if ($userUpdates !== []) {
            $request->user()->forceFill($userUpdates)->save();
        }
        if (array_key_exists('home_address', $validated)) {
            $application = $this->application($request);
            abort_if(! $application, 409, 'No rider application is linked to this profile.');
            $application->update(['home_address' => $validated['home_address']]);
        }

        return $this->profile($request);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $rider = $this->riders->rider($request);
        $path = $request->file('photo')->store("rider-profile-photos/{$rider->id}", 'local');
        if (! $path) {
            throw new RuntimeException('The rider profile photo could not be stored.');
        }
        $oldPath = $request->user()->avatar;
        $request->user()->forceFill(['avatar' => $path])->save();
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Rider profile photo updated.',
            'photo_configured' => true,
        ]);
    }

    public function documents(Request $request): JsonResponse
    {
        $application = $this->application($request);

        return response()->json([
            'application_status' => $application?->status,
            'documents' => $application
                ? $application->documents->map(fn (RiderApplicationDocument $document) => [
                    'id' => $document->reference,
                    'type' => $document->type,
                    'original_name' => $document->original_name,
                    'uploaded_at' => $document->created_at?->toISOString(),
                    'status' => $application->status === RiderApplication::STATUS_EXPIRED_DOCUMENTS
                        ? 'expired'
                        : 'verified',
                ])->values()
                : [],
        ]);
    }

    public function updateDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(RiderApplicationDocument::TYPES)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        $application = $this->application($request);
        abort_if(! $application, 409, 'No rider application is linked to this profile.');
        $file = $request->file('file');
        $path = $file->store("rider-applications/{$application->reference}", 'local');
        if (! $path) {
            throw new RuntimeException('The rider document could not be stored.');
        }
        $existing = $application->documents()->where('type', $validated['type'])->first();

        try {
            $document = $application->documents()->updateOrCreate(
                ['type' => $validated['type']],
                [
                    'reference' => (string) Str::uuid(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                ],
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($existing && $existing->path !== $path) {
            Storage::disk('local')->delete($existing->path);
        }

        if ($application->status === RiderApplication::STATUS_EXPIRED_DOCUMENTS) {
            $application->update(['status' => RiderApplication::STATUS_PENDING]);
        }

        return response()->json([
            'message' => 'Rider document submitted for verification.',
            'document' => [
                'id' => $document->reference,
                'type' => $document->type,
                'status' => 'pending',
            ],
        ], 201);
    }

    public function vehicle(Request $request): JsonResponse
    {
        $application = $this->application($request);

        return response()->json([
            'vehicle' => $application ? [
                'type' => $application->vehicle_type,
                'make_model' => $application->vehicle_make_model,
                'plate_number' => $application->vehicle_plate_number,
                'color' => $application->vehicle_color,
            ] : null,
        ]);
    }

    public function updateVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'make_model' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:100'],
        ]);
        $application = $this->application($request);
        abort_if(! $application, 409, 'No rider application is linked to this profile.');
        $application->update([
            'vehicle_type' => $validated['type'],
            'vehicle_make_model' => $validated['make_model'],
            'vehicle_plate_number' => Str::upper($validated['plate_number']),
            'vehicle_color' => $validated['color'],
            'status' => RiderApplication::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Vehicle update submitted for verification.',
            'vehicle' => [
                ...$validated,
                'plate_number' => Str::upper($validated['plate_number']),
            ],
        ]);
    }

    public function performance(Request $request): JsonResponse
    {
        $riderId = $this->riders->rider($request)->id;
        $offers = DB::table('rider_api_offers')->where('rider_id', $riderId);
        $offerCount = (clone $offers)->count();
        $accepted = (clone $offers)->where('status', 'accepted')->count();
        $deliveries = DB::table('rider_api_deliveries')->where('rider_id', $riderId);
        $deliveryCount = (clone $deliveries)->count();
        $completed = (clone $deliveries)->where('current_state', 'delivered')->count();
        $cancelled = (clone $deliveries)->where('current_state', 'cancelled')->count();
        $feedback = DB::table('rider_api_feedback')->where('rider_id', $riderId);

        return response()->json([
            'performance' => [
                'rating' => round((float) (clone $feedback)->avg('rating'), 2),
                'acceptance_rate' => $this->percentage($accepted, $offerCount),
                'completion_rate' => $this->percentage($completed, $deliveryCount),
                'cancellation_rate' => $this->percentage($cancelled, $deliveryCount),
                'on_time_rate' => 0.0,
                'completed_deliveries' => $completed,
            ],
        ]);
    }

    public function feedback(Request $request): JsonResponse
    {
        $feedback = DB::table('rider_api_feedback')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'feedback' => collect($feedback->items())->map(fn (object $item) => [
                'id' => $item->reference,
                'delivery_id' => $item->delivery_reference,
                'rating' => (int) $item->rating,
                'comment' => $item->comment,
                'created_at' => $item->created_at,
            ]),
            'pagination' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'total' => $feedback->total(),
            ],
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        $settings = $this->settingsRecord($this->riders->rider($request)->id);

        return response()->json(['settings' => $this->settingsData($settings)]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => ['sometimes', Rule::in(['en', 'fil', 'ceb'])],
            'navigation_app' => ['sometimes', Rule::in(['system', 'google_maps', 'waze', 'apple_maps'])],
            'share_live_location' => ['sometimes', 'boolean'],
            'background_location' => ['sometimes', 'boolean'],
        ]);
        $riderId = $this->riders->rider($request)->id;
        $this->settingsRecord($riderId);
        DB::table('rider_api_settings')->where('rider_id', $riderId)->update([
            ...$validated,
            'updated_at' => now(),
        ]);

        return $this->settings($request);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'The current password is incorrect.'], 422);
        }

        $request->user()->forceFill(['password' => Hash::make($validated['password'])])->save();
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $request->user()->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'Rider password changed.']);
    }

    public function requestDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $rider = $this->riders->rider($request);
        $active = DB::table('rider_api_deliveries')
            ->where('rider_id', $rider->id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();
        abort_if($active, 409, 'Account deletion cannot be requested during an active delivery.');
        $reference = (string) Str::uuid();

        DB::table('rider_api_delete_requests')->insert([
            'reference' => $reference,
            'rider_id' => $rider->id,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Account deletion request submitted.',
            'delete_request' => ['id' => $reference, 'status' => 'pending'],
        ], 201);
    }

    public function deletionStatus(Request $request): JsonResponse
    {
        $record = DB::table('rider_api_delete_requests')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->whereNull('cancelled_at')
            ->latest('created_at')
            ->first();

        return response()->json([
            'delete_request' => $record ? [
                'id' => $record->reference,
                'status' => $record->status,
                'reason' => $record->reason,
                'created_at' => $record->created_at,
            ] : null,
        ]);
    }

    public function cancelDeletion(Request $request): JsonResponse
    {
        $record = DB::table('rider_api_delete_requests')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('status', 'pending')
            ->whereNull('cancelled_at')
            ->latest('created_at')
            ->first();
        abort_if(! $record, 404);
        DB::table('rider_api_delete_requests')->where('id', $record->id)->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Account deletion request cancelled.']);
    }

    private function application(Request $request): ?RiderApplication
    {
        return RiderApplication::query()
            ->where('email', $request->user()->email)
            ->with('documents')
            ->latest('id')
            ->first();
    }

    private function settingsRecord(int $riderId): object
    {
        DB::table('rider_api_settings')->insertOrIgnore([
            'rider_id' => $riderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rider_api_settings')->where('rider_id', $riderId)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsData(object $settings): array
    {
        return [
            'language' => $settings->language,
            'navigation_app' => $settings->navigation_app,
            'share_live_location' => (bool) $settings->share_live_location,
            'background_location' => (bool) $settings->background_location,
        ];
    }

    private function percentage(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 2) : 0.0;
    }
}
