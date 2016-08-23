<?php

class IndexController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function indexAction()
    {
        $auth = $this->session->get('auth');

        if (!$auth)
            return $this->response->redirect('session');

        if ($auth['type'] == 'Student')
            return $this->forward('student');
        else if ($auth['type'] == 'Advisor')
            return $this->forward('advisor');
        else if ($auth['type'] == 'Admin')
        {
            if ($auth['view'] == 'Admin')
                return $this->forward('admin');
            if ($auth['view'] == 'Advisor')
                return $this->forward('advisor');
            else
                return $this->forward('student');
        }
    }
}

?>
