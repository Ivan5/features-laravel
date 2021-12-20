<?php

use App\Models\Office;
use Illuminate\Foundation\Testing\DatabaseTransactions;

//uses(Tests\TestCase::class);
uses(DatabaseTransactions::class);

it('show the office', function() {
    $office = Office::factory()->create();

    $response = $this->get("/api/offices/{$office->id}");

    $response->assertOk();
});