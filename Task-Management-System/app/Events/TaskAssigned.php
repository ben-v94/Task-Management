<?php

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Task;

class TaskAssigned implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $task;
  public $taskUrl;

  public function __construct($task)
  {
      $this->task = $task;
      $this->taskUrl = url("/tasks/{$task->id}");
  }

  public function broadcastOn()
  {
    return ['user-' . $this->task->assigned_to];
  }

  public function broadcastAs()
  {
    return 'task-assigned';
  }
}