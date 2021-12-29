<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\OfficeFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{

    //use RefreshDatabase;
    use DatabaseTransactions;
    /** @test
     */
    public function can_take_office()
    {
        Office::factory(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }


    /** @test */
    public function it_only_list_office_not_hidden_and_approved()
    {
        Office::factory(3)->create();

        Office::factory()->create(['hidden' => true]);

        Office::factory()->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_filters_by_host()
    {
        Office::factory(3)->create();

        $host = User::factory()->create();

        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?user_id='.$host->id);


        $response->assertOk();
        $response->assertJsonCount(1,'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    /** @test */
    public function it_filter_by_user_id()
    {
        Office::factory(3)->create();
        $user = User::factory()->create();
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?visitor_id='.$user->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id,$response->json('data')[0]['id']);
    }

    /** @test */
    public function it_includes_images_tags_and_user()
    {
        $user = User::factory()->create();
        Office::factory()->for($user)->hasTags(1)->hasImages(1)->create();

        $response = $this->get('/api/offices');

        $response->assertOk()
            ->assertJsonCount(1, 'data.0.tags')
            ->assertJsonCount(1, 'data.0.images')
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    /** @test */
    public function it_returns_the_number_of_active_reservations()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->for($user)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->for($user)->create(['status' => Reservation::STATUS_CANCEL]);

        $response = $this->get('/api/offices');

        $response->assertOk();
        $this->assertEquals(1,$response->json('data')[0]['reservations_count']);
        
    }

    /** @test */
    public function can_create_an_office()
    {
        $user = User::factory()->createQuietly();
        $tag = Tag::factory()->create();
        $tag1 = Tag::factory()->create();


        $this->actingAs($user);

        $response = $this->post('/api/offices',[
            'title' => 'This is a title',
            'description' => 'This is a description',
            'lat' => '39.54646464568',
            'lng' => '-8.18456465465',
            'address_line1' => 'address',
            'price_per_day' => 10_000,
            'monthly_discount' => 65,
            'tags' => [
                $tag->id, $tag1->id
            ]
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title','This is a title');
    }
    
}
