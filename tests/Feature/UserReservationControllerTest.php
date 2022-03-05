<?php


use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

//uses(Tests\TestCase::class);
uses(DatabaseTransactions::class);
//uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

it('list reservation that belong to the user', function() {
    $user = User::factory()->create();

    Reservation::factory()->for($user)->count(2)->create();
    Reservation::factory()->count(3)->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/reservations');

    $response->assertJsonCount(2, 'data');

});

it('list reservation filter by date rage', function() {
    $user = User::factory()->create();

    $fromDate = '2021-03-03';
    $toDate = '2021-04-04';

    Reservation::factory()->for($user)->create([
        'start_date' => '2021-03-01',
        'end_date' => '2021-03-15'
    ]);

    Reservation::factory()->for($user)->create([
        'start_date' => '2021-03-25',
        'end_date' => '2021-04-15'
    ]);

    Reservation::factory()->for($user)->create([
        'start_date' => '2021-03-25',
        'end_date' => '2021-03-29'
    ]);

    //outside the date range
    Reservation::factory()->for($user)->create([
        'start_date' => '2021-02-25',
        'end_date' => '2021-03-01'
    ]);

    Reservation::factory()->for($user)->create([
        'start_date' => '2021-05-01',
        'end_date' => '2021-05-01'
    ]);


    $this->actingAs($user);

    $response = $this->getJson('/api/reservations?'.http_build_query([
            'from_date' => $fromDate,
            'to_date' => $toDate
        ]));

    $response->assertJsonCount(3, 'data');

});

it('filter results by status' , function(){
    $user = User::factory()->create();

    $reservation = Reservation::factory()->for($user)->create([
        'status' => Reservation::STATUS_ACTIVE
    ]);

    $reservation2 = Reservation::factory()->cancelled()->create();

    $this->actingAs($user);

    $response = $this->getJson('/api/reservations?'.http_build_query([
            'status' => Reservation::STATUS_ACTIVE
        ]));

    $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation->id);
});

it('filter results by office' , function(){
    $user = User::factory()->create();

    $office = Office::factory()->create();

    $reservation = Reservation::factory()->for($office)->for($user)->create();

    $reservation2 = Reservation::factory()->for($user)->create();

    $this->actingAs($user);

    $response = $this->getJson('/api/reservations?'.http_build_query([
            'office_id' => $office->id
        ]));

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $reservation->id);
});

it('makes reservations', function() {
    $user = User::factory()->create();

    $office = Office::factory()->create([
        'price_per_day' => 1_00,
        'monthly_discount' => 10
    ]);

    $this->actingAs($user);

    $response = $this->postJson('/api/reservations', [
        'office_id' => $office->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(40)
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.price', 3600)
        ->assertJsonPath('data.status', Reservation::STATUS_ACTIVE);
});

it('can not make reservation of non existing office', function() {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/reservations', [
        'office_id' => 87,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(41)
    ]);

    $response->assertUnprocessable();
});
