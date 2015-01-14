<?php

class Security extends \Phalcon\Mvc\User\Plugin
{
    protected $_acl;

    public function __construct($dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    public function beforeDispatch(Phalcon\Events\Event $event, Phalcon\Mvc\Dispatcher $dispatcher)
    {
        //TODO
        $this->flashSession->output();
        $auth = $this->session->get('auth');
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        if (!$auth)
        {
            $role = 'Guest';
            if ($controller != 'session')
            {
                $this->response->redirect('session');
            }
            return true;
        }
        else
        {
            $role = $auth['type'];
        }

        $acl = $this->getAcl();
        $allowed = $acl->isAllowed($role, $controller, $action);

        if ($allowed != Phalcon\Acl::ALLOW)
        {
            $this->flash->error("You cannot access this module");

            $dispatcher->forward(array('controller' => 'index', 'action' => 'index'));

            return false;
        }

    }

    public function getAcl()
    {

        if (!$this->_acl)
        {
            $acl = new Phalcon\Acl\Adapter\Memory();
            $acl->setDefaultAction(Phalcon\Acl::DENY);

            $roles = array(
                'Admin' => new Phalcon\Acl\Role('Admin'),
                'Student' => new Phalcon\Acl\Role('Student'),
                'Advisor' => new Phalcon\Acl\Role('Advisor'),
                'Staff' => new Phalcon\Acl\Role('Staff'),
                'Guest' => new Phalcon\Acl\Role('Guest')
            );

            foreach ($roles as $role)
            {
                $acl->addRole($role);
            }

            //public acl
            $publicResources = array('index' => array('index'), 'session' => array('index', 'login', 'logout'));

            foreach ($publicResources as $resource => $actions)
            {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //grant access to all user
            foreach ($roles as $role)
            {
                foreach ($publicResources as $resource => $actions)
                {
                    foreach ($actions as $action)
                    {
                        $acl->allow($role->getName(), $resource, $action);
                    }
                }
            }

            //Student acl
            $studentResources = array(
                'student' => array('*'),
                'projects' => array(
                    'newProject',
                    'doNewProject',
                    'me',
                    'manage',
                    'delete',
                    'editSetting',
                    'member',
                    'addmember',
                    'doAddMember',
                    'deletemember',
                ),
                'progress' => array(
                    'index',
                    'newProgress',
                    'doAddProgress',
                    'view',
                    'delete',
                    'edit',
                    'doEdit',
                    'exportPDF'
                ),
                'advisor' => array('advisorList'),
                'profile' => array('*'),
                'exam' => array('showExam'),
                'score' => array('studentView'),
                'userSettings' => array('*')
            );

            foreach ($studentResources as $resource => $actions)
            {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //grant access for student
            foreach ($studentResources as $resource => $actions)
            {
                foreach ($actions as $action)
                {
                    $acl->allow('Student', $resource, $action);
                    $acl->allow('Admin', $resource, $action);
                }
            }

            $advisorResources = array(
                'advisor' => array('*'),
                'projects' => array(
                    'manage',
                    'delete',
                    'editSetting',
                    'member',
                    'addmember',
                    'doAddMember',
                    'deletemember',
                    'proposed',
                    'accept',
                    'reject'
                ),
                'progress' => array(
                    'view',
                    'evaluate',
                    'doEvaluate',
                    'index',
                    'newProgress',
                    'doAddProgress',
                    'delete',
                    'edit',
                    'doEdit',
                    'exportPDF'
                ),
                'profile' => array('*'),
                'exam' => array('download', 'showExam'),
                'score' => array('advisorView'),
                'userSettings' => array('*')
            );

            foreach ($advisorResources as $resource => $actions)
            {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            foreach ($advisorResources as $resource => $actions)
            {
                foreach ($actions as $action)
                {
                    $acl->allow('Advisor', $resource, $action);
                    $acl->allow('Admin', $resource, $action);
                }
            }

            //admin acl
            $adminResources = array(
                'admin' => array('*'),
                'profile' => array('*'),
                'score' => array('*'),
                'exam' => array('*'),
                'news' => array('*'),
                'settings' => array('*'),
                'enroll' => array('*')
            );

            foreach ($adminResources as $resource => $actions)
            {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }


            //grant access for admin
            foreach ($adminResources as $resource => $actions)
            {
                foreach ($actions as $action)
                {
                    $acl->allow('Admin', $resource, $action);
                }
            }

            $this->_acl = $acl;
        }

        return $this->_acl;
    }
}

?>
