<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Database connection is not available for the homepage feature test.');
        }

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }
}
