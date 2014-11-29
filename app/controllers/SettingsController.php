<?php
    
class SettingsController extends Phalcon\Mvc\Controller
{
    private $auth;

    public function initialize()
    {	
        $this->auth = $this->session->get('auth');
    }

    public function indexAction()
    {
        $this->view->setTemplateAfter('adminside');

        $settings = Settings::find();

        $this->view->setVar('settings', $settings);
    }

}

?>
