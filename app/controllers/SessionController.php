<?php

use Phalcon\Mvc\Model\Transaction\Failed;

class SessionController extends ControllerBase
{
    public function initialize()
    {
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา-เข้าสู่ระบบ');
        parent::initialize();
    }

    public function useHashAction()
    {
        $this->clearHash();

        $hash = $this->dispatcher->getParam(0);

        $hashLink = HashLink::findFirst(array(
            "conditions" => "hash=:hash:",
            "bind" => array("hash" => $hash)
        ));

        if (!$hashLink)
        {
            $this->flash->error('Link not found or expired.');
            return $this->forward('session');
        }

        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $hashLink->user_id)
        ));

        if (!$user)
        {
            $this->flash->error('User not valid');
            return $this->forward('session');
        }

        $auth = $this->session->get('auth');

        if (!$auth)
        {
            $this->_registerSession($user);
        }
        else
        {
            if ($user->id != $auth['id'])
            {
                $this->flash->error('User not valid');
                return $this->forward('session/end');
            }
        }

        $this->response->redirect($hashLink->link);
        $hashLink->delete();
    }

    public function indexAction()
    {
        if ($this->session->get('auth'))
            return $this->forward('index');
    }

    public function adminLoginAction()
    {
        if ($this->session->get('auth'))
            return $this->forward('index');
    }

    //current use for admin only
    public function localLoginAction()
    {
        if (!$this->request->isPost())
        {
            $this->flashSession->error('Invalid Request');
            return $this->response->redirect('session');
        }
        if (!$this->security->checkToken())
        {
            $this->flashSession->error('Invalid Token');
            return $this->response->redirect('session');
        }

        $request = $this->request;
        $username = $request->getPost('username');
        $password = $request->getPost('password');

        if (empty($username) || empty($password))
        {
            $this->flash->error('Login Failure');
            return $this->forward('session/adminLogin');
        }

        //Authenticate

        $user = User::findFirst(array(
            "conditions" => "user_id=:username:",
            "bind" => array("username" => $username)
        ));

        if (!$user)
        {
            $this->flash->error('Login Failure this page only admin can login');
            return $this->forward('session/adminLogin');
        }

        if (!$this->security->checkHash($password, $user->password))
        {
            $this->flash->error('Login Failure');
            return $this->forward('session/adminLogin');
        }


        $user->last_login = date('Y-m-d H:i:s');
        if (!$user->save())
        {
            $this->dbError($user);
            $this->flash->error('Database Failure');
            return $this->forward('session/adminLogin');
        }

        $this->_registerSession($user);
        $this->flash->success('Login Success');


        return $this->forward('session/adminLogin');
    }

    public function loginAction()
    {
        $request = $this->request;

        $code = $request->getQuery('code');

        $curl = curl_init($this->oauth->token_url);
        curl_setopt($curl, CURLOPT_PORT, 443);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->oauth->client_id . ":" . $this->oauth->client_secret);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, 'Content-Type: application/x-www-from-urlencoded');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $postdata = 'grant_type=authorization_code&code=' . $code;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

        $json_response = curl_exec($curl);

        var_dump($json_response);
        var_dump(curl_error($curl));

        $obj = json_decode($json_response);
        curl_close($curl);

        //access token
        $access_token = $obj->access_token;

        $curl = curl_init($this->oauth->profile_url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, 'Content-Type: application/x-www-from-urlencoded');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $postdata = 'access_token=' . $access_token;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

        $json_response = curl_exec($curl);
        curl_close($curl);

        $obj = json_decode($json_response);

        if ($obj->success == true)
        {
            $username = $obj->username;
            $title = $obj->title;
            $firstname = $obj->firstname;
            $lastname = $obj->lastname;
            $email = $obj->username . '@' . 'psu.ac.th';
            $officename = $obj->officename;
        }
        else
        {
            $this->flashSession->error('Oauth2 error contact admin');
            return $this->response->redirect('session');
        }

        if ($officename != 'ภาควิชาวิศวกรรมคอมพิวเตอร์ คณะวิศวกรรมศาสตร์')
        {
            $this->flashSession->error('Valid only CoE User');
            return $this->response->redirect('session');
        }


        $user = User::findFirst(array(
            "conditions" => "user_id=:username:",
            "bind" => array("username" => $username)
        ));

        if (!$user)
        {
            $user = new User();
            $user->user_id = $username;
            $user->title = $title;
            $user->name = $firstname . ' ' . $lastname;
            $user->email = $email;
            if (preg_match("/^[0-9]/", $user->user_id))
                $user->type = 'Student';
            else
                $user->type = 'Staff';

            if (!$user->save())
            {
                $this->dbError($user);
                $this->flash->error('Database Failure');
                return $this->forward('session');
            }
        }

        $user->name = $firstname . ' ' . $lastname;
        $user->ignoreCheck = true;
        $user->last_login = date('Y-m-d H:i:s');
        if (!$user->save())
        {
            $this->dbError($user);
            $this->flash->error('Database Failure');
            return $this->forward('session');
        }

        $this->_registerSession($user);
        $this->flash->success('Login Success');
        $this->response->redirect('index');
    }

    private function _registerSession($user)
    {
        $view = NULL;
        if ($user->type == 'Admin')
            $view = 'Admin';
        $this->session->set('auth', array(
            'id' => $user->id,
            'user_id' => $user->user_id,
            'title' => $user->title,
            'facebook' => $user->facebook,
            'name' => $user->name,
            'type' => $user->type,
            'login_time' => $user->last_login,
            'view' => $view
        ));
    }

    public function logoutAction()
    {
        $this->session->remove('auth');

        $this->flashSession->success('Logout Success');

        return $this->response->redirect($this->oauth->logout_url . '?redirect_url=' . $this->oauth->app_url);
    }

    private function clearHash()
    {
        $hashLinks = HashLink::find([
            "conditions" => "expire_time <= :now:",
            "bind" => ["now" => date('Y-m-d H:i:s')]
        ]);

        try
        {
            $transaction = $this->transactionManager->get();

            foreach ($hashLinks as $hashLink)
            {
                $hashLink->setTransaction($transaction);
                $hashLink->delete();
            }

            $transaction->commit();
        }
        catch (Failed $e)
        {

        }
    }
}