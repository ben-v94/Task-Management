<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\Task; 
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;



class SendTaskAssignedEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $assignedUser = User::find($this->task->assigned_to);

        if ($assignedUser) 
        {
            try 
            {
                Mail::raw("A new task has been assigned: " . url("api/tasks/{$this->task->id}"), function ($message)
                {
                    $message->to($assignedUser->email)->subject('New Task Assigned');
                });

                EmailLog::create([
                    'recipient_email' => $assignedUser->email,
                    'subject' => 'New Task Assigned',
                    'body' => "A new task has been assigned: " . url("api/tasks/{$this->task->id}"),
                    'task_id' => $this->task->id,
                    'sender_id' => $this->task->sender_id,
                ]);
            } 
            catch (\Exception $e) 
            {
                \Log::error("Failed to send email to {$assignedUser->email}: " . $e->getMessage());
            }
        }
    }
}
