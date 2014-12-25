<?php

class ProjectsController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    //confirm project
    public function acceptAction()
    {
        $params = $this->dispatcher->getParams();
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        $quota = Quota::findFirst(array(
            "conditions" => "advisor_id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        if ($this->CheckQuota->acceptProject($user_id) + 1 > $quota->quota_pp)
        {
            $this->flash->error('ไม่สามารถเพิ่มได้เนื่องจากเกินจำนวนที่อาจารย์ที่ปรึกษาจะรับได้');
            if ($auth['type'] != 'Student')
                return $this->forward('index');

            return $this->forward('projects/newProject');
        }

        if (empty($params[0]))
        {
            $this->flash->error('Invalid Request');
            return $this->forward('projects/proposed');
        }

        if (!$this->_checkPermission($params[0]))
            return false;

        $transaction = $this->transactionManager->get();

        try
        {
            $project = Project::findFirst(array(
                "conditions" => "project_id=:project_id: AND project_status='Pending'",
                "bind" => array("project_id" => $params[0])
            ));

            $project->setTransaction($transaction);

            if ($project)
            {
                //save log
                $projectMaps = ProjectMap::find(array(
                    "conditions" => "project_id=:project_id:",
                    "bind" => array("project_id" => $params[0])
                ));
                $sendEmailIds = array();

                foreach ($projectMaps as $projectMap)
                {
                    $log = new Log();
                    $log->user_id = $projectMap->user_id;
                    $log->description = $user->name . ' ยืนยันโครงงาน ' . $project->project_name;
                    if (!$log->save())
                        $transaction->rollback('Error when notification');

                    //send email to owner
                    if ($projectMap->map_type == 'owner')
                    {
                        $owner = User::findFirst(array(
                            "conditions" => "id=:user_id:",
                            "bind" => array("user_id" => $projectMap->user_id)
                        ));

                        if (!$owner)
                            $transaction->rollback('Error when send email');

                        if (!empty($owner->email))
                        {
                            $sendEmail = new SendEmail();
                            $sendEmail->setTransaction($transaction);
                            $sendEmail->to = $owner->email;
                            $sendEmail->subject = 'โครงงาน ' . $project->project_name . ' ได้รับการยืนยัน';
                            $sendEmail->body = $user->name . ' ยืนยันโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s');
                            if (!$sendEmail->save())
                                $transaction->rollback('Error when send email');

                            array_push($sendEmailIds, $sendEmail->id);
                        }
                    }
                }

                //update project
                $project->project_status = 'Accept';
                if (!$project->save())
                    $transaction->rollback('Error when update project');

                $transaction->commit();

                $this->_createScore($projectMaps, $project);
                $this->_updateWorkLoad($user, $project, NULL);

                foreach ($sendEmailIds as $sendEmailId)
                {
                    $this->queue->choose($this->projecttube);
                    $this->queue->put($sendEmailId);
                }
            }
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction error: ' . $e->getMessage());
            return $this->forward('projects/proposed');
        }

        $this->flash->success('Accept Success');
        return $this->forward('projects/proposed');
    }

    private function _createScore($projectMaps, $project)
    {
        $currentSemester = Semester::maximum(array("column" => "semester_id"));
        if ($project->semester_id != $currentSemester)
            return;

        foreach ($projectMaps as $projectMap)
        {
            if ($projectMap->map_type != "owner")
                continue;

            for ($i = 0; $i < 2; $i++)
            {
                if ($project->project_level_id == 1)
                    $score = new ScorePrepare();
                else
                    $score = new ScoreProject();
                $score->user_id = $projectMap->user_id;
                $score->project_id = $project->project_id;
                if ($i == 0)
                    $score->is_midterm = 1;
                else
                    $score->is_midterm = 0;
                $score->save();
            }
        }
    }

    //update work load and add advisor if latest semester
    private function _updateWorkLoad($advisor, $project, $deCoAdvisor)
    {
        $currentSemester = Semester::maximum(array("column" => "semester_id"));
        if ($project->semester_id != $currentSemester)
            return;

        $projectLevel = ProjectLevel::findFirst("project_level_id='$project->project_level_id'");
        $ncoadvisor = $projectLevel->coadvisor;

        if ($project->project_level_id > 1)
            $ncoadvisor = 0;

        //TODO
        $ncoadvisor = 0;

        $coadvisors = User::find(array(
            "conditions" => "advisor_group='$advisor->advisor_group' AND id!='$advisor->id' AND type='Advisor'",
            "limit" => $ncoadvisor,
            "order" => "work_load ASC"
        ));

        foreach ($coadvisors as $coadvisor)
        {
            //add to map
            $projectMap = new ProjectMap();
            $projectMap->user_id = $coadvisor->id;
            $projectMap->project_id = $project->project_id;
            $projectMap->map_type = 'coadvisor';
            $projectMap->save();
        }

        $this->_updateWork();
    }

    private function _updateWork()
    {
        $works = User::find("type='Advisor'");
        $currentSemesterId = $this->view->getVar('currentSemesterId');
        foreach ($works as $work)
        {
            $work->work_load = $this->CheckQuota->getLoad($work->id, $currentSemesterId);
            $work->save();
        }
    }

    //show propose page
    //reject project

    public function rejectAction()
    {
        $params = $this->dispatcher->getParams();
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];
        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        if (empty($params[0]))
        {
            $this->flash->error('Invalid Request');
            return $this->forward('projects/proposed');
        }

        if (!$this->_checkPermission($params[0]))
            return false;

        $transaction = $this->transactionManager->get();

        try
        {

            $project = Project::findFirst(array(
                "conditions" => "project_id=:project_id:",
                "bind" => array("project_id" => $params[0])
            ));

            $project->setTransaction($transaction);

            if ($project)
            {
                //check project accepted
                if ($project->project_status == 'Accept' && $auth['type'] != 'Advisor')
                {
                    $this->flash->error('Access Denied: Contact your advisor');
                    return $this->forward('index');
                }

                //save log
                $projectMaps = ProjectMap::find(array(
                    "conditions" => "project_id=:project_id: AND (map_type='owner' OR map_type='advisor')",
                    "bind" => array("project_id" => $params[0])
                ));


                foreach ($projectMaps as $projectMap)
                {
                    $log = new Log();
                    $log->setTransaction($transaction);
                    $log->user_id = $projectMap->user_id;
                    $log->description = $user->name . ' ปฏิเสธโครงงาน ' . $project->project_name;

                    if (!$log->save())
                    {
                        $transaction->rollback('Error when save log');
                    }

                    //send email to advisor
                    if ($projectMap->map_type = 'owner')
                    {
                        $advisor = User::findFirst(array(
                            "conditions" => "id=:user_id:",
                            "bind" => array("user_id" => $projectMap->user_id)
                        ));

                        if (!$advisor)
                        {
                            $transaction->rollback('User not found');
                        }

                        if (!empty($advisor->email))
                        {
                            $sendEmail = new SendEmail();
                            $sendEmail->setTransaction($transaction);
                            $sendEmail->to = $advisor->email;
                            $sendEmail->subject = 'โครงงานถูกปฏิเสธ';
                            $sendEmail->body = $user->name . ' ปฏิเสธโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s');
                            if (!$sendEmail->save())
                            {
                                $transaction->rollback('Error when send email');
                            }
                        }
                    }
                }
            }

            //delete project
            if (!$project->delete())
            {
                $transaction->rollback("Error when save project");
            }

            $transaction->commit();

            $this->queue->choose($this->projecttube);
            $this->queue->put($sendEmail->id);
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: ' . $e->getMessage());
            return $this->forward('project/proposed');
        }

        $this->_updateWork();

        $this->flashSession->success('Reject Success');
        return $this->response->redirect('projects/proposed');
    }

    //delete member from current project

    public function proposedAction()
    {
    }

    //add project member

    public function deletememberAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');
        $params = $this->dispatcher->getParams();
        $user_id = $auth['id'];

        if (empty($params[0]) || empty($params[1]))
        {
            $this->flash->error('Invalid Request');
            return $this->forward('projects/me');
        }

        $project = Project::findFirst("project_id='$params[0]'");

        if (!$this->_checkPermission($project->project_id))
            return false;

        $logUsers = ProjectMap::find("project_id='$params[0]'");

        //check user permission
        $projectMap = ProjectMap::findFirst("project_map_id='$params[1]'");

        if (!$projectMap || !$project)
        {
            $this->flash->error('Access Denied');
            return $this->forward('projects/me');
        }

        if ($projectMap->user_id == $user_id)
        {
            $this->flash->error('Access Denied');
            return $this->forward('projects/me');
        }

        if ($project->project_status == 'Accept')
        {
            $this->flash->error('Access Denied: Project already accepted');
            return $this->forward('projects/me');
        }

        $projectMap = ProjectMap::findFirst("project_map_id='$params[1]'");
        $user = User::findFirst("id='$projectMap->user_id'");
        $projectMap->delete();

        //log
        foreach ($logUsers as $logUser)
        {
            $log = new Log();
            $log->user_id = $logUser->user_id;
            $log->description = $auth['name'] . ' ได้ลบ ' . $user->name . ' ออกจากโครงงาน ' . $project->project_name;
            $log->save();
        }

        $this->flashSession->success('Delete member success');
        return $this->response->redirect('projects/member/' . $params[0]);
    }

    //show add member form

    public function doAddMemberAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');

        $user_id = $auth['id'];
        $project_id = $request->getPost('pid');
        $member_id = $request->getPost('id');

        if (empty($project_id) || empty($member_id))
        {
            $this->flash->error('User not found');
            return $this->dispatcher->forward(array(
                'controller' => 'projects',
                'action' => 'addmember',
                'params' => array($project_id)
            ));
        }

        //check users exists
        $user = User::findFirst("id='$member_id' AND type='Student'");

        if (!$user)
        {
            $this->flash->error('User not found');
            return $this->dispatcher->forward(array(
                'controller' => 'projects',
                'action' => 'addmember',
                'params' => array($project_id)
            ));
        }

        //check project exists
        $project = Project::findFirst("project_id='$project_id'");
        if (!$project)
        {
            $this->flash->error('Project not found');
            return $this->forward('projects/me');
        }

        if ($project->project_status == 'Accept')
        {
            $this->flash->error('Access denied: Project already accepted');
            return $this->dispatcher->forward(array(
                'controller' => 'projects',
                'action' => 'addmember',
                'params' => array($project_id)
            ));
        }

        //check exists new member project
        $projectMaps = ProjectMap::find("user_id='$member_id' AND map_type='owner'");
        $member_project_ids = array();
        foreach ($projectMaps as $projectMap)
        {
            array_push($member_project_ids, $projectMap->project_id);
        }

        if (count($member_project_ids))
        {
            $records = $this->modelsManager->createBuilder();
            $records->from(array("Project"));
            $records->inWhere("Project.project_id", $member_project_ids);
            $records->andWhere("Project.semester_id='$project->semester_id'");
            $records = $records->getQuery()->execute();
        }

        if (count($member_project_ids))
        {
            foreach ($records as $record)
            {
                $this->flash->error('User has only one project');
                return $this->dispatcher->forward(array(
                    'controller' => 'projects',
                    'action' => 'addmember',
                    'params' => array($project_id)
                ));
            }
        }

        //check owner
        $projectMap = ProjectMap::findFirst("project_id='$project_id' AND user_id='$user_id'");

        if (!$projectMap)
        {
            $this->flash->error('Access denied');
            return $this->forward('projects/me');
        }

        //check exists owner in current project
        $projectMap = ProjectMap::findFirst("project_id='$project_id' AND user_id='$member_id'");

        if (!$projectMap)
        {
            $projectMap = new ProjectMap();
            $projectMap->user_id = $member_id;
            $projectMap->project_id = $project_id;
            $projectMap->map_type = 'owner';
            $projectMap->save();

            //insert log

            $projectMaps = ProjectMap::find("project_id='$project_id'");
            foreach ($projectMaps as $projectMap)
            {
                $log = new Log();
                $log->user_id = $projectMap->user_id;
                $log->description = $auth['name'] . ' ได้เพิ่ม ' . $user->name . ' ในโครงงาน ' . $project->project_name;
                $log->save();
            }
        }

        $this->flash->success('Add member success');
        return $this->dispatcher->forward(array(
            'controller' => 'projects',
            'action' => 'member',
            'params' => array($project_id)
        ));
    }

    //show member list in current project

    public function addmemberAction()
    {
        $params = $this->dispatcher->getParams();
        $this->_checkPermission($params[0]);
    }

    //delete project

    public function memberAction()
    {
        $params = $this->dispatcher->getParams();
        $this->_checkPermission($params[0]);
    }

    //show edit form

    public function deleteAction()
    {
        $params = $this->dispatcher->getParams();

        $auth = $this->session->get('auth');
        $user_id = $auth['id'];
        $pid = $this->request->getPost('pid');
        $comment = $this->request->getPost('comment');

        if (empty($pid))
        {
            if (!empty($params[0]))
                $pid = $params[0];
            else
            {
                $this->flash->error('Invalid Request');
                return $this->forward('projects/me');
            }
        }

        //check owner
        $projectMap = ProjectMap::findFirst(array(
            "conditions" => "project_id=:project_id: AND user_id=:user_id:",
            "bind" => array("project_id" => $pid, "user_id" => $user_id)
        ));

        if (!$projectMap)
        {
            $this->flash->error('Access Denied');
            return $this->forward('projects/me');
        }

        $project = Project::findFirst(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $pid)
        ));

        if ($project->project_status == "Accept" && $auth['type'] != 'Advisor' && $auth['type'] != 'Admin')
        {
            $this->flash->error('Access Denied: Contact your advisor');
            return;
        }

        $projectMaps = ProjectMap::find(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project->project_id)
        ));

        foreach ($projectMaps as $projectMap)
        {
            $log = new Log();
            $log->user_id = $projectMap->user_id;
            $log->description = $auth['name'] . ' ได้ลบโครงงาน ' . $project->project_name;
            if (!empty($comment))
                $log->description .= $log->description . ' ( ' . $comment . ' ) ';
            $log->save();

            //send email notification to advisor or owner
            if (($projectMap->map_type == 'owner' || $projectMap->map_type == 'advisor') && $projectMap->user_id != $user_id)
            {
                $user = User::findFirst(array(
                    "conditions" => "id=:user_id:",
                    "bind" => array("user_id" => $projectMap->user_id)
                ));

                if ($user)
                {
                    if (!empty($user->email))
                    {
                        $sendEmail = new SendEmail();
                        $sendEmail->to = $user->email;
                        $sendEmail->subject = 'โครงงาน ' . $project->project_name . ' ถูกลบ';
                        $sendEmail->body = $auth['name'] . ' ได้ลบโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s');
                        if ($sendEmail->save())
                        {
                            $this->queue->choose($this->projecttube);
                            $this->queue->put($sendEmail->id);
                        }
                    }
                }
            }
        }

        $project->delete();
        $this->_updateWork();

        $this->flash->success('Cancel success');
        return $this->forward('index');
    }

    //show project for user
    public function editSettingAction()
    {
        $request = $this->request;
        $project_id = $request->getPost('id');
        $this->_checkPermission($project_id);

        $project_name = $request->getPost('project_name');
        $project_type = $request->getPost('project_type');
        $description = $request->getPost('description');

        if (empty($project_name) || empty($project_type))
        {
            $this->flashSession->error('Important data is missing.');
            return $this->response->redirect('projects/manage/' . $project_id);
        }

        $project = Project::findFirst("project_id='$project_id'");
        $project->project_name = $project_name;
        $project->project_type = $project_type;
        $project->project_description = $description;
        $project->save();

        $this->flashSession->success('Edit Success');
        return $this->response->redirect('projects/manage/' . $project_id);
    }

    public function manageAction()
    {
        $params = $this->dispatcher->getParams();

        $this->_checkPermission($params[0]);
    }

    //add project

    public function meAction()
    {
    }

    public function doNewProjectAction()
    {
        $auth = $this->session->get('auth');

        $request = $this->request;

        $user_id = $auth['id'];
        $project_name = $request->getPost('name');
        $project_type = $request->getPost('project_type');
        $advisor = $request->getPost('advisor');
        $project_level = $request->getPost('project_level');
        $semester = $request->getPost('semester');
        $description = $request->getPost('description');
        $coadvisor1 = $request->getPost('coadvisor1');
        $coadvisor2 = $request->getPost('coadvisor2');

        if (empty($project_name) || empty($project_type) || empty($advisor) || empty($project_level) || empty($semester))
        {
            $this->flash->error('Importand field are required');
            return $this->forward('projects/newProject');
        }

        $quota = Quota::findFirst("advisor_id='$advisor'");
        if ($this->CheckQuota->acceptProject($advisor) + 1 > $quota->quota_pp)
        {
            $this->flash->error('ไม่สามารถเพิ่มได้เนื่องจากเกินจำนวนที่อาจารย์ที่ปรึกษาจะรับได้');
            return $this->forward('projects/newProject');
        }

        //check project in semester
        $projectMaps = ProjectMap::find("user_id='$user_id'");

        foreach ($projectMaps as $projectMap)
        {
            $project = Project::findFirst("project_id='$projectMap->project_id'");
            if ($semester == $project->semester_id)
            {
                $this->flashSession->error('Project duplicate');
                return $this->forward('projects/newProject');
            }
        }

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        //check enroll student
        $enroll = Enroll::findFirst(array(
            "conditions" => "student_id LIKE :user_id: AND semester_id=:semester_id: AND project_level_id=:project_level_id:",
            "bind" => array(
                "user_id" => "%" . $user->user_id . "%",
                "semester_id" => $semester,
                "project_level_id" => $project_level
            )
        ));

        //TODO
        /* if (!$enroll)
         {
             $this->flash->error('ข้อมูลไม่ตรงกับที่ลงทะเบียน');
             return $this->forward('projects/newProject');
         }*/

        $transaction = $this->transactionManager->get();

        try
        {
            $project = new Project();
            $project->setTransaction($transaction);
            $project->project_name = $project_name;
            $project->project_type = $project_type;
            $project->project_level_id = $project_level;
            $project->project_description = $description;
            $project->semester_id = $semester;

            //save fail
            if (!$project->save())
            {
                $transaction->rollback('Error when create project');
            }

            //add owner
            $projectMap = new ProjectMap();
            $projectMap->setTransaction($transaction);
            $projectMap->user_id = $user_id;
            $projectMap->project_id = $project->project_id;
            $projectMap->map_type = 'owner';
            if (!$projectMap->save())
            {
                $transaction->rollback('Error When create project');
            }

            //add advisor
            $projectMap = new ProjectMap();
            $projectMap->setTransaction($transaction);
            $projectMap->user_id = $advisor;
            $projectMap->project_id = $project->project_id;
            $projectMap->map_type = 'advisor';
            if (!$projectMap->save())
            {
                $transaction->rollback('Error when create project');
            }

            if ($project_level != 1)
            {
                if (!empty($coadvisor1))
                {
                    $projectMap = new ProjectMap();
                    $projectMap->setTransaction($transaction);
                    $projectMap->user_id = $coadvisor1;
                    $projectMap->project_id = $project->project_id;
                    $projectMap->map_type = 'coadvisor';
                    if (!$projectMap->save())
                    {
                        $transaction->rollback('Error when create project');
                    }
                }

                if (!empty($coadvisor2))
                {
                    $projectMap = new ProjectMap();
                    $projectMap->setTransaction($transaction);
                    $projectMap->user_id = $coadvisor2;
                    $projectMap->project_id = $project->project_id;
                    $projectMap->map_type = 'coadvisor';
                    if (!$projectMap->save())
                    {
                        $transaction->rollback('Error when create project');
                    }
                }
            }

            //insert log to advisor
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $user_id;
            $log->description = 'สร้างโครงงาน ' . $project_name . ' เรียบร้อยแล้ว (รอการยืนยันจากอาจารย์ที่ปรึกษา)';
            if (!$log->save())
            {
                $transaction->rollback('Error when create log');
            }

            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $advisor;
            $log->description = $auth['name'] . 'ได้สร้างโครงงาน ' . $project_name . ' รอการยืนยัน';
            if (!$log->save())
            {
                $transaction->rollback('Error when create log');
            }

            //send email to advisor
            $oadvisor = User::findFirst(array(
                "conditions" => "id=:id:",
                "bind" => array("id" => $advisor)
            ));

            if (!$oadvisor)
            {
                $transaction->rollback('Advisor not found');
            }

            if (!empty($oadvisor->email))
            {
                $sendEmail = new SendEmail();
                $sendEmail->setTransaction($transaction);
                $sendEmail->to = $oadvisor->email;
                $sendEmail->subject = "มีโครงงานใหม่";
                $sendEmail->body = $auth['name'] . ' ได้สร้างโครงงาน ' . $project_name . ' (รอการยืนยัน) เวลา ' . date('d-m-Y H:i:s');
                if (!$sendEmail->save())
                    $transaction->rollback('Error when send email');
            }

            $transaction->commit();

            $this->queue->choose($this->projecttube);
            $this->queue->put($sendEmail->id);
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: ' . $e->getMessage());
            return $this->forward('projects/newProject');
        }

        $this->flash->success('New project success');
        return $this->response->redirect('index');
    }

    public function newProjectAction()
    {
        $this->_getAllSemester();
        $this->_updateWork();
    }
}

?>