<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
    public function index()
    {
        return Task::all();
    }

    public function store(Request $request)
    {
        $input = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date|after:today',
        ]);

        $task = Task::create([
            'title' => $input['title'],
            'description' => $input['description'],
            'assigned_to' => $input['assigned_to'],
            'sender_id' => $request->user()->id,
            'due_date' => $input['due_date'],
        ]);

        $assignedUser = User::find($task->assigned_to);

        if ($assignedUser) 
        {
            try 
            {
                Mail::raw("A new task has been assigned: " . url("api/tasks/{$task->id}"), function ($message) use ($assignedUser) 
                {
                    $message->to($assignedUser->email)->subject('New Task Assigned');
                });
                
                EmailLog::create([
                    'recipient_email' => $assignedUser->email,
                    'subject' => 'New Task Assigned',
                    'body' => "A new task has been assigned: " . url("api/tasks/{$task->id}"),
                    'task_id' => $task->id,
                    'sender_id' => $task->sender_id,
                ]);
            } 
            catch (\Exception $e) 
            {
                \Log::error("Failed to send email to {$assignedUser->email}: " . $e->getMessage());
            }
        }

        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return $task;
    }

    public function update(Request $request, string $id)
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

        $input = $request->validate([
            'status' => 'required|in:pending,in-progress,completed',
        ]);

        $task->status = $input['status'];
        $task->save();

        if ($task->status === 'completed') 
        {
            $this->sendTaskCompletionEmail($task);
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

    protected function sendTaskCompletionEmail($task)
    {
        $sender = User::find($task->sender_id);
        Mail::raw("The task '{$task->title}' has been marked as completed by the assignee.", function ($message) use ($sender) 
        {
            $message->to($sender->email)->subject('Task Completed');
        });

        EmailLog::create([
            'recipient_email' => $sender->email,
            'subject' => 'Task Completed',
            'body' => "The task '{$task->title}' has been marked as completed by the assignee.",
            'task_id' => $task->id,
            'sender_id' => $task->assigned_to,
        ]);
    }
}
