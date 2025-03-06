<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\User;
use App\Jobs\SendReminderEmail;
use App\Jobs\SendOverdueEmail;
use Illuminate\Support\Facades\Mail;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send task reminders for pending and in-progress tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::whereIn('status', ['pending', 'in-progress'])->get();

        foreach ($tasks as $task)
        {
            $assignedUser = User::find($task->assigned_to);

            SendReminderEmail::dispatch($task, $assignedUser);

            if ($task->due_date < now()) 
            {
                SendOverdueEmail::disptach($task, $assignedUser);
            }
        }

        $this->info('Task reminders sent successfully.');
    }

    /**
     * Send an overdue email.
     *
     * @param Task $task
     * @param User $user
     * @return void
     */
    // protected function sendOverdueEmail($task, $user)
    // {
    //     Mail::raw("The task '{$task->title}' is overdue. Please complete it as soon as possible.", function ($message) use ($user) {
    //         $message->to($user->email)->subject('Overdue Task');
    //     });
    // }
}
