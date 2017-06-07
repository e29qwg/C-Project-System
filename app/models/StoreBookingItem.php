<?php

class StoreBookingItem extends \Phalcon\Mvc\Model
{
    public $id;
    public $booking_id;
    public $item_id;
    public $amount;

    public function initialize()
    {
        $this->setConnectionService('dbStore');
        $this->setSource('booking_item');
        $this->belongsTo('item_id', 'StoreItem', 'id', ['alias' => 'Item']);
        $this->belongsTo('booking_id', 'StoreBooking', 'id', ['alias' => 'Booking']);
    }

}
