<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;

class SendTaskCompletionEmail implements ShouldQueue
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
    public function handle()
    {
        $sender = User::find($this->task->sender_id);

        if ($sender) 
        {
            try 
            {
                Mail::raw("The task '{$this->task->title}' has been marked as completed by the assignee.", function ($message)
                {
                    $message->to($sender->email)->subject('Task Completed');
                });

                EmailLog::create([
                    'recipient_email' => $sender->email,
                    'subject' => 'Task Completed',
                    'body' => "The task '{$this->task->title}' has been marked as completed by the assignee.",
                    'task_id' => $this->task->id,
                    'sender_id' => $this->task->assigned_to,
                ]);
            } 
            catch (\Exception $e) 
            {
                \Log::error("Failed to send email to {$sender->email}: " . $e->getMessage());
            }
        }
    }
}
