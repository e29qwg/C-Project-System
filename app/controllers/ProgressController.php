<?php

class ProgressController extends ControllerBase
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

    public function exportPDFAction()
    {
        ini_set('max_execution_time', 300);


        $project_id = $this->dispatcher->getParam(0);

        if (empty($project_id))
        {
            $this->flash->error('Invalid Request');
            return;
        }

        $progresss = Progress::find(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project_id)
        ));

        try
        {
            $html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(25.4, 12.7, 12.7, 12.7));
            $html2pdf->pdf->SetDisplayMode('fullpage');

            $count = 1;

            foreach ($progresss as $progress)
            {
                $txt = '<h1 style="text-align: center;">ใบรายงานความก้าวหน้าครั้งที่ ' . $count++ . '</h1>';
                $txt .= '<h4>งานที่ทำเสร็จเรียบร้อยแล้ว</h4>';
                $txt .= $progress->progress_finish;
                $txt .= '<h4>งานที่อยู่ระหว่างดำเนินการ</h4>';
                $txt .= $progress->progress_working;
                $txt .= '<h4>งานที่ยังไม่ได้นำเนินการ</h4>';
                $txt .= $progress->progress_todo;
                $txt .= '<h4>สรุปผลการดำเนินการและปัญหาที่เกิดขึ้น</h4>';
                $txt .= $progress->progress_summary;
                $txt .= '<h4>เป้าหมายที่วางไว้เพื่อประเมินความสำเร็จในครั้งต่อไป</h4>';
                $txt .= $progress->progress_target;
                $txt .= '<h4>วันที่บันทึก</h4>';
                $txt .= $progress->create_date;
                if (!empty($progress->edit_date))
                {
                    $txt .= '<h4>วันที่แก้ไข</h4>';
                    $txt .= $progress->edit_date;
                }

                $progressEvaluate = ProgressEvaluate::findFirst(array(
                    "conditions" => "progress_id=:progress_id:",
                    "bind" => array("progress_id" => $progress->progress_id)
                ));

                if ($progressEvaluate)
                {
                    if (!empty($progressEvaluate->evaluation))
                    {
                        $txt .= '<h4>ผลการประเมิน</h4>';
                        if ($progressEvaluate->evaluation == '1')
                            $txt .= 'ต้องปรับปรุง';
                        else if ($progressEvaluate->evaluation == '2')
                            $txt .= 'พอใช้';
                        else if ($progressEvaluate->evaluation == '3')
                            $txt .= 'ดี';
                    }

                    if (!empty($progressEvaluate->comment))
                    {
                        $txt .= '<h4>ความคิดเห็นอาจารย์ที่ปรึกษา</h4>';
                        $txt .= $progressEvaluate->comment;
                    }
                }

                $txt .= '<page_footer>Create by CoE-Project Exported date: ' . date('Y-m-d H:i:s') . '</page_footer>';
                $html2pdf->writeHTML('<page style="font-family: freeserif; font-size:16px;">' . $txt . '</page>');
            }

            $content = $html2pdf->Output('utf8.pdf', true);

            $this->response->setContent($content);
            $this->response->setHeader('Content-Type', 'application/force-download');
            $this->response->setHeader('Content-Disposition', 'attachment;filename=progress.pdf');
            $this->response->send();

            $this->view->disable();
        }
        catch (HTML2PDF_exception $e)
        {
            echo $e;
        }

        ini_set('max_execution_time', 30);
    }

    public function doEditAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return;
        }


        $id = $request->getPost('id');
        $progress_id = $request->getPost('progress_id');
        $progress_finish = $request->getPost('progress_finish');
        $progress_working = $request->getPost('progress_working');
        $progress_todo = $request->getPost('progress_todo');
        $progress_summary = $request->getPost('progress_summary');
        $progress_target = $request->getPost('progress_target');

        $progress = Progress::findFirst("progress_id='$progress_id'");

        //check user permission
        $projectMap = ProjectMap::findFirst("project_id='$progress->project_id' AND user_id='$user_id'");

        if (!(($projectMap->map_type == 'owner' && $progress->user_id == $user_id) || $projectMap->map_type = 'advisor'))
        {
            $this->flash->error('Access Denied');
            return;
        }

        $progress->progress_finish = $progress_finish;
        $progress->progress_working = $progress_working;
        $progress->progress_todo = $progress_todo;
        $progress->progress_summary = $progress_summary;
        $progress->progress_target = $progress_target;

        if (!$progress->save())
        {
            $this->flash->error('DB Error: When update progress');
            return;
        }

        $this->flash->success('Update Success');

        return $this->dispatcher->forward(array(
            'controller' => 'progress',
            'action' => 'index',
            'params' => array($id)
        ));
    }

    public function editAction()
    {
        $progress_id = $this->dispatcher->getParam(1);

        $progress = Progress::findFirst(array(
            "conditions" => "progress_id=:progress_id:",
            "bind" => array("progress_id" => $progress_id)
        ));

        if (!$this->permission->checkPermission($this->auth['id'], $progress->project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $this->view->progress = $progress;
    }

    public function doEvaluateAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');
        $progress_id = $request->getPost('progress_id');
        $evaluate = $request->getPost('evaluate');
        $comment = $request->getPost('comment');

        $transaction = $this->transactionManager->get();
        $emails = array();

        try
        {
            $progress = Progress::findFirst(array(
                "conditions" => "progress_id=:progress_id:",
                "bind" => array("progress_id" => $progress_id)
            ));

            $progress->setTransaction($transaction);

            $project_id = $progress->project_id;

            //check is advisor in this project
            if (!$this->_checkAdvisorPermission($project_id))
                return false;

            $progressEvaluate = ProgressEvaluate::findFirst(array(
                "conditions" => "progress_id=:progress_id:",
                "bind" => array("progress_id" => $progress_id)
            ));

            $progressEvaluate->setTransaction($transaction);

            if ($progressEvaluate)
            {
                $progressEvaluate->evaluation = $evaluate;
                $progressEvaluate->comment = $comment;
                if (!$progressEvaluate->save())
                {
                    $transaction->rollback("Error when save to database");
                }
            }
            else
            {
                $transaction->rollback("Data not found");
            }

            //fetch user
            $owner = User::findFirst(array(
                "conditions" => "id=:id:",
                "bind" => array("id" => $progress->user_id)
            ));

            //fetch project owner
            if ($owner)
            {
                if (!empty($owner->email) && $owner->id != $auth['id'] && $this->wantNotification($owner->id, 'progress_update'))
                {
                    $to = $owner->email;
                    $subject = 'Your progress has been evaluate';
                    $mes = htmlspecialchars('มีการเปลี่ยนแปลงผลการประเมินของใบรายงานความก้าวหน้าโครงงาน เวลา ' . date('d-m-Y H:i:s'));

                    $data = array();
                    $data['to'] = $to;
                    $data['subject'] = $subject;
                    $data['mes'] = $mes;

                    array_push($emails, $data);
                }
            }

            $transaction->commit();

            //put to beanstalkd
            foreach ($emails as $email)
            {
                $this->sendMail($email['subject'], $email['mes'], $email['to']);
            }

        }
        catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flashSession->error('Transaction failure: ' . $e->getMessage());
            return $this->pForward('progress', 'evaluate', array($project_id));
        }

        $this->flashSession->success("Update evaluation success");
        return $this->response->redirect("progress/evaluate/" . $project_id);
    }

    //insert evaluate to database

    public function evaluateAction()
    {
        $params = $this->dispatcher->getParams();
        if (!$this->_checkAdvisorPermission($params[0]))
            return;

        $project_id = $params[0];

        $project = Project::findFirst(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project_id)
        ));


        $nonEvalProgresss = array();
        $evaledProgresss = array();

        foreach ($project->Progress as $progress)
        {
            $progressEvaluate = ProgressEvaluate::findFirst(array(
                "progress_id=:progress_id:",
                "bind" => array("progress_id" => $progress->progress_id)
            ));

            if ($progressEvaluate)
            {
                if ($progressEvaluate->evaluation == 0)
                    array_push($nonEvalProgresss, $progress);
                else
                    array_push($evaledProgresss, $progress);
            }
        }

        $this->view->setVar('nonEvalProgresss', $nonEvalProgresss);
        $this->view->setVar('evaledProgresss', $evaledProgresss);
        $this->view->project = $project;
    }

    //show evaluate page for advisor

    public function deleteAction()
    {
        $auth = $this->session->get('auth');
        $type = $auth['type'];
        $user_id = $auth['id'];
        $params = $this->dispatcher->getParams();

        $progress_id = $params[1];

        $progress = Progress::findFirst("progress_id='$progress_id'");
        $projectMap = ProjectMap::findFirst("project_id='$progress->project_id' AND user_id='$user_id'");

        if (!$progress || !$projectMap)
        {
            $this->flash->error('Access Denied');
            return;
        }

        if (!(($projectMap->map_type == 'owner' && $progress->user_id == $user_id) || $projectMap->map_type = 'advisor'))
        {
            $this->flash->error('Access Denied');
            return;
        }

        if (!$progress)
        {
            $this->flash->error('Access Denied');
            return $this->forward('projects/me');
        }

        $progress->delete();


        $this->flashSession->success('Delete Success');


        if ($type != 'Student')
            return $this->response->redirect('progress/evaluate/' . $params[0]);

        return $this->response->redirect('progress/index/' . $params[0]);
    }

    //delete progress

    public function viewAction()
    {
        $progress_id = $this->dispatcher->getParam(1);

        $progress = Progress::findFirst(array(
            "conditions" => "progress_id=:progress_id:",
            "bind" => array("progress_id" => $progress_id)
        ));

        if (!$this->permission->checkPermission($this->auth['id'], $progress->project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        if ($progress->user_id != $this->auth['id'] && $this->auth['type'] == 'Student')
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $this->view->progress = $progress;
    }

    //view progress

    public function doAddProgressAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];
        $type = $auth['type'];

        $project_id = $request->getPost('id');
        if (!$this->permission->checkPermission($this->auth['user_id'], $project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        //check time pass
        if (!$this->permission->canAddNewProgress($user_id, $project_id))
        {
            $this->flash->error('Miss time conditions');
            return $this->pForward('progress', 'progress', array($project_id));
        }

        //insert progress
        $project = Project::findFirst("project_id='$project_id'");
        $user = User::findFirst("id='$user_id'");

        $progress_finish = $request->getPost('progress_finish');
        $progress_working = $request->getPost('progress_working');
        $progress_todo = $request->getPost('progress_todo');
        $progress_summary = $request->getPost('progress_summary');
        $progress_target = $request->getPost('progress_target');

        $progress = new Progress();
        $progress->project_id = $project_id;
        $progress->user_id = $auth['id'];
        $progress->progress_finish = $progress_finish;
        $progress->progress_working = $progress_working;
        $progress->progress_todo = $progress_todo;
        $progress->progress_summary = $progress_summary;
        $progress->progress_target = $progress_target;


        if (!$progress->save())
        {
            $this->dbError($progress);
            $this->flash->error('Database Failure');

            return $this->pForward('progress', 'newProgress', array($project_id));
        }

        $evaluate = new ProgressEvaluate();
        $evaluate->progress_id = $progress->progress_id;
        $evaluate->evaluation = '0';
        if (!$evaluate->save())
        {
            $progress->delete();
            $this->flash->error('Database Failure');

            return $this->pForward('progress', 'newProgress', array($project_id));
        }

        $this->flashSession->success('Add progress success');

        $projectMap = ProjectMap::findFirst("project_id='$project_id' AND map_type='advisor'");

        //sent notification to advisor
        $log = new Log();
        $log->user_id = $projectMap->user_id;
        $log->description = $user->title . ' ' . $user->name . ' ได้บันทึกความก้าวหน้าโครงงาน ' . $project->project_name;
        $log->save();

        //send email to advisor
        $advisor = User::findFIrst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $projectMap->user_id)
        ));

        if ($advisor)
        {
            if (!empty($advisor->email) && $advisor->id != $auth['id'] && $this->wantNotification($advisor->id, 'progress_update'))
            {
                $hashLink = new HashLink();
                $hashLink->user_id = $advisor->id;
                $hashLink->hash = \Phalcon\Text::random(Phalcon\Text::RANDOM_ALNUM, 20);
                $hashLink->link = '/progress/view/' . $project_id . '/' . $progress->progress_id;
                $hashLink->save();

                $to = $advisor->email;
                $subject = 'มีรายงานความก้าวหน้าโครงงาน ' . $project->project_name;
                $mes = htmlspecialchars($user->title . ' ' . $user->name . ' ได้บันทึกความก้าวหน้าโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s'));
                $mes .= '<br>';
                $mes .= "<a href=\"" . $this->furl . $this->url->get('session/useHash/') . $hashLink->hash . "\">คลิกที่นี่เพื่อดู</a>";

                $this->sendMail($subject, $mes, $to);
            }
        }

        if ($type != 'Student')
            return $this->response->redirect('progress/evaluate/' . $project_id);

        return $this->response->redirect('progress/index/' . $project_id);
    }

    //add progress to db

    public function newProgressAction()
    {
        $params = $this->dispatcher->getParams();

        if (!$this->permission->checkPermission($this->auth['id'], $params[0]))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }


        //$this->permission
        if (!$this->permission->canAddNewProgress($this->auth['id'], $params[0]))
        {
            $this->flash->error('Missing time conditions');
            return $this->pForward('progress', 'index', array($params[0]));
        }
    }

    //show add progress page

    public function indexAction()
    {
        $auth = $this->session->get('auth');

        $params = $this->dispatcher->getParams();
        if (!$this->permission->checkPermission($this->auth['id'], $params[0]))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        if ($auth['type'] != 'Student')
        {
            $this->dispatcher->forward(array(
                'controller' => 'progress',
                'action' => 'evaluate',
                'params' => array($params[0])
            ));

            return;
        }

        $progresss = Progress::find(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $params[0]),
            "order" => "create_date ASC"
        ));

        $this->view->progresss = $progresss;


        if (count($progresss))
        {
            //check available time for new progress
            $last_date = $progresss[count($progresss) - 1]->create_date;
            $next_date = strtotime("+7 day", DateTime::createFromFormat('Y-m-d H:i:s', $last_date)->getTimestamp());
            $this->view->next_date = $next_date;
        }
    }

    //show progress page

    private function toUTF8($str)
    {
        return iconv('UTF-8', 'TIS-620', $str);
    }
}

?>
