<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionKeepAliveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_refreshes_csrf_token_for_authenticated_users()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/session/keepalive');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'csrf_token',
                'session_id',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('csrf_token'));
        $this->assertNotEmpty($response->json('session_id'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/session/keepalive');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_regenerates_session_token()
    {
        $user = User::factory()->create();
        
        $firstResponse = $this->actingAs($user)
            ->postJson('/session/keepalive');
        
        $firstToken = $firstResponse->json('csrf_token');
        
        $secondResponse = $this->actingAs($user)
            ->postJson('/session/keepalive');
        
        $secondToken = $secondResponse->json('csrf_token');

        // Tokens should be different (regenerated)
        $this->assertNotEquals($firstToken, $secondToken);
    }
}

