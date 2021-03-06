<?php

class Elements extends Phalcon\Mvc\User\Component
{

    private $_studentMenu = array(
        'pull-left' => array(
            'mainmenu' => array(
                'caption' => 'Home',
                'action' => 'index',
                'active' => 'index'
            ),
            'room' => [
                'caption' => 'Room',
                'action' => 'room',
                'active' => 'room'
            ]
        )
    );

    private $_advisorMenu = [
        'pull-left' => array(
            'mainmenu' => array(
                'caption' => 'Home',
                'action' => 'index',
                'active' => 'index'
            ),
            'room' => [
                'caption' => 'Room',
                'action' => 'room/viewOnly',
                'active' => 'room'
            ]
        )
    ];

    public function getAdvisorMenu()
    {
        $controllerName = $this->view->getControllerName();
        foreach ($this->_advisorMenu as $position => $menu)
        {
            echo '<ul class="nav navbar-nav navbar-' . $position . '">';
            foreach ($menu as $controller => $option)
            {
                if ($option['active'] == $controllerName)
                {
                    echo '<li class="active">';
                }
                else
                {
                    echo '<li>';
                }

                echo Phalcon\Tag::linkTo('./' . $option['action'], $option['caption']);
                echo '</li>';
            }
        }
        echo '</ul>';
        echo '<ul class="nav navbar-nav navbar-right">';
        $this->getLIO();
        echo '</ul>';
    }

    public function getMenu()
    {
        $auth = $this->session->get('auth');

        if ($auth['type'] == 'Student')
            $this->getStudentMenu();
        else if ($auth['type'] == 'Advisor')
            $this->getAdvisorMenu();
        else if ($auth['type'] == 'Admin')
            $this->getAdvisorMenu();
    }

    public function getStudentMenu()
    {
        $controllerName = $this->view->getControllerName();

        foreach ($this->_studentMenu as $position => $menu)
        {
            echo '<ul class="nav navbar-nav navbar-' . $position . '">';
            foreach ($menu as $controller => $option)
            {
                if ($option['active'] == $controllerName)
                {
                    echo '<li class="active">';
                }
                else
                {
                    echo '<li>';
                }

                echo Phalcon\Tag::linkTo('./' . $option['action'], $option['caption']);
                echo '</li>';
            }
        }
        echo '</ul>';
        echo '<ul class="nav navbar-nav navbar-right">';
        $this->getLIO();
        echo '</ul>';
    }

    public function getLIO()
    {
        $auth = $this->session->get('auth');
        $userType = $auth['type'];
        echo '<li>';
        if (!$auth)
        {
            //echo Phalcon\Tag::linkTo('./session' , 'Login');
        }
        else
        {
            $controllerName = $this->view->getControllerName();

            if ($userType == 'Admin')
            {
                if ($controllerName == 'admin')
                    echo '<li class="active">';
                else
                    echo '<li>';
                echo Phalcon\Tag::LinkTo('./admin', 'Admin') . '</li>';
            }


            echo '<li>' . Phalcon\Tag::linkTo('./profile/index/' . $auth['id'], '<span class="glyphicon glyphicon-user col-sm-2"></span>&nbsp;&nbsp;' . $auth['title'] . $auth['name']) . '</li>';
            echo '<li>' . Phalcon\Tag::linkTo('./session/logout', '<span class="glyphicon glyphicon-log-out"></span>');
        }
        echo '</li>';
    }
}
