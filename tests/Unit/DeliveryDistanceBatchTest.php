<?php

namespace Tests\Unit;

use App\Model\DeliveryDistance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryDistanceBatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::setDefaultDriver('array');
        Cache::flush();
        config([
            'services.google.maps_key' => 'test-key',
            'services.google.distance_matrix_batch_size' => 25,
            'services.google.distance_matrix_max_batches' => 1,
            'services.google.distance_matrix_daily_element_limit' => 1000,
        ]);
    }

    public function test_it_fetches_multiple_routes_in_one_request(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/json*' => Http::response([
                'status' => 'OK',
                'rows' => [
                    [
                        'elements' => [[
                            'status' => 'OK',
                            'distance' => ['value' => 2450],
                            'duration' => ['value' => 610],
                        ]],
                    ],
                    [
                        'elements' => [[
                            'status' => 'OK',
                            'distance' => ['value' => 5100],
                            'duration' => ['value' => 901],
                        ]],
                    ],
                ],
            ]),
        ]);

        $origins = [
            10 => ['latitude' => 8.4801, 'longitude' => 124.6401],
            20 => ['latitude' => 8.4901, 'longitude' => 124.6501],
        ];
        $destination = ['latitude' => 8.4701, 'longitude' => 124.6301];

        $firstResult = DeliveryDistance::getBatchCoordinateComputations(
            $origins,
            $destination
        );
        $this->assertSame([
            10 => ['distance_km' => 2.45, 'duration_minutes' => 11],
            20 => ['distance_km' => 5.1, 'duration_minutes' => 16],
        ], $firstResult);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['origins']
                === '8.480100,124.640100|8.490100,124.650100'
                && $query['destinations'] === '8.470100,124.630100';
        });
    }

    public function test_it_never_exceeds_the_configured_batch_budget(): void
    {
        config(['services.google.distance_matrix_batch_size' => 2]);

        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/json*' => Http::response([
                'status' => 'OK',
                'rows' => [
                    [
                        'elements' => [[
                            'status' => 'OK',
                            'distance' => ['value' => 1000],
                            'duration' => ['value' => 300],
                        ]],
                    ],
                    [
                        'elements' => [[
                            'status' => 'OK',
                            'distance' => ['value' => 2000],
                            'duration' => ['value' => 600],
                        ]],
                    ],
                ],
            ]),
        ]);

        $result = DeliveryDistance::getBatchCoordinateComputations([
            1 => [8.41, 124.61],
            2 => [8.42, 124.62],
            3 => [8.43, 124.63],
        ], [8.4, 124.6]);

        $this->assertSame([1, 2], array_keys($result));
        Http::assertSentCount(1);
    }

    public function test_it_skips_google_when_the_daily_element_budget_is_exhausted(): void
    {
        config(['services.google.distance_matrix_daily_element_limit' => 1]);
        Http::fake();

        $result = DeliveryDistance::getBatchCoordinateComputations([
            1 => [8.41, 124.61],
            2 => [8.42, 124.62],
        ], [8.4, 124.6]);

        $this->assertSame([], $result);
        Http::assertNothingSent();
    }

    public function test_dashboard_computations_use_local_coordinates_without_google(): void
    {
        Http::fake();

        $result = DeliveryDistance::getDashboardCoordinateComputations([
            10 => ['latitude' => 8.4801, 'longitude' => 124.6401],
            20 => ['latitude' => 8.4901, 'longitude' => 124.6501],
        ], [
            'latitude' => 8.4701,
            'longitude' => 124.6301,
        ]);

        $this->assertSame([10, 20], array_keys($result));
        $this->assertArrayHasKey('distance_km', $result[10]);
        $this->assertArrayHasKey('duration_minutes', $result[10]);
        Http::assertNothingSent();
    }
}
