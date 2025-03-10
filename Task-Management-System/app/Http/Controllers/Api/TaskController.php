<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Jobs\SendTaskAssignedEmail;
use App\Jobs\SendTaskCompletionEmail;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Resources\Task\TaskResource;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Events\TaskAssigned;
use App\Events\TaskStatusUpdated;


class TaskController extends Controller
{
    public function index()
    {
        return TaskResource::collection(Task::paginate(20));
    }

    public function store(StoreTaskRequest $request)
    {
        
        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'sender_id' => auth()->user()->id,
            'due_date' => $request->due_date,
        ]);

        SendTaskAssignedEmail::dispatch($task);
        event(new TaskAssigned($task));
        
        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, string $id)
    {

        $task = Task::findOrFail($id);
        
        if (!in_array(auth()->user()->id, [$task->assigned_to, $task->sender_id])) 
        {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $task->status = $request->status;
        $task->save();

        if ($task->status === 'completed') 
        {
           
            SendTaskCompletionEmail::dispatch($task);
        }

        event(new TaskStatusUpdated($task));

        return response()->json(['message' => 'Task status updated successfully', 'task' => $task]);
    }

    public function destroy(Task $task)
    {
    
        if (auth()->user()->id !== $task->sender_id && auth()->user()->role !== 'admin') 
        {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

}
