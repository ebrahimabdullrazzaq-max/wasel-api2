<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewEmployerRegistered extends Notification
{
    use Queueable;

    protected $employer;

    public function __construct($employer)
    {
        $this->employer = $employer;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Employer Registration',
            'message' => "{$this->employer->name} has registered and is awaiting approval.",
            'employer_id' => $this->employer->id,
        ];
    }
}
