<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Jobs\SendTaskAssignedEmail;
use App\Jobs\SendTaskCompletionEmail;
use App\Http\Requests\Task\StoreTaskRequest;


class TaskController extends Controller
{
    public function index()
    {
        return Task::paginate(20);
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
        
        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return $task;
    }

    public function update(UpdateTaskRequest $request, string $id)
    {
        try 
        {
            $task = Task::findOrFail($id);
        } 
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
        {
            return response()->json(['error' => 'Task not found'], 404);
        }

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

        return response()->json(['message' => 'Task status updated successfully', 'task' => $task]);
    }

    public function destroy(string $id)
    {
        try 
        {
            $task = Task::findOrFail($id);
        } 

        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
        {
            return response()->json(['error' => 'Task not found'], 404);
        }

        if (auth()->user()->id !== $task->sender_id && auth()->user()->role !== 'admin') 
        {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

}
