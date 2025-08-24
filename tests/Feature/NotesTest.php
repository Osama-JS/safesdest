<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_their_notes()
    {
        // Create some notes for the user
        Note::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Create notes for another user (should not be visible)
        $otherUser = User::factory()->create();
        Note::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/admin/notes');

        $response->assertStatus(200)
            ->assertJson(['status' => 1])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_create_a_note()
    {
        $noteData = [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/admin/notes', $noteData);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('notes', [
            'user_id' => $this->user->id,
            'title' => $noteData['title'],
            'content' => $noteData['content']
        ]);
    }

    /** @test */
    public function user_can_update_their_note()
    {
        $note = Note::factory()->create(['user_id' => $this->user->id]);
        
        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/admin/notes/{$note->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => $updateData['title'],
            'content' => $updateData['content']
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_note()
    {
        $otherUser = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $otherUser->id]);
        
        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/admin/notes/{$note->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_note()
    {
        $note = Note::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/admin/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_note()
    {
        $otherUser = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/admin/notes/{$note->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    /** @test */
    public function note_creation_requires_title_and_content()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/admin/notes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function note_update_requires_title_and_content()
    {
        $note = Note::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/admin/notes/{$note->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }
}
