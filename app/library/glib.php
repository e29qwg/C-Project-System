<?php

define('LISTSELECTED', 'list-group-item-success');

class Glib extends \Phalcon\Mvc\User\Component
{
    //return true if completed profile
    public function isCompleteProfile($user_id)
    {
        $user = User::findFirst(array(
            'conditions' => 'id=:user_id:',
            'bind' => array('user_id' => $user_id)
        ));

        if ($user)
        {
            if (!empty($user->tel) && !empty($user->email))
                return true;
        }

        return false;
    }
}

?>