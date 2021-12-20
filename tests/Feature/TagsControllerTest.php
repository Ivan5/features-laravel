<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagsControllerTest extends TestCase
{
    /** @test */
    public function it_lists_tags()
    {
        $response = $response = $this->get('/api/tags');

        $tags = Tag::all()->count();

        $response->assertStatus(200);
        $this->assertCount($tags, $response->json('data'));
    }
}
