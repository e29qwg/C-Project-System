<?php

class ProjectsController extends ControllerBase
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

    public function setStatusAction()
    {
        $request = $this->request;

        $id = $request->getPost('id');
        $status = $request->getPost('status');
        $option = $request->getPost('option');

        if (!$this->permission->checkPermission($this->auth['id'], $id))
        {
            $this->flashSession->error('Access denied');
            return $this->_redirectBack();
        }

        $project = Project::findFirst([
            "conditions" => "project_id=:id:",
            "bind" => ["id" => $id]
        ]);

        if (!$project)
        {
            $this->flashSession->error('Project not found');
            return $this->_redirectBack();
        }

        $project->project_status = $status;

        $storeController = new StoreController();
        $bookings = $storeController->getStoreInfo($project->project_id);

        //check pending rent item from store
        if (count($bookings['bookings']) && $status != Project::PROJECT_ACCEPT)
        {
            if (empty($option))
            {
                $this->flash->error('ไม่สามารถเปลี่ยนสถานะได้เนื่องจากนักศึกษายังคืนอุปกรณ์ไม่ครบ');
                return $this->pForward('projects', 'status', [$project->project_id]);
            }

            $project->store_option = $option;
        }

        //remove option for in progress project
        if ($status == Project::PROJECT_ACCEPT)
            $project->store_option = null;

        if ($status == Project::PROJECT_PASS && $project->store_option == PROJECT::STORE_MOVE_TO_ADVISOR)
        {
            if (!$this->moveStoreItem($project, null, true))
            {
                $this->flashSession->error('Error when transaction');
                return $this->_redirectBack();
            }
        }

        if (!$project->save())
        {
            $this->dbError($project);
            return $this->_redirectBack();
        }

        $this->flashSession->success('Update success');
        return $this->response->redirect('projects/status/' . $id);
    }

    public function statusAction()
    {
        $project_id = $this->dispatcher->getParam(0);

        $storeController = new StoreController();
        $bookings = $storeController->getStoreInfo($project_id);

        if (!$this->permission->checkAdvisorPermission($project_id))
        {
            $this->flashSession->error('Access denied');
            return $this->_redirectBack();
        }

        if ($bookings['bookings'])
            $this->view->setVar('pendingRent', true);
    }

    //private call from checkPrerequire
    private function moveStoreItem($oldProject, $newProject, $advisor=false)
    {
        //get pending items
        $store = new StoreController();
        $infos = $store->getStoreInfo($oldProject->project_id);
        $this->dbStore->begin();

        //move to new booking
        if (!$advisor)
        {
            foreach ($infos['bookings'] as $info)
            {
                $info->project_id = $newProject->project_id;
                if (!$info->save())
                {
                    $this->dbStore->rollback();
                    return false;
                }
            }
        }
        //move to advisor
        else
        {
            foreach ($infos['bookings'] as $booking)
            {
                $booking->user_id = $booking->advisor_id;
                $booking->use_for_type = 'etc.';
                if (!$booking->save())
                {
                    $this->dbStore->rollback();
                    return false;
                }
            }
        }

        $this->dbStore->commit();
        return true;
    }

    //private call from acceptAction
    private function checkPrerequire($project, $transaction)
    {
        //prepare project skip check
        if ($project->project_level_id == 1)
            return;

        //get owner id
        $projectMaps = $project->ProjectMap;

        foreach ($projectMaps as $projectMap)
        {
            if ($projectMap->map_type == 'owner')
            {
                $owner_id = $projectMap->user_id;
                break;
            }
        }

        $builder = $this->modelsManager->createBuilder();
        $builder->from("Project");
        $builder->innerJoin("ProjectMap", "Project.project_id=ProjectMap.project_id");
        $builder->where("ProjectMap.map_type='owner'");
        $builder->andWhere("ProjectMap.user_id=:user_id:", ["user_id" => $owner_id]);
        $builder->andWhere("Project.project_id!=:project_id:", ["project_id" => $project->project_id]);
        $builder->orderBy("Project.semester_id DESC");
        $builder->limit(1);

        $records = $builder->getQuery()->execute();
        if (!count($records))
            return;

        $oldProject = $records[0];
        $oldProject->setTransaction($transaction);

        if ($oldProject->project_level_id == $project->project_level_id)
        {
            //if old project not set status auto set to fail
            if ($oldProject->project_status != Project::PROJECT_FAIL && $oldProject->project_status != Project::PROJECT_DROP)
            {
                $oldProject->project_status = Project::PROJECT_FAIL;
                $oldProject->store_option = Project::STORE_IN_NEXT_PROJECT;
            }
        }
        elseif ($oldProject->project_level_id < $project->project_level_id)
        {
            if ($oldProject->project_status != Project::PROJECT_PASS)
            {
                //if accept alert advisor to change status
                if ($oldProject->project_status == Project::PROJECT_ACCEPT)
                {
                    //auto set status
                    $oldProject->project_status = Project::PROJECT_PASS;
                    $oldProject->store_option = Project::STORE_IN_NEXT_PROJECT;
                    $oldProject->save();
                }
                else
                {
                    $link = '<a href="'.$this->url->get().'projects/status/'.$oldProject->project_id.'" target="_blank">click</a>';
                    $transaction->rollback('โครงงานภาคเรียนที่แล้วมีข้อขัดแย้ง ตรวจสอบสถานะโครงงานได้ที่ '. $link);
                }
            }
        }

        //options
        if ($oldProject->store_option == PROJECT::STORE_IN_NEXT_PROJECT)
        {
            if (!$this->moveStoreItem($oldProject, $project))
                $transaction->rollback('เกิดข้อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ');
        }
        elseif ($oldProject->store_option == PROJECT::STORE_MOVE_TO_ADVISOR)
        {
            if (!$this->moveStoreItem($oldProject, $project, true))
                $transaction->rollback('เกิดข้อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ');
        }
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

        if ($this->CheckQuota->acceptProject($user_id, $this->view->getVar('currentSemesterId')) + 1 > $quota->quota_pp)
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

        if (!$this->permission->checkPermission($this->auth['id'], $params[0]))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

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
                $this->checkPrerequire($project, $transaction);

                //save log
                $projectMaps = ProjectMap::find(array(
                    "conditions" => "project_id=:project_id: AND (map_type='advisor' OR map_type='owner')",
                    "bind" => array("project_id" => $params[0])
                ));

                $emails = array();

                foreach ($projectMaps as $projectMap)
                {
                    $log = new Log();
                    $log->setTransaction($transaction);
                    $log->user_id = $projectMap->user_id;

                    if ($projectMap->map_type == 'advisor')
                        $log->description = $user->name . ' ยืนยันโครงงาน ' . $project->project_name;
                    else
                        $log->description = 'โครงงาน ' . $project->project_name . '<font style="color: red"> ได้รับการยืนยันแล้ว</font>';

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

                        //add event log
                        $eventLog = new EventLog();
                        $eventLog->username = $owner->user_id;
                        $eventLog->event = 'success accept project';
                        $eventLog->system = 'project';
                        $eventLog->save();

                        if (!empty($owner->email) && $owner->id != $auth['id'] && $this->wantNotification($owner->id, 'project_update'))
                        {
                            $data = array();
                            $data['to'] = $owner->email;
                            $data['subject'] = 'โครงงาน ' . $project->project_name . ' ได้รับการยืนยัน';
                            $data['mes'] = htmlspecialchars($user->name . ' ยืนยันโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s'));


                            array_push($emails, $data);
                        }
                    }
                }


                //update project
                $project->project_status = 'Accept';
                if (!$project->save())
                    $transaction->rollback('Error when update project');


                //$transaction->rollback('debug');
                $transaction->commit();

                foreach ($emails as $email)
                {
                    $this->sendMail($email['subject'], $email['mes'], $email['to']);
                }
            }
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->setAutoescape(false);
            $this->flash->error('Transaction error: ' . $e->getMessage());
            $this->flash->setAutoescape(true);
            return $this->forward('projects/proposed');
        }

        $this->flash->success('Accept Success');
        return $this->forward('projects/proposed');
    }

    //show propose page
    //reject project

    public function rejectAction()
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $project_id = $this->request->getPost('project_id');
        $reason = $this->request->getPost('reason');

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        if (empty($project_id))
        {
            $this->flash->error('Invalid Request');
            return $this->forward('projects/proposed');
        }

        if (!$this->permission->checkPermission($this->auth['id'], $project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $transaction = $this->transactionManager->get();
        $emails = array();

        try
        {
            $project = Project::findFirst(array(
                "conditions" => "project_id=:project_id:",
                "bind" => array("project_id" => $project_id)
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
                    "bind" => array("project_id" => $project_id)
                ));


                foreach ($projectMaps as $projectMap)
                {
                    $log = new Log();
                    $log->setTransaction($transaction);
                    $log->user_id = $projectMap->user_id;
                    $log->description = $user->name . ' ปฏิเสธโครงงาน ' . $project->project_name;

                    if (!empty($reason))
                        $log->description .= ' (' . $reason . ')';

                    if (!$log->save())
                    {
                        $transaction->rollback('Error when save log');
                    }

                    //send email to owner
                    if ($projectMap->map_type == 'owner')
                    {
                        $owner = User::findFirst(array(
                            "conditions" => "id=:user_id:",
                            "bind" => array("user_id" => $projectMap->user_id)
                        ));

                        if (!$owner)
                        {
                            $transaction->rollback('User not found');
                        }

                        //add event log
                        $eventLog = new EventLog();
                        $eventLog->username = $owner->user_id;
                        $eventLog->event = 'reject reject project';
                        $eventLog->system = 'project';
                        $eventLog->save();

                        if (!empty($owner->email) && $owner->id != $auth['id'] && $this->wantNotification($owner->id, 'project_update'))
                        {
                            $data = array();
                            $data['to'] = $owner->email;
                            $data['subject'] = 'โครงงานถูกปฏิเสธ';
                            $data['mes'] = htmlspecialchars($user->name . ' ปฏิเสธโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s'));

                            if (!empty($reason))
                                $data['mes'] .= htmlspecialchars("\n" . $reason);

                            array_push($emails, $data);
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

            foreach ($emails as $email)
            {
                $this->sendMail($email['subject'], $email['mes'], $email['to']);
            }

        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: ' . $e->getMessage());
            return $this->forward('project/proposed');
        }


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
        $auth = $this->session->get('auth');
        $params = $this->dispatcher->getParams();
        $user_id = $auth['id'];

        if (empty($params[0]) || empty($params[1]))
        {
            $this->flash->error('Invalid Request');
            return $this->forward('projects/me');
        }

        $project = Project::findFirst([
            "conditions" => "project_id=:id:",
            "bind" => ["id" => $params[0]]
        ]);

        if (!$this->permission->checkPermission($this->auth['id'], $project->project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $logUsers = ProjectMap::find([
            "conditions" => "project_id=:id:",
            "bind" => ["id" => $params[0]]
        ]);

        //check user permission
        $projectMap = ProjectMap::findFirst([
            "conditions" => "project_map_id=:id:",
            "bind" => ["id" => $params[1]]
        ]);

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

        if ($project->project_status == 'Accept' && $this->auth['type'] == 'Student')
        {
            $this->flashSession->error('Access Denied: Project already accepted');
            return $this->forward('projects/me');
        }


        $projectMap = ProjectMap::findFirst([
            "conditions" => "project_map_id=:id:",
            "bind" => ["id" => $params[1]]
        ]);

        $user = User::findFirst([
            "conditions" => "id=:id:",
            "bind" => ["id" => $projectMap->user_id]
        ]);

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
            return $this->pForward('projects', 'addmember', array($project_id));
        }

        //check users exists
        $user = User::findFirst([
            "conditions" => "id=:id: AND type='Student'",
            "bind" => ["id" => $member_id]
        ]);

        if (!$user)
        {
            $this->flash->error('User not found');
            return $this->pForward('projects', 'addmember', array($project_id));
        }

        //check project exists
        $project = Project::findFirst([
            "conditions" => "project_id=:id:",
            "bind" => ["id" => $project_id]
        ]);

        if (!$project)
        {
            $this->flash->error('Project not found');
            return $this->forward('projects/me');
        }

        if ($project->project_status == 'Accept')
        {
            $this->flash->error('Access denied: Project already accepted');
            return $this->pForward('projects', 'addmember', array($project_id));
        }

        //check exists new member project
        $projectMaps = ProjectMap::find([
            "conditions" => "user_id=:user_id: AND map_type='owner'",
            "bind" => ["user_id" => $member_id]
        ]);

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
                return $this->pForward('projects', 'addmember', array($project_id));
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
        return $this->pForward('projects', 'member', array($project_id));
    }

    //show member list in current project

    public function addmemberAction()
    {
        $params = $this->dispatcher->getParams();
        if (!$this->permission->checkPermission($this->auth['id'], $params[0]))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }
    }

    //delete project

    public function memberAction()
    {
        $params = $this->dispatcher->getParams();
        if (!$this->permission->checkPermission($this->auth['id'], $params[0]))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $this->loadOwnerProject();

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();
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

        $storeController = new StoreController();
        $bookings = $storeController->getStoreInfo($project->project_id);

        if (count($bookings['bookings']))
        {
            $this->flashSession->error('ไม่สามารถลบโครงงานได้เนื่องจากมีรายการยืมอุปกรณ์');
            return $this->_redirectBack();
        }

        if ($project->project_status == "Accept" && $auth['type'] != 'Advisor' && $auth['type'] != 'Admin')
        {
            $this->flashSession->error('Access Denied: Contact your advisor');
            return $this->_redirectBack();
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
                    if (!empty($user->email) && $user->id != $auth['id'] && $this->wantNotification($user->id, 'project_update'))
                    {
                        $to = $user->email;
                        $subject = 'โครงงาน ' . $project->project_name . ' ถูกลบ';
                        $mes = htmlspecialchars($auth['name'] . ' ได้ลบโครงงาน ' . $project->project_name . ' เวลา ' . date('d-m-Y H:i:s'));
                        $this->sendMail($subject, $mes, $to);
                    }
                }
            }
        }

        $project->delete();

        $this->flash->success('Cancel success');
        return $this->forward('index');
    }

    //show project for user
    public function editSettingAction()
    {
        $request = $this->request;
        $project_id = $request->getPost('id');

        if (!$this->permission->checkPermission($this->auth['id'], $project_id))
        {
            $this->flashSession->error('Access Denied');
            return $this->_redirectBack();
        }

        $project_name = $request->getPost('name');
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
    }

    //add project

    public function meAction()
    {
        if ($this->auth['type'] != 'Student')
            return $this->forward('index');

        /*$this->loadOwnerProject();*/
    }


    public function doNewProjectAction()
    {
        //fetch student
        $student = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $this->auth['id'])
        ));

        if (!$student)
        {
            $this->flash->error('User not found');
            return $this->forward('projects/new');
        }

        //check can create project
        $projectPermission = new ProjectPermission();
        $canCreateProject = $projectPermission->canCreateProject($this->current_semester, $this->auth['id']);


        if (!$canCreateProject)
        {
            $this->flash->error($projectPermission->getErrorMessage());
            return $this->forward('projects/new');
        }

        $request = $this->request;

        $advisor_id = $request->getPost('advisor_id');
        $project_name = $request->getPost('project_name');
        $project_type = $request->getPost('project_type');
        $description = $request->getPost('description');

        \Phalcon\Tag::setDefaults(array(
            'advisor_id' => $advisor_id,
            'project_name' => $project_name,
            'project_type' => $project_type,
            'description' => $description,
        ));


        if (empty($advisor_id) || empty($project_name) || empty($project_type) || empty($description))
        {
            $this->flash->error('Important data missing');
            return $this->forward('projects/new');
        }

        //check advisor quota
        if ($this->permission->quotaAvailable($advisor_id, $this->current_semester) <= 0)
        {
            $this->flash->error('Advisor quota limit exceed');
            return $this->forward('projects/newProject');
        }

        $transaction = $this->transactionManager->get();

        try
        {
            $project = new Project();
            $project->setTransaction($transaction);
            $project->project_name = $project_name;
            $project->project_type = $project_type;
            $project->project_level_id = $projectPermission->getProjectLevel()->project_level_id;
            $project->project_description = $description;
            $project->semester_id = $this->current_semester;

            //save fail
            if (!$project->save())
            {
                $this->dbError($project);
                $transaction->rollback('Error when create project');
            }

            //add owner
            $projectMap = new ProjectMap();
            $projectMap->setTransaction($transaction);
            $projectMap->user_id = $student->id;
            $projectMap->project_id = $project->project_id;
            $projectMap->map_type = 'owner';
            if (!$projectMap->save())
            {
                $transaction->rollback('Error When create project');
            }

            //add advisor
            $projectMap = new ProjectMap();
            $projectMap->setTransaction($transaction);
            $projectMap->user_id = $advisor_id;
            $projectMap->project_id = $project->project_id;
            $projectMap->map_type = 'advisor';
            if (!$projectMap->save())
            {
                $transaction->rollback('Error when create project');
            }

            //insert log to advisor
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $student->id;
            $log->description = 'สร้างโครงงาน ' . $project_name . ' เรียบร้อยแล้ว (รอการยืนยันจากอาจารย์ที่ปรึกษา)';
            if (!$log->save())
            {
                $transaction->rollback('Error when create log');
            }

            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $advisor_id;
            $log->description = $this->auth['name'] . 'ได้สร้างโครงงาน ' . $project_name . ' รอการยืนยัน';
            if (!$log->save())
            {
                $transaction->rollback('Error when create log');
            }

            //send email to advisor
            $advisor = User::findFirst(array(
                "conditions" => "id=:id:",
                "bind" => array("id" => $advisor_id)
            ));

            if (!$advisor)
            {
                $transaction->rollback('Advisor not found');
            }

            if (!empty($advisor->email) && $advisor->active && $advisor->id != $this->auth['id'] && $this->wantNotification($advisor->id, 'project_update'))
            {
                $hashLink = new HashLink();
                $hashLink->setTransaction($transaction);
                $hashLink->user_id = $advisor->id;
                $hashLink->link = 'projects/manage/' . $project->project_id;
                if (!$hashLink->save())
                    $transaction->rollback('Error when create project');

                $to = $advisor->email;
                $subject = "มีโครงงานใหม่ รอการยืนยัน";
                $body = htmlspecialchars($this->auth['name'] . ' ได้สร้างโครงงาน ' . $project_name . ' (รอการยืนยัน) เวลา ' . date('d-m-Y H:i:s'));
                $body .= "<br>";
                $body .= "<a href=\"" . $this->furl . $this->url->get('session/useHash/') . $hashLink->hash . "\">คลิกที่นี่เพื่อดูขอเสนอโครงงาน</a>";
                $body .= "<br>หมายเหตุ ลิงค์นี้ใช้ได้ครั้งเดียวและจะหมดอายุในวันที่ " . $hashLink->expire_time;

                $this->sendMail($subject, $body, $to);
            }

            $transaction->commit();

        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: ' . $e->getMessage());
            return $this->forward('projects/newProject');
        }

        $this->flashSession->success('New project success');
        return $this->response->redirect('index');
    }

    public function newProjectAction()
    {
        //check can create project
        $projectPermission = new ProjectPermission();
        $canCreateProject = $projectPermission->canCreateProject($this->current_semester, $this->auth['id']);

        $this->view->setVar('canCreateProject', $canCreateProject);

        if (empty($canCreateProject))
        {
            $this->flash->error($projectPermission->getErrorMessage());
            return true;
        }


        //set project level
        $this->view->setVar('project_level_name', $projectPermission->getProjectLevel()->project_level_name);
        $this->loadViewAdvisors();
    }


}
