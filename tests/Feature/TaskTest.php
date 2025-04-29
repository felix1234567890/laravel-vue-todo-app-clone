<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Task;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_task()
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'priority' => 'low',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Test Task']);
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
    }

    /** @test */
    public function it_can_get_all_tasks()
    {
        Task::factory()->count(3)->create(['priority' => 'medium']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_update_a_task()
    {
        $task = Task::factory()->create(['priority' => 'medium']);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task',
            'priority' => 'high',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Updated Task']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Task']);
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        $task = Task::factory()->create(['priority' => 'medium']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
