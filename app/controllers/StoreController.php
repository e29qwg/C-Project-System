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

        $this->view->selectProject = $selectProject;
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
        //get project info
        $curl_datas= ['project_id' => $project_id, 'client_id' => $this->store_client->client_id, 'client_secret' => $this->store_client->client_secret];
        $curl = curl_init($this->store_client->get_rent_items_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($curl_datas));
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output = curl_exec($curl);

        $response = json_decode($output);

        if ($response->status != 'success')
            return;

        $this->view->setVar('bookings', $response->booking);
        $this->view->setVar('bookingItems', $response->bookingItem);
        $this->view->setVar('items', $response->item);

    }
}

