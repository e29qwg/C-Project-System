<?php

class StoreBooking extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $advisor_id;
    public $subject_id;
    public $use_for_type;
    public $use_for;
    public $project_id;
    public $status;
    public $return_date;
    public $create_date;
    public $auto_cancel_time;

    public function copyData($booking)
    {
        $this->user_id = $booking->user_id;
        $this->advisor_id = $booking->advisor_id;
        $this->subject_id = $booking->subject_id;
        $this->use_for = $booking->use_for;
        $this->status = $booking->status;
        $this->return_date = $booking->return_date;
        $this->create_date = $booking->create_date;
        $this->use_for_type = $booking->use_for_type;
        $this->project_id = $booking->project_id;
        $this->auto_cancel_time = $booking->auto_cancel_time;
    }

    public function setWait()
    {
        $config = include __DIR__ . "/../config/config.php";

        $this->status = 'wait';
        $this->auto_cancel_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + $config->cancel_time->waiting);
    }

    public function setPending()
    {
        $config = include __DIR__ . "/../config/config.php";

        $this->status = 'pending';
        $this->auto_cancel_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + $config->cancel_time->pending);
    }


    public function beforeValidationOnCreate()
    {
        $config = include __DIR__ . "/../config/config.php";

        if (empty($this->status))
            $this->status = 'new';
        if (empty($this->create_date))
            $this->create_date = date('Y-m-d H:i:s');
        if (empty($this->auto_cancel_time))
            $this->auto_cancel_time = date('Y-m-d H:i:s', strtotime($this->create_date) + $config->cancel_time->new);
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setConnectionService('dbStore');
        $this->setSource('booking');

        $this->hasMany('id', 'StoreBookingItem', 'booking_id', array('alias' => 'BookingItem'));
        $this->hasOne('id', 'StoreBookingMap', 'booking_id', array('alias' => 'BookingMap'));
    }

}
