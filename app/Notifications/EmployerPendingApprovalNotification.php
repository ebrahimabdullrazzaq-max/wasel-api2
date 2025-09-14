<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class EmployerPendingApprovalNotification extends Notification
{
    use Queueable;

    protected $employer;

    /**
     * Create a new notification instance.
     */
    public function __construct($employer)
    {
        $this->employer = $employer;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Employer Registration',
            'message' => 'An employer named ' . $this->employer->name . ' has registered and is pending approval.',
            'employer_id' => $this->employer->id,
        ];
    }
}
