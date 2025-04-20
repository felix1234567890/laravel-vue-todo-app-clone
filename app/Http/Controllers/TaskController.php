<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Faker\Factory as Faker;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(): JsonResponse
    {
        return response()->json(Task::all(), Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): JsonResponse
    {
        $faker = Faker::create();
        $task = new Task();
        $task->title = $faker->sentence(1);
        $task->priority = $faker->boolean ? 'low' : 'high'; // Already lowercase
        $task->save();
        return response()->json($task, Response::HTTP_CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Log the incoming request data
            Log::info('Task store request data:', $request->all());

            // Convert priority to uppercase for storage
            $data = $request->all();
            if (isset($data['priority'])) {
                // Convert to lowercase for validation
                $data['priority'] = strtolower($data['priority']);
            }

            Log::info('Normalized data for validation:', $data);

            $validated = validator($data, [
                'title' => 'required|string|max:255',
                'priority' => 'required|string|in:low,medium,high',
            ])->validate();

            Log::info('Validation passed, creating task with data:', $validated);

            // Ensure priority is lowercase for storage
            if (isset($validated['priority'])) {
                $validated['priority'] = strtolower($validated['priority']);
            }

            $task = Task::create($validated);
            Log::info('Task created successfully:', ['task_id' => $task->id]);

            return response()->json($task, Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', [
                'errors' => $e->errors(),
                'data_received' => $request->all()
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'data_received' => $request->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Exception in TaskController@store:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_received' => $request->all()
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the task',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json($task, Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'priority' => 'sometimes|required|string|in:low,medium,high',
        ]);

        // Ensure priority is lowercase for storage
        if (isset($validated['priority'])) {
            $validated['priority'] = strtolower($validated['priority']);
        }

        $task->update($validated);
        return response()->json($task, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
