<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class EmployerAssignedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Optional: Add 'mail' or 'broadcast' later
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'order',
            'title' => 'New Order Assigned',
            'message' => 'You have been assigned a new order with ID ' . $this->order->id,
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'assigned_at' => now(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Order Assigned')
            ->line('You have a new delivery order.')
            ->action('View Order', url('/employer/orders/' . $this->order->id));
    }
}
