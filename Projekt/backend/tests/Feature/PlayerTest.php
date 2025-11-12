<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Players;

class PlayerTest extends TestCase
{
    /**
     * A basic test example.
     */
    // public function test_the_application_returns_a_successful_response(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    use RefreshDatabase;
    protected $connection = 'mysql_testing';

    /**
     * Test retrieving all players.
     *
     * @return void
     */
    public function testGetAllPlayers()
    {
        Players::factory()->count(3)->create();

        $response = $this->get('/api/players');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test retrieving a single player.
     *
     * @return void
     */
    public function testGetSinglePlayer()
    {
        $player = Players::factory()->create();

        $response = $this->get('/api/players/' . $player->id);

        $response->assertStatus(200)
            ->assertJson([
                'name' => $player->name,
                'age' => $player->age,
                'points' => $player->points,
                'address' => $player->address
            ]);
    }

    /**
     * Test creating a new player.
     *
     * @return void
     */
    public function testCreatePlayer()
    {
        $data = [
            'name' => 'John Doe',
            'age' => 25,
            'points' => 100,
            'address' => '123 Main St'
        ];

        $response = $this->post('/api/players', $data);

        $response->assertStatus(200)
            ->assertExactJson(['Player added']);
    }

    /**
     * Test updating a player.
     *
     * @return void
     */
    public function testUpdatePlayer()
    {
        $player = Players::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'age' => 30,
            'points' => 200,
            'address' => '456 New St'
        ];

        $response = $this->put('/api/players/' . $player->id, $data);

        $response->assertStatus(200)
            ->assertExactJson(['Player updated']);
    }

    /**
     * Test deleting a player.
     *
     * @return void
     */
    public function testDeletePlayer()
    {
        $player = Players::factory()->create();

        $response = $this->delete('/api/players/' . $player->id);

        $response->assertStatus(200)
            ->assertExactJson(['Player deleted']);
    }
}
