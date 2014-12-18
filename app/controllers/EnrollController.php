<?php

class EnrollController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function indexAction()
    {

    }
}

?>