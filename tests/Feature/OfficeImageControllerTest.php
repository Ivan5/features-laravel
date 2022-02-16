<?php

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

it('can store a images for office', function(){

    Storage::fake('public');

    $user = User::factory()->create();
    $office = Office::factory()->for($user)->create();

    $this->actingAs($user);

    $response = $this->post('/api/offices/'.$office->id.'/images', [
        'image' => UploadedFile::fake()->image('image.jpg')
    ]);

    $response->assertCreated();

    Storage::disk('public')->assertExists(
        $response->json('data.path')
    );
});

it('can detele a image', function() {
    Storage::fake('public')->put('/office_image.jpg', 'empty');

    $user = User::factory()->create();
    $office = Office::factory()->for($user)->create();

    $office->images()->create([
        'path' => 'image.jpg'
    ]);

    $image = $office->images()->create([
        'path' => 'office_image.jpg'
    ]);

    $this->actingAs($user);

    $response = $this->delete("/api/offices/{$office->id}/images/{$image->id}");

    $this->assertModelMissing($image);

    Storage::disk('public')->assertMissing('office_image.jpg');

});

it('dosent delete the only image', function() {
    $user = User::factory()->create();
    $office = Office::factory()->for($user)->create();

    $image = $office->images()->create([
        'path' => 'office_image.jpg'
    ]);

    $this->actingAs($user);

    $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

    $response->assertUnprocessable();

});

it('dosent delete the featured image', function() {
    $user = User::factory()->create();
    $office = Office::factory()->for($user)->create();

    $image = $office->images()->create([
        'path' => 'office_image.jpg'
    ]);

    $office->update(['featured_image_id' => $image->id]);

    $this->actingAs($user);

    $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

    $response->assertUnprocessable();

});