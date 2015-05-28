<?php

class ControllerBase extends Phalcon\Mvc\Controller
{
    protected $auth;

    protected function initialize()
    {
        $auth = $this->session->get('auth');

        $setting = Settings::findFirst("name='current_semester'");

        if (!$setting)
        {
            $this->flash->error('Cannot read setting from database');
            return false;
        }

        $this->view->setVar('currentSemesterId', $setting->value);

        if ($auth)
        {

            if ($auth['type'] == 'Advisor')
                $this->view->setTemplateAfter('advisorside');
            if ($auth['type'] == 'Admin')
            {
                if ($auth['view'] == 'Advisor')
                    $this->view->setTemplateAfter('advisorside');
                else if ($auth['view'] == 'Admin')
                    $this->view->setTemplateAfter('adminside');
                else
                    $this->view->setTemplateAfter('main');
            }
        }

        $this->auth = $auth;
    }

    protected function strDbError($model)
    {
        $str = '';

        foreach ($model->getMessages() as $mes)
        {
            $str .= $mes.'<br>';
        }
        return $str;
    }


    protected function _getAllSemester()
    {
        $semesters = Semester::find();
        $allSemesters = array();
        $allSemesterIds = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
            array_push($allSemesterIds, $semester->semester_id);
        }

        $this->view->setVar('allSemesters', $allSemesters);
        $this->view->setVar('allSemesterIds', $allSemesterIds);
    }

    protected function _checkAdvisorPermission($project_id)
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $projectMap = ProjectMap::findFirst("user_id='$user_id' AND map_type='advisor' AND project_id='$project_id'");

        if (!$projectMap || empty($project_id))
        {
            $this->flash->error('Access Denied');
            $this->forward('index');

            return false;
        }


        return true;
    }

    protected function forward($uri)
    {
        $uriParts = explode('/', $uri);

        if (empty($uriParts[1]))
            $uriParts[1] = 'index';

        return $this->dispatcher->forward(array(
            'controller' => $uriParts[0],
            'action' => $uriParts[1]
        ));
    }

    protected function _checkPermission($project_id)
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $projectMap = ProjectMap::findFirst("project_id='$project_id' AND user_id='$user_id'");

        if (!$projectMap || empty($project_id))
        {
            $this->flash->error('Access Denied');
            $this->forward('index');

            return false;
        }
        return true;
    }

    protected function wantNotification($user_id, $noption)
    {
        $notification = Notification::findFirst(array(
            "conditions" => "user_id=:user_id: AND noption=:noption:",
            "bind" => array("user_id" => $user_id, "noption" => $noption)
        ));

        if ($notification)
            return true;
        return false;
    }

}

?>
