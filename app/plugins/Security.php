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
        $this->flashSession->output();
        $auth = $this->session->get('auth');
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        if (!$auth)
        {
            if ($controller != 'session' && $controller != 'api')
            {
                $this->response->redirect('session');
            }
            return true;
        }
        else
        {
            $role = $this->permission->getRole($auth['id']);

            if ($role == 'Incomplete' && $controller != 'profile' && $controller != 'session')
            {
                $this->view->setTemplateAfter('main');
                $this->flash->warning('Please complete your profile');
                $this->dispatcher->forward(array(
                    'controller' => 'profile',
                    'action' => 'updateProfile'
                ));
            }
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
                'Incomplete' => new Phalcon\Acl\Role('Incomplete'),
                'Guest' => new Phalcon\Acl\Role('Guest')
            );

            foreach ($roles as $role)
            {
                $acl->addRole($role);
            }

            //public acl
            $publicResources = array(
                'index' => array('index'),
                'api' => array('getStatusProjectFarm'),
                'session' => array('index', 'login', 'localLogin', 'logout', 'useHash'),
            );

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

            $incompleteResources = array(
                'profile' => array('*')
            );

            foreach ($incompleteResources as $resource => $actions)
            {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);

                foreach ($actions as $action)
                    $acl->allow('Incomplete', $resource, $action);
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
                'advisor' => array(
                    'list',
                    'profile',
                    'getProjectList',
                    'checkQuota'
                ),
                'profile' => array('*'),
                'exam' => array('showExam'),
                'score' => array('studentView'),
                'userSettings' => array('*'),
                'report' => array(
                    'index',
                    'upload',
                    'doUpload',
                    'download'
                )
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
                    $acl->allow('Staff', $resource, $action);
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
                'userSettings' => array('*'),
                'report' => array('evaluate', 'reject', 'accept')
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
