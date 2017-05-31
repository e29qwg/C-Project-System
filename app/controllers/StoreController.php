<?php

class StoreController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();

        $this->loadOwnerProject();

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();

        $project_id = $this->dispatcher->getParam(0);

        $selectProject = Project::findFirst(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project_id)
        ));

        $this->view->setVar('selectProject', $selectProject);
    }

    public function indexAction()
    {
        $this->loadOwnerProject();
        $params = $this->dispatcher->getParams();

        $project_id = $params[0];

        if (!$this->permission->checkPermission($this->auth['id'], $project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();

        $this->getStoreInfo();
    }

    public function getStoreInfo()
    {
        $params = $this->dispatcher->getParams();

        $project_id = $params[0];

        $bookings = StoreBooking::find([
            "conditions" => "project_id=:project_id: AND (status='pending' OR status='pending' OR status='wait' OR status='accept' OR status='in_use')",
            "bind" => ["project_id" => $project_id]
        ]);


        $finalBookings = StoreBooking::find([
            "conditions" => "project_id=:project_id: AND status='final'",
            "bind" => ["project_id" => $project_id]
        ]);

        $cancelBookings = StoreBooking::find([
            "conditions" => "project_id=:project_id: AND status='cancel'",
            "bind" => ["project_id" => $project_id]
        ]);

        $this->view->setVar('bookings', $bookings);
        $this->view->setVar('finalBookings', $finalBookings);
        $this->view->setVar('cancelBookings', $cancelBookings);
    }
}

