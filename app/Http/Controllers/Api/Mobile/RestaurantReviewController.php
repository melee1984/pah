<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\RestaurantReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:partners,id'],
            'partner_location_id' => [
                'nullable',
                'integer',
                Rule::exists('partner_location', 'id')->where(
                    fn ($query) => $query->where('partner_id', $request->integer('restaurant_id'))
                ),
            ],
        ]);

        $query = RestaurantReview::query()
            ->with('user')
            ->where('partner_id', $validated['restaurant_id'])
            ->where('active', true);

        if (! empty($validated['partner_location_id'])) {
            $query->where('partner_location_id', $validated['partner_location_id']);
        }

        $reviews = $query->latest()->limit(50)->get();

        return response()->json([
            'status' => 1,
            'data' => [
                'reviews' => $reviews->map(fn (RestaurantReview $review) => $this->reviewData($review))->values(),
                'summary' => [
                    'average_rating' => round((float) $reviews->avg('rating'), 2),
                    'rating_count' => $reviews->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:partners,id'],
            'partner_location_id' => [
                'nullable',
                'integer',
                Rule::exists('partner_location', 'id')->where(
                    fn ($query) => $query->where('partner_id', $request->integer('restaurant_id'))
                ),
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $review = RestaurantReview::updateOrCreate(
            [
                'user_id' => $request->user('api')->id,
                'partner_id' => $validated['restaurant_id'],
                'partner_location_id' => $validated['partner_location_id'] ?? null,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => trim($validated['comment'] ?? ''),
                'active' => true,
            ]
        );
        $review->load('user');

        return response()->json([
            'status' => 1,
            'message' => $review->wasRecentlyCreated
                ? 'Review submitted successfully.'
                : 'Review updated successfully.',
            'data' => ['review' => $this->reviewData($review)],
        ], $review->wasRecentlyCreated ? 201 : 200);
    }

    private function reviewData(RestaurantReview $review): array
    {
        $name = trim(collect([
            $review->user?->firstname,
            $review->user?->lastname,
        ])->filter()->implode(' '));

        return [
            'id' => $review->id,
            'reviewer_name' => $name ?: ($review->user?->name ?: 'Pahatud customer'),
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }
}
