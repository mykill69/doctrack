<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoutingSlipCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $routingSlip;

    public function __construct($routingSlip)
    {
        $this->routingSlip = $routingSlip;
    }

    public function broadcastOn()
    {
        return new Channel('routing-slip-channel');
    }

    public function broadcastWith()
    {
        return [
            'message' => 'A new routing slip has been created!',
            'routingSlip' => $this->routingSlip,
        ];
    }
}