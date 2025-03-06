<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\Task; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOverdueEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $task;
    protected $assignedUser;

    public function __construct(Task $task, User $assignedUser)
    {
        $this->task = $task;
        $this->assignedUser = $assignedUser;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try 
        {    
            Mail::raw("The task {$this->task->title} " . url("api/tasks/{$this->task->id}") . " is overdue", function ($message) 
            {
                $message->to($this->assignedUser->email)->subject('Overdue Task');
            });
        } 
        
        catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage());
        }
    }
}
