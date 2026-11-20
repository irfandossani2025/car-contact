<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_privacy_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('Privacy');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy')->assertSee('support@techittechnologies.com');
    }
}
