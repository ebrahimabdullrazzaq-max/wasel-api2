<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class EmployerApprovedNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['database']; // Save to database for /notifications
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Employer Approved',
            'message' => 'Your employer account has been approved by the admin.',
        ];
    }
    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Your Delivery Account Has Been Approved!')
        ->greeting('Hello ' . $notifiable->name . '!')
        ->line('Great news! Your delivery driver account has been approved.')
        ->line('You can now start receiving delivery orders.')
        ->action('Open App', config('app.url'))
        ->line('Thank you for joining our team!');
}
}
