<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagsTest extends TestCase
{
    /** @test */
    public function itListsTags()
    {
        Tag::factory(3)->create();

        $response = $this->get('/api/tags');

        $response->assertStatus(200);
        
        $this->assertCount(3, $response->json('data'));
    }
}
