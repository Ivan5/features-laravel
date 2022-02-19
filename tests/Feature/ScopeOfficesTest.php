<?php

use App\Models\Office;
use Illuminate\Foundation\Testing\DatabaseTransactions;

//uses(Tests\TestCase::class);
//uses(DatabaseTransactions::class);
uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

it('orders by distance when coordinates are provided', function () {

    $office1 = Office::factory()->create([
        'lat' => '45.643914651254896',
        'lng' => '13.779026647679379',
        'title' => 'Trieste'
    ]);

    $office2 = Office::factory()->create([
        'lat' => '45.55693195587672',
        'lng' => '11.53683638978917',
        'title' => 'Vicenza'
    ]);

    $response = $this->get('/api/offices?lat=45.43866625142615&lng=12.349699713069079');

    $response->assertOk();

    $this->assertEquals('Vicenza', $response->json('data')[0]['title']);
    $this->assertEquals('Trieste', $response->json('data')[1]['title']);
});

it('no orders by distance when coordinates are not provided', function() {
    $office1 = Office::factory()->create([
        'lat' => '45.643914651254896',
        'lng' => '13.779026647679379',
        'title' => 'Trieste'
    ]);

    $office2 = Office::factory()->create([
        'lat' => '45.55693195587672',
        'lng' => '11.53683638978917',
        'title' => 'Vicenza'
    ]);

    $response = $this->get('/api/offices');

    $response->assertOk();

    $this->assertEquals('Trieste', $response->json('data')[0]['title']);
    $this->assertEquals('Vicenza', $response->json('data')[1]['title']);
});
