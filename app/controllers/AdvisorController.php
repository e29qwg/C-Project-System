<?php

class AdvisorController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('advisorside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function advisorListAction()
    {
        $this->view->setTemplateAfter('main');
    }

    public function indexAction()
    {
    }
}

?>
