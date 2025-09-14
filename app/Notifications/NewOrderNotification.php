<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'mail', 'broadcast']; // ✅ Added broadcast
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'New order received!',
            'order_id' => $this->order->id,
            'customer' => $this->order->user->name,
            'amount' => $this->order->total,
            'link' => '/admin/orders/' . $this->order->id
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->order->id,
            'type' => 'order',
            'message' => "📦 New Order #{$this->order->id} - {$this->order->total} SAR",
            'data' => [
                'order_id' => $this->order->id,
                'customer' => $this->order->user->name,
                'amount' => $this->order->total,
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Order #' . $this->order->id)
            ->line('You have a new order from ' . $this->order->user->name)
            ->line('Total amount: $' . number_format($this->order->total, 2))
            ->action('View Order', url('/admin/orders/' . $this->order->id))
            ->line('Thank you for using our application!');
    }
}
