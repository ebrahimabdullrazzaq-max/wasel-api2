<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class EmployerRejectedNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Employer Rejected',
            'message' => 'Your employer account has been rejected by the admin.',
        ];
    }

    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Delivery Account Rejected')
        ->greeting('Hi ' . $notifiable->name)
        ->line('We regret to inform you that your delivery driver application was not approved.')
        ->line('Please contact support if you have any questions.')
        ->action('Contact Support', config('app.url') . '/support');
}
}
