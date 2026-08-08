<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_landpay_homepage_is_available(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Your payment plan,')
            ->assertSee('Client portal')
            ->assertSee('images/landpay-logo.png', escape: false);
    }
}