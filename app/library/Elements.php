<?php

class Elements extends Phalcon\Mvc\User\Component
{   

    private $_headerMenu = array
    (
        'pull-left' => array(
/*            'problemset' => array(
                'caption' => 'Problem',
                'action' => 'problem',
                'active' => 'problem'
            ),*/
        ),
    );


    public function getMenu()
    {
        $controllerName = $this->view->getControllerName();
        foreach ($this->_headerMenu as $position => $menu) {
            echo '<ul class="nav navbar-nav navbar-'.$position.'">';
            foreach ($menu as $controller => $option) {
                if ($option['active'] == $controllerName) {
                    echo '<li class="active">';
                } else {
                    echo '<li>';
                }
                
                echo Phalcon\Tag::linkTo('./'.$option['action'],$option['caption']);
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
                echo Phalcon\Tag::LinkTo('./admin', 'Admin').'</li>';
            }


            echo '<li>'.Phalcon\Tag::linkTo('./profile/index/'.$auth['id'] , '<span class="glyphicon glyphicon-user col-sm-2"></span>&nbsp;&nbsp;'.$auth['title'].'&nbsp;'.$auth['name']).'</li>';
            echo '<li>'.Phalcon\Tag::linkTo('./session/logout', '<span class="glyphicon glyphicon-log-out"></span>');
        }
        echo '</li>';
    }
}
