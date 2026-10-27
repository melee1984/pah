<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    public function test_public_page_has_search_and_social_metadata(): void
    {
        $response = $this->get('/about-us');

        $response
            ->assertOk()
            ->assertSee('<title>About us | PahatudFood</title>', false)
            ->assertSee('<meta name="description" content="Learn how Pahatud connects customers across the Philippines with local restaurants, merchants, and reliable delivery while helping neighborhood businesses grow.">', false)
            ->assertSee('<link rel="canonical" href="http://localhost/about-us">', false)
            ->assertSee('<meta property="og:title" content="About us | PahatudFood">', false)
            ->assertSee('<meta property="og:image" content="http://localhost/images/pahatud-social.jpg">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('images/favicon-32x32.png', false)
            ->assertSee('<header class="landing-header">', false)
            ->assertSee('<img src="http://localhost/images/logo.jpg" alt="PahatudFood logo">', false)
            ->assertDontSee('<svg class="public-brand-mark"', false);
    }

    public function test_private_page_is_not_indexed(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_informational_pages_use_the_homepage_logo(): void
    {
        foreach (['/about-us', '/contact-us', '/privacy-policy', '/terms-of-use', '/fraud-prevention', '/payment-method'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<header class="landing-header">', false)
                ->assertSee('<img src="http://localhost/images/logo.jpg" alt="PahatudFood logo">', false)
                ->assertDontSee('<svg class="public-brand-mark"', false);
        }
    }
}
