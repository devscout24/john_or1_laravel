<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Order Received',
            'message' => "You have received a new order #" . $this->order->id . " from " . $this->order->customer->name,
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer->name,
            'total_amount' => $this->order->total,
            'type' => 'new_order'
        ];
    }
}