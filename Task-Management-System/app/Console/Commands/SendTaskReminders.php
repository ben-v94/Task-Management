<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::where(function($query) 
        {
                        $query->where('status', 'pending')->orWhere('status', 'in-progress');
        })
                    ->where('due_date', '<', now())->get();

        foreach ($tasks as $task) 
        {
            $assignedUser = User::find($task->assigned_to);
            
            $this->sendReminderEmail($assignedUser, $task);
            
            if ($task->due_date < now()) 
            {
                $this->sendOverdueEmail($assignedUser, $task);
            }
    }

    $this->info('Task reminders sent successfully.');
    }

    protected function sendReminderEmail($user, $task)
    {
        Mail::raw("You have a pending task: {$task->title}. Due date: {$task->due_date}.", function ($message) use ($user)
        {
            $message->to($user->email)->subject('Task Reminder');
        });
    }

    protected function sendOverdueEmail($user, $task)
    {
        Mail::raw("The task '{$task->title}' is overdue. Please complete it as soon as possible.", function ($message) use ($user) 
        {
            $message->to($user->email)->subject('Overdue Task');
        });
    }
}
