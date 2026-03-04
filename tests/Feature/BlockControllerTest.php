<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_block_another_user()
    {
        $user = User::factory()->create();
        $userToBlock = User::factory()->create();
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/v1/client/blocks', [
            'blocked_id' => $userToBlock->id
        ]);
        
        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'User blocked successfully'
        ]);
        
        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock->id
        ]);
    }

    public function test_user_cannot_block_themselves()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/v1/client/blocks', [
            'blocked_id' => $user->id
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'status' => false,
            'message' => 'You cannot block yourself'
        ]);
    }

    public function test_user_cannot_block_already_blocked_user()
    {
        $user = User::factory()->create();
        $userToBlock = User::factory()->create();
        
        // Create existing block
        Block::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock->id
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/v1/client/blocks', [
            'blocked_id' => $userToBlock->id
        ]);
        
        $response->assertStatus(409);
        $response->assertJson([
            'status' => false,
            'message' => 'User is already blocked'
        ]);
    }

    public function test_user_can_unblock_user()
    {
        $user = User::factory()->create();
        $userToBlock = User::factory()->create();
        
        $block = Block::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock->id
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->deleteJson("/api/v1/client/blocks/{$userToBlock->id}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'User unblocked successfully'
        ]);
        
        $this->assertDatabaseMissing('user_blocks', [
            'id' => $block->id
        ]);
    }

    public function test_user_can_list_blocks()
    {
        $user = User::factory()->create();
        $userToBlock1 = User::factory()->create();
        $userToBlock2 = User::factory()->create();
        
        Block::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock1->id
        ]);
        
        Block::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock2->id
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->getJson('/api/v1/client/blocks');
        
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Blocked users retrieved successfully'
        ]);
        
        $this->assertCount(2, $response->json('data.blocks'));
    }

    public function test_user_can_check_block_status()
    {
        $user = User::factory()->create();
        $userToBlock = User::factory()->create();
        
        Sanctum::actingAs($user);
        
        // Check status when not blocked
        $response = $this->getJson("/api/v1/client/blocks/{$userToBlock->id}/status");
        
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'is_blocked' => false
            ]
        ]);
        
        // Block the user
        Block::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userToBlock->id
        ]);
        
        // Check status when blocked
        $response = $this->getJson("/api/v1/client/blocks/{$userToBlock->id}/status");
        
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'is_blocked' => true
            ]
        ]);
    }
}
