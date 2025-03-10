<?php

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Task;

class TaskStatusUpdated implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $task;
  public $taskUrl;
  public $recipient;

  public function __construct($task)
  {
      $this->task = $task;
      $this->taskUrl = url("/tasks/{$task->id}");
      $this->recipient = auth()->user()->id === $task->assigned_to ? $task->sender_id : $task->assigned_to;
  }

  public function broadcastOn()
  {
    return ['user-' . $this->recipient];
  }

  public function broadcastAs()
  {
    return 'task-status-updated';
  }
}