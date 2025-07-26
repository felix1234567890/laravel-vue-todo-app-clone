<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable logging during tests to avoid cluttering test output
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
    }

    // ========================================
    // TASK CREATION TESTS (POST /api/tasks)
    // ========================================

    /** @test */
    public function it_can_create_a_task_with_valid_data()
    {
        $taskData = [
            'title' => 'Complete project documentation',
            'priority' => 'high',
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'title',
                     'priority',
                     'created_at',
                     'updated_at'
                 ])
                 ->assertJsonFragment([
                     'title' => 'Complete project documentation',
                     'priority' => 'high'
                 ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete project documentation',
            'priority' => 'high'
        ]);
    }

    /** @test */
    public function it_stores_priority_in_lowercase()
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'priority' => 'HIGH',
        ]);

        $response->assertStatus(201);

        // Verify priority is stored in lowercase in database
        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'priority' => 'high'
        ]);

        // Verify response contains lowercase priority
        $response->assertJsonFragment(['priority' => 'high']);
    }

    /** @test */
    public function it_accepts_mixed_case_priority_values()
    {
        $testCases = [
            ['input' => 'Low', 'expected' => 'low'],
            ['input' => 'MEDIUM', 'expected' => 'medium'],
            ['input' => 'HiGh', 'expected' => 'high'],
        ];

        foreach ($testCases as $case) {
            $response = $this->postJson('/api/tasks', [
                'title' => "Test Task {$case['input']}",
                'priority' => $case['input'],
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('tasks', [
                'title' => "Test Task {$case['input']}",
                'priority' => $case['expected']
            ]);
        }
    }

    /** @test */
    public function it_validates_required_title_field()
    {
        $response = $this->postJson('/api/tasks', [
            'priority' => 'medium',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title'])
                 ->assertJsonFragment([
                     'message' => 'Validation failed'
                 ]);

        $this->assertDatabaseCount('tasks', 0);
    }

    /** @test */
    public function it_validates_required_priority_field()
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['priority']);

        $this->assertDatabaseCount('tasks', 0);
    }

    /** @test */
    public function it_validates_priority_values()
    {
        $invalidPriorities = ['urgent', 'normal', 'critical', '', null, 123, true];

        foreach ($invalidPriorities as $priority) {
            $response = $this->postJson('/api/tasks', [
                'title' => 'Test Task',
                'priority' => $priority,
            ]);

            $response->assertStatus(422)
                     ->assertJsonValidationErrors(['priority']);
        }

        $this->assertDatabaseCount('tasks', 0);
    }

    /** @test */
    public function it_validates_title_max_length()
    {
        $longTitle = str_repeat('a', 256); // Exceeds 255 character limit

        $response = $this->postJson('/api/tasks', [
            'title' => $longTitle,
            'priority' => 'low',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('tasks', 0);
    }

    /** @test */
    public function it_accepts_title_at_max_length()
    {
        $maxTitle = str_repeat('a', 255); // Exactly 255 characters

        $response = $this->postJson('/api/tasks', [
            'title' => $maxTitle,
            'priority' => 'low',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => $maxTitle]);
    }

    /** @test */
    public function it_handles_empty_request_body()
    {
        $response = $this->postJson('/api/tasks', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title', 'priority']);

        $this->assertDatabaseCount('tasks', 0);
    }

    // ========================================
    // TASK LISTING TESTS (GET /api/tasks)
    // ========================================

    /** @test */
    public function it_can_get_all_tasks()
    {
        $tasks = Task::factory()->count(3)->create([
            'priority' => 'medium'
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(3)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'title',
                         'priority',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        foreach ($tasks as $task) {
            $response->assertJsonFragment([
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority
            ]);
        }
    }

    /** @test */
    public function it_returns_empty_array_when_no_tasks_exist()
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertExactJson([]);
    }

    /** @test */
    public function it_returns_tasks_with_different_priorities()
    {
        Task::factory()->create(['priority' => 'low', 'title' => 'Low Priority Task']);
        Task::factory()->create(['priority' => 'medium', 'title' => 'Medium Priority Task']);
        Task::factory()->create(['priority' => 'high', 'title' => 'High Priority Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(3)
                 ->assertJsonFragment(['priority' => 'low'])
                 ->assertJsonFragment(['priority' => 'medium'])
                 ->assertJsonFragment(['priority' => 'high']);
    }

    /** @test */
    public function it_returns_tasks_in_creation_order()
    {
        $firstTask = Task::factory()->create(['title' => 'First Task']);
        sleep(1); // Ensure different timestamps
        $secondTask = Task::factory()->create(['title' => 'Second Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('First Task', $data[0]['title']);
        $this->assertEquals('Second Task', $data[1]['title']);
    }

    // ========================================
    // TASK UPDATE TESTS (PUT /api/tasks/{id})
    // ========================================

    /** @test */
    public function it_can_update_a_task_with_valid_data()
    {
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'priority' => 'low'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'priority' => 'high'
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updateData)
                 ->assertJsonStructure([
                     'id',
                     'title',
                     'priority',
                     'created_at',
                     'updated_at'
                 ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'priority' => 'high'
        ]);
    }

    /** @test */
    public function it_can_update_only_title()
    {
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'priority' => 'medium'
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'New Title Only'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'New Title Only',
            'priority' => 'medium' // Should remain unchanged
        ]);
    }

    /** @test */
    public function it_can_update_only_priority()
    {
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'priority' => 'low'
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 'HIGH' // Test case conversion
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original Title', // Should remain unchanged
            'priority' => 'high' // Should be converted to lowercase
        ]);
    }

    /** @test */
    public function it_validates_update_data()
    {
        $task = Task::factory()->create();

        // Test invalid priority
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 'invalid'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['priority']);

        // Test title too long
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => str_repeat('a', 256)
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);

        // Test empty title
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => ''
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_task()
    {
        $response = $this->putJson('/api/tasks/999', [
            'title' => 'Updated Title',
            'priority' => 'high'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_empty_update_request()
    {
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'priority' => 'medium'
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", []);

        $response->assertStatus(200);

        // Task should remain unchanged
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original Title',
            'priority' => 'medium'
        ]);
    }

    // ========================================
    // TASK DELETION TESTS (DELETE /api/tasks/{id})
    // ========================================

    /** @test */
    public function it_can_delete_a_task()
    {
        $task = Task::factory()->create([
            'title' => 'Task to Delete',
            'priority' => 'medium'
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204)
                 ->assertNoContent();

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_task()
    {
        $response = $this->deleteJson('/api/tasks/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_multiple_tasks_independently()
    {
        $task1 = Task::factory()->create(['title' => 'Task 1']);
        $task2 = Task::factory()->create(['title' => 'Task 2']);
        $task3 = Task::factory()->create(['title' => 'Task 3']);

        // Delete task2
        $response = $this->deleteJson("/api/tasks/{$task2->id}");
        $response->assertStatus(204);

        // Verify only task2 is deleted
        $this->assertDatabaseHas('tasks', ['id' => $task1->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task3->id]);
    }

    // ========================================
    // TASK CREATION VIA CREATE ENDPOINT TESTS (GET /api/tasks/create)
    // ========================================

    /** @test */
    public function it_can_create_task_via_create_endpoint()
    {
        $response = $this->getJson('/api/tasks/create');

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'title',
                     'priority',
                     'created_at',
                     'updated_at'
                 ]);

        // Verify task was created in database
        $taskData = $response->json();
        $this->assertDatabaseHas('tasks', [
            'id' => $taskData['id'],
            'title' => $taskData['title'],
            'priority' => $taskData['priority']
        ]);

        // Verify priority is either 'low' or 'high' (as per faker logic)
        $this->assertContains($taskData['priority'], ['low', 'high']);
    }

    /** @test */
    public function it_creates_different_tasks_via_create_endpoint()
    {
        $response1 = $this->getJson('/api/tasks/create');
        $response2 = $this->getJson('/api/tasks/create');

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $task1 = $response1->json();
        $task2 = $response2->json();

        // Tasks should have different IDs and titles
        $this->assertNotEquals($task1['id'], $task2['id']);
        $this->assertNotEquals($task1['title'], $task2['title']);

        // Both should be in database
        $this->assertDatabaseHas('tasks', ['id' => $task1['id']]);
        $this->assertDatabaseHas('tasks', ['id' => $task2['id']]);
    }

    // ========================================
    // INTEGRATION AND EDGE CASE TESTS
    // ========================================

    /** @test */
    public function it_handles_malformed_json_requests()
    {
        $response = $this->json('POST', '/api/tasks', [], [
            'Content-Type' => 'application/json'
        ], '{invalid json}');

        $response->assertStatus(400);
    }

    /** @test */
    public function it_handles_non_json_content_type()
    {
        $response = $this->post('/api/tasks', [
            'title' => 'Test Task',
            'priority' => 'low'
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);

        // Should still work as Laravel handles form data
        $response->assertStatus(201);
    }

    /** @test */
    public function it_maintains_data_integrity_across_operations()
    {
        // Create a task
        $createResponse = $this->postJson('/api/tasks', [
            'title' => 'Integration Test Task',
            'priority' => 'medium'
        ]);
        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('id');

        // Verify it appears in list
        $listResponse = $this->getJson('/api/tasks');
        $listResponse->assertStatus(200)
                     ->assertJsonFragment(['id' => $taskId]);

        // Update the task
        $updateResponse = $this->putJson("/api/tasks/{$taskId}", [
            'title' => 'Updated Integration Task',
            'priority' => 'high'
        ]);
        $updateResponse->assertStatus(200);

        // Verify update in list
        $listResponse = $this->getJson('/api/tasks');
        $listResponse->assertJsonFragment([
            'id' => $taskId,
            'title' => 'Updated Integration Task',
            'priority' => 'high'
        ]);

        // Delete the task
        $deleteResponse = $this->deleteJson("/api/tasks/{$taskId}");
        $deleteResponse->assertStatus(204);

        // Verify it's gone from list
        $listResponse = $this->getJson('/api/tasks');
        $listResponse->assertJsonMissing(['id' => $taskId]);
    }

    /** @test */
    public function it_handles_concurrent_operations()
    {
        $task = Task::factory()->create([
            'title' => 'Concurrent Test Task',
            'priority' => 'low'
        ]);

        // Simulate concurrent updates
        $response1 = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated by Request 1'
        ]);

        $response2 = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated by Request 2'
        ]);

        // Both should succeed (last one wins)
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify final state
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated by Request 2'
        ]);
    }

    /** @test */
    public function it_handles_large_datasets()
    {
        // Create many tasks
        Task::factory()->count(100)->create();

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(100);
    }

    /** @test */
    public function it_preserves_timestamps_correctly()
    {
        $task = Task::factory()->create();
        $originalCreatedAt = $task->created_at;
        $originalUpdatedAt = $task->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Update the task
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(200);

        $updatedTask = Task::find($task->id);

        // created_at should remain the same
        $this->assertEquals($originalCreatedAt->timestamp, $updatedTask->created_at->timestamp);

        // updated_at should be different
        $this->assertNotEquals($originalUpdatedAt->timestamp, $updatedTask->updated_at->timestamp);
    }

    /** @test */
    public function it_handles_special_characters_in_title()
    {
        $specialTitles = [
            'Task with émojis 🚀',
            'Task with "quotes" and \'apostrophes\'',
            'Task with <html> tags',
            'Task with & ampersands',
            'Task with unicode: ñáéíóú',
        ];

        foreach ($specialTitles as $title) {
            $response = $this->postJson('/api/tasks', [
                'title' => $title,
                'priority' => 'low'
            ]);

            $response->assertStatus(201)
                     ->assertJsonFragment(['title' => $title]);

            $this->assertDatabaseHas('tasks', ['title' => $title]);
        }
    }

    /** @test */
    public function it_handles_boundary_values()
    {
        // Test minimum valid title (1 character)
        $response = $this->postJson('/api/tasks', [
            'title' => 'A',
            'priority' => 'low'
        ]);
        $response->assertStatus(201);

        // Test maximum valid title (255 characters)
        $maxTitle = str_repeat('A', 255);
        $response = $this->postJson('/api/tasks', [
            'title' => $maxTitle,
            'priority' => 'high'
        ]);
        $response->assertStatus(201);
    }

    /** @test */
    public function it_maintains_consistent_response_format()
    {
        $task = Task::factory()->create();

        $endpoints = [
            ['method' => 'POST', 'url' => '/api/tasks', 'data' => ['title' => 'New Task', 'priority' => 'low']],
            ['method' => 'PUT', 'url' => "/api/tasks/{$task->id}", 'data' => ['title' => 'Updated Task']],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url'], $endpoint['data']);

            if ($endpoint['method'] !== 'DELETE') {
                $response->assertJsonStructure([
                    'id',
                    'title',
                    'priority',
                    'created_at',
                    'updated_at'
                ]);
            }
        }
    }
}
