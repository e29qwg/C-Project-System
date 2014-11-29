<?php

class ControllerBase extends Phalcon\Mvc\Controller
{
    protected function initialize()
    {
        $auth = $this->session->get('auth');
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
            'action' => $uriParts[1],
        ));;
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

}

?>
