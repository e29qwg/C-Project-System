<?php

class StoreItem extends \Phalcon\Mvc\Model
{

    public $id;
    public $name;
    public $keyword;
    public $category_id;
    public $detail;
    public $warning;
    public $type;
    public $student_view;
    public $has_ref;
    public $admin_id;

    public function initialize()
    {
        $this->setConnectionService('dbStore');
        $this->setSource('item');

        $this->hasMany('id', 'StoreBookingItem', 'item_id', array('alias' => 'BookingItem'));
    }

}
