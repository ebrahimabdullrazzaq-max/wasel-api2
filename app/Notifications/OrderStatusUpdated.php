<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // You can add 'mail' if you want
    }

    /**
     * Store the notification in the database.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'order_update',
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => 'Your order #' . $this->order->id . ' status has been updated to ' . $this->order->status,
        ];
    }

    /**
     * (Optional) To show as array (if needed for API)
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
