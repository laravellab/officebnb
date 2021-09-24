<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;


    /** @test */
    public function it_PaginatesAllOffices()
    {
        Office::factory(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    /** @test */
    public function itListsOnlyApprovedOffices()
    {
        Office::factory(3)->create();
        Office::factory(1)->create(['hidden' => true]);
        Office::factory(1)->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        $response->assertOk()->assertJsonCount(3, 'data');
    }


    /** @test */
    public function itListsOfficeByHostId()
    {
        Office::factory(3)->create();

        $host = User::factory()->create();


        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?host_id=' . $host->id);

        $response->assertOk()->assertJsonCount(1, 'data');

        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }



    /** @test */
    public function itFiltersByUserId()
    {
        Office::factory(3)->create();

        $user = User::factory()->create();


        $office = Office::factory()->create();


        Reservation::factory()->for(Office::factory())->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?user_id=' . $user->id);


        $response->assertOk()->assertJsonCount(1, 'data');

        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }


    /** @test */
    public function itIncludesImagesTagsAndUser()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->create();

        $tag = Tag::factory()->create();

        $image = Image::factory()->create([
            'resource_id' => $office->id,
            'resource_type' => \get_class($office),
        ]);

        $office->tags()->attach($tag);

        $response = $this->get('/api/offices');

        $response->assertOk();

        $this->assertIsArray($response->json('data')[0]['tags']);
        $this->assertCount(1, $response->json('data')[0]['tags']);
        $this->assertIsArray($response->json('data')[0]['images']);
        $this->assertCount(1, $response->json('data')[0]['images']);
        $this->assertEquals($user->id, $response->json('data')[0]['user']['id']);
    }


    /** @test */
    public function itReturnsTheNumberOfActiveReservations()
    {
        $office = Office::factory()->create();


        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get('/api/offices');

        $response->assertOk();


        $this->assertEquals(1, $response->json('data')[0]['reservations_count']);
    }


    /** @test */
    public function itOrdersByDistanceWhenCoordinatesAreProvided()
    {
        // 38.720661384644046
        // -9.16044783453807

        $office1 = Office::factory()->create([
            'lat' => '39.46082812765714',
            'lng' => '-8.9583096270969',
            'title' => "Turquel"
        ]);

        $office2 = Office::factory()->create([
            'lat' => '39.09115425741275',
            'lng' => '-9.272265851738432',
            'title' => "Torres Vedras"
        ]);

        $response = $this->get('/api/offices?lat=38.720661384644046&lng=-9.16044783453807');

        $response->assertOk();

        $this->assertEquals('Torres Vedras', $response->json('data')[0]['title']);
        $this->assertEquals('Turquel', $response->json('data')[1]['title']);

        $response = $this->get('/api/offices');

        $response->assertOk();

        $this->assertEquals('Turquel', $response->json('data')[0]['title']);
        $this->assertEquals('Torres Vedras', $response->json('data')[1]['title']);
    }


    /** @test */
    public function itShowsTheOffice()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->create();

        $tag = Tag::factory()->create();

        $image = Image::factory()->create([
            'resource_id' => $office->id,
            'resource_type' => \get_class($office),
        ]);

        $office->tags()->attach($tag);

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);


        $response = $this->get('/api/offices/' . $office->id);

        $response->assertOk();

        $this->assertEquals(1, $response->json('data')['reservations_count']);
        $this->assertIsArray($response->json('data')['tags']);
        $this->assertCount(1, $response->json('data')['tags']);
        $this->assertIsArray($response->json('data')['images']);
        $this->assertCount(1, $response->json('data')['images']);
        $this->assertEquals($user->id, $response->json('data')['user']['id']);
    }
}
