<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTaskNotification extends Notification
{
  use Queueable;

  public Task $task;

  public function __construct(Task $task)
  {
    $this->task = $task;
  }

  public function via($notifiable)
  {
    return ['database']; // لاحقًا يمكن إضافة broadcast أو sms
  }

  public function toArray($notifiable)
  {
    return [
      'task_id' => $this->task->id,
      'title' => 'New Task Assigned',
      'body' => 'You have a new task available for confirmation.',
    ];
  }
}
