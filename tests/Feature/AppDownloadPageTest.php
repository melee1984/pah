<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppDownloadPageTest extends TestCase
{
    private const FOOD_PLAY_URL = 'https://play.google.com/store/apps/details?id=io.pahatud.user';
    private const MERCHANT_PLAY_URL = 'https://play.google.com/store/apps/details?id=io.pahatud.merchant';
    private const RIDER_PLAY_URL = 'https://play.google.com/store/apps/details?id=io.pahatud.rider.user';

    public function test_app_download_page_presents_all_verified_android_downloads(): void
    {
        $response = $this->get(route('apps.index'));

        $response
            ->assertOk()
            ->assertSee('<title>Download the Pahatud Android apps | PahatudFood</title>', false)
            ->assertSee('PahatudFood')
            ->assertSee(self::FOOD_PLAY_URL, false)
            ->assertSee('<img src="http://localhost/images/app-download/pahatud-food-qr.svg" alt="QR code linking to PahatudFood on Google Play" width="240" height="240">', false)
            ->assertSee('Pahatud Merchant')
            ->assertSee(self::MERCHANT_PLAY_URL, false)
            ->assertSee('images/app-download/pahatud-merchant-qr.svg', false)
            ->assertSee('Pahatud Rider')
            ->assertSee(self::RIDER_PLAY_URL, false)
            ->assertSee('images/app-download/pahatud-rider-qr.svg', false)
            ->assertDontSee('io.pahatud.com')
            ->assertDontSee('Coming soon')
            ->assertDontSee('app-download-qr-logo', false)
            ->assertDontSee('App Store')
            ->assertDontSee('iOS');
    }

    public function test_each_qr_code_encodes_its_exact_google_play_url(): void
    {
        $qrCodes = [
            'pahatud-food-qr.svg' => self::FOOD_PLAY_URL,
            'pahatud-merchant-qr.svg' => self::MERCHANT_PLAY_URL,
            'pahatud-rider-qr.svg' => self::RIDER_PLAY_URL,
        ];

        foreach ($qrCodes as $filename => $playUrl) {
            $qrCode = file_get_contents(public_path('images/app-download/'.$filename));

            $this->assertIsString($qrCode);
            $this->assertStringContainsString('<desc>'.$playUrl.'</desc>', $qrCode);
        }
    }

    public function test_every_visible_google_play_link_is_a_direct_download_target(): void
    {
        $html = $this->get(route('apps.index'))->getContent();

        preg_match_all('/<a\b[^>]*href="https:\/\/play\.google\.com\/store\/apps\/details\?id=[^"]+"[^>]*>/', $html, $matches);

        $this->assertCount(5, $matches[0]);

        foreach ($matches[0] as $anchor) {
            $this->assertStringContainsString('data-direct-download', $anchor);
            $this->assertStringContainsString('target="_blank"', $anchor);
            $this->assertStringContainsString('rel="noopener noreferrer"', $anchor);
        }
    }
}
