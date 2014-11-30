<?php

class UsersettingsController extends ControllerBase
{
    private $auth;

    public function initialize()
    {
        $this->auth = $this->session->get('auth');
    }

    public function setSemesterAction()
    {
        $params = $this->dispatcher->getParams();

        if (!empty($params[0]))
        {
            $userCurrentSemester = UserCurrentSemester::findFirst(array(
                "conditions" => "user_id=:user_id:",
                "bind" => array("user_id" => $this->auth['id'])
            ));

            if (!$userCurrentSemester)
                $userCurrentSemester = new UserCurrentSemester();

            $userCurrentSemester->user_id = $this->auth['id'];
            $userCurrentSemester->semester_id = $params[0];
            $userCurrentSemester->save();

            $this->view->disable();
        }
    }
}

?>
