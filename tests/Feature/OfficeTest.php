<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_example()
    {
        Office::factory(3)->create();

        $response = $this->get('/api/offices');

        $response->assertStatus(200);
    }
}
