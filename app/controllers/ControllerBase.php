<?php

class ControllerBase extends Phalcon\Mvc\Controller
{
    protected $auth;
    protected $current_semester;
    protected $allSemester;
    protected $userSemester;
    protected $pp_start;
    protected $p1_start;
    protected $p2_start;

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

        $this->view->setVar('url', $this->url->get());
        $this->auth = $auth;

        $this->loadUserSemester();
        $this->loadAllSemester();
        $this->loadCurrentSemester();
        $this->loadRoomDate();
    }

    protected  function checkAPIClient($request)
    {
        $client_id = $request->getPost('client_id');
        $client_secret = $request->getPost('client_secret');

        $client = Client::findFirst([
            "conditions" => "client_id=:client_id:",
            "bind" => ["client_id" => $client_id]
        ]);

        $response = [
            'status' => 'error',
            'data' => null,
            'message' => ''
        ];

        if (!$client) {
            $response['message'] = 'Invalid client';
            echo json_encode($response);
            return false;
        }

        if ($client->client_secret != $client_secret) {
            $response['message'] = 'Invalid secret';
            echo json_encode($response);
            return false;
        }

        return true;
    }

    protected function loadSetting($key)
    {
        $setting = Settings::findFirst([
            "conditions" => "name=:key:",
            "bind" => ["key" => $key]
        ]);

        if (!$setting)
            return null;
        return $setting->value;
    }

    protected function loadRoomDate()
    {
        $setting = Settings::findFirst("name='room_reserve_p1_start'");
        $this->p1_start = $setting->value;

        $setting = Settings::findFirst("name='room_reserve_p2_start'");
        $this->p2_start = $setting->value;

        $setting = Settings::findFirst("name='room_reserve_pp_start'");
        $this->pp_start = $setting->value;
    }
    
    protected function loadAdvisorProject()
    {
        $builder = $this->modelsManager->createBuilder();
        $builder->from("Project");
        $builder->where("Project.semester_id=:semester_id:", array("semester_id" => $this->userSemester));
        $builder->innerJoin("ProjectMap", "Project.project_id=ProjectMap.project_id");
        $builder->andWhere("ProjectMap.user_id=:user_id:", array("user_id" => $this->auth['id']));
        $builder->andWhere("ProjectMap.map_type='advisor'");
        $builder->andWhere("Project.project_status='accept'");

        $projects = $builder->getQuery()->execute();

        $datas = array(
            'pp' => array(),
            'p1' => array(),
            'p2' => array()
        );

        foreach ($projects as $project)
        {
            switch ($project->project_level_id)
            {
                case '1': array_push($datas['pp'], $project); break;
                case '2': array_push($datas['p1'], $project); break;
                case '3': array_push($datas['p2'], $project); break;
            }
        }

        $this->view->projects = $datas;
        $this->view->project_id = $this->dispatcher->getParam(0);
    }

    protected function loadViewAdvisors()
    {
        $users = User::find("type='advisor'");

        $advisors = array();

        foreach ($users as $user)
        {
            $advisors[$user->id] = $user->title . $user->name;
        }

        $this->view->setVar('advisors', $advisors);
    }

    protected function loadUserSemester()
    {
        $userSemester = UserCurrentSemester::findFirst(array(
            "conditions" => "user_id=:user_id:",
            "bind" => array("user_id" => $this->auth['id'])
        ));

        if ($userSemester)
            $this->userSemester = $userSemester->semester_id;
        else
            $this->userSemester = $this->current_semester;
    }

    protected function loadCurrentSemester()
    {
        $setting = Settings::findFirst("name='current_semester'");
        $this->current_semester = $setting->value;

        //fetch current_semester

        $semester = Semester::findFirst(array(
            "conditions" => "semester_id=:id:",
            "bind" => array("id" => $this->current_semester)
        ));

        $this->view->setVar('current_semester', $semester->semester_term . '/' . $semester->semester_year);
        $this->view->setVar('current_semester_id', $semester->semester_id);
    }


    protected function loadAllSemester()
    {
        $this->allSemester = Semester::find();
        $semesters = array();

        foreach ($this->allSemester as $semester)
        {
            $semesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemester', $semesters);
    }


    protected function _redirectBack()
    {
        if (isset($_SERVER['HTTP_REFERER']))
            return $this->response->redirect($_SERVER['HTTP_REFERER']);
        return $this->response->redirect('index');
    }

    protected function sendMail($subject, $message, $to)
    {
        if (empty($to))
            return;

        $this->queue->choose($this->config->queue->tube);
        $this->queue->put(array(
            'from' => 'CoE-Project',
            'send_to' => $to,
            'subject' => $subject,
            'message' => $message
        ));
    }

    protected function dbError($model)
    {
        if (true)
        {
            foreach ($model->getMessages() as $mes)
            {
                $this->flashSession->error($mes);
            }
        }
    }

    protected function strDbError($model)
    {
        $str = '';

        foreach ($model->getMessages() as $mes)
        {
            $str .= $mes . '<br>';
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


    protected function forward($uri)
    {
        $uriParts = explode('/', $uri);

        if (empty($uriParts[1]))
            $uriParts[1] = 'index';

        $this->dispatcher->forward(array(
            'controller' => $uriParts[0],
            'action' => $uriParts[1]
        ));

        return true;
    }

    protected function pForward($controller, $action, $params)
    {
        $this->dispatcher->forward(array(
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        ));

        return true;
    }

    protected function loadOwnerProject()
    {
        $builder = $this->modelsManager->createBuilder();
        $builder->from("Project");
        $builder->innerJoin("ProjectMap", "Project.project_id=ProjectMap.project_id");
        $builder->where("ProjectMap.map_type='owner'");
        $builder->andWhere("ProjectMap.user_id=:user_id:", array("user_id" => $this->auth['id']));
        $builder->orderBy("Project.semester_id DESC");

        $projects = $builder->getQuery()->execute();

        $this->view->projects = $projects;
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
