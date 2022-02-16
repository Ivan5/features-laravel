<?php



use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

//uses(Tests\TestCase::class);
uses(DatabaseTransactions::class);

it('list reservation that belong to the user', function() {
    $user = User::factory()->create();

    Reservation::factory()->for($user)->count(2)->create();
    Reservation::factory()->count(3)->create();

    $this->actingAs($user);

    $response = $this->getJson('/api/reservations');

    $response->assertJsonCount(2, 'data');

});