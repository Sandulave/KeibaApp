<?php

namespace Tests\Feature;

use Tests\TestCase;

class SiteLogoTest extends TestCase
{
    public function test_summer_site_uses_summer_icon_logo(): void
    {
        config([
            'domain.site.type' => 'summer',
            'domain.site.label' => '夏競馬バトル',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('summer_icon.png');
        $response->assertSee('favicon.svg');
        $response->assertDontSee('login_header.png');
    }

    public function test_g1_site_keeps_image_logo(): void
    {
        config([
            'domain.site.type' => 'g1',
            'domain.site.label' => '初心者G1馬券バトル',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('login_header.png');
    }
}
