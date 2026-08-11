<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_affiliate_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('affiliate.dashboard'));
    }
}
