<?php

use Phalcon\Mvc\Model\Transaction\Failed;

class RoomController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();

        $this->view->p1_start = $this->p1_start;
        $this->view->p2_start = $this->p2_start;
    }

    public function viewOnlyAction()
    {
        $availableSeats = Room::find("status='available'");

        $this->view->availableSeats = $availableSeats;
    }

    public function confirmSeatAction()
    {
        $status = $this->checkStatus();

        if ($status != 'selectSeat')
        {
            $this->flashSession->error('Access denied');
            return $this->_redirectBack();
        }

        $seat_name = $this->dispatcher->getParam(0);

        try
        {
            $this->db->begin();

            $seat = Room::findFirst([
                "conditions" => "text=:seat_name: AND status='available'",
                "bind" => ["seat_name" => $seat_name],
                "for_update" => true
            ]);

            if (!$seat)
                throw new Failed('Seat not available');

            $seat->status = 'in_use';
            $seat->user_id = $this->auth['id'];

            if (!$seat->save())
            {
                $this->dbError($seat);
                throw new Failed('Error when confirm seat');
            }

            $projectMap = $this->getProjectMap();

            if (empty($projectMap) || $projectMap == null)
            {
                throw new Failed('Invalid project conditions');
            }

            $roomMap = RoomMap::findFirst([
                "conditions" => "user_id=:user_id: AND project_id=:project_id: and status='accept'",
                "bind" => ["user_id" => $this->auth['id'], "project_id" => $projectMap->project_id],
                "for_update" => true
            ]);

            if (!$roomMap)
                throw new Failed("Conditions error");

            $roomMap->status = 'wait';

            if (!$roomMap->save())
            {
                $this->dbError($roomMap);
                throw new Failed('Error when confirm seat');
            }

            //add log to owner
            $log = new Log();
            $log->user_id = $this->auth['id'];
            $log->description = 'ยืนยันการเลือกที่นั่ง '.$seat_name.' แล้ว รอการติดต่อจากเจ้าหน้าที่';

            if (!$log->save())
            {
                $this->dbError($log);
                throw new Failed('Error when confirm seat');
            }

            //mail to staff
            $subject = 'ยืนยันที่นั่งห้องโครงงาน ('.$this->auth['user_id'].')';
            $mes = $subject.' เวลา '.date('Y-m-d H:i:s');
            $to = 'sakarin@coe.phuket.psu.ac.th';

            $this->sendMail($subject, $mes, $to);

            $this->db->commit();
        }
        catch (Failed $e)
        {
            $this->flashSession->error('Transaction failure: ' . $e->getMessage());
            return $this->_redirectBack();
        }

        $this->flashSession->success('Confirm seat success');
        return $this->response->redirect('room');
    }

    public function selectSeatAction()
    {
        $status = $this->checkStatus();

        if ($status != 'selectSeat')
        {
            $this->flashSession->error('Access denied');
            return $this->_redirectBack();
        }
    }

    public function acceptAction()
    {
        $id = $this->dispatcher->getParam(0);

        try
        {
            $transaction = $this->transactionManager->get();

            $roomMap = RoomMap::findFirst([
                "conditions" => "id=:id:",
                "bind" => ["id" => $id]
            ]);
            $roomMap->setTransaction($transaction);

            if (!$roomMap)
            {
                $transaction->rollback('Data not found');
            }

            $projectMaps = $roomMap->Project->ProjectMap;
            $owner = $roomMap->User;
            $found = false;

            foreach ($projectMaps as $projectMap)
            {
                if ($projectMap->map_type == 'advisor' && $projectMap->user_id == $this->auth['id'])
                {
                    $found = true;
                    break;
                }
            }

            if ($found)
            {
                $roomMap->status = 'accept';
                if (!$roomMap->save())
                {
                    $transaction->rollback('Error when accept request');
                }
            }
            else
            {
                $transaction->rollback('Access denied');
            }

            //save log
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $owner->id;
            $log->description = 'คำขอใช้ห้องโครงงานได้รับการอนุมัติ';

            if (!$log->save())
            {
                $transaction->rollback('Error when accept request');
            }

            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $this->auth['id'];
            $log->description = 'อนุมัติคำขอใช้ห้องโครงงาน ' . $roomMap->Project->project_name;

            if (!$log->save())
            {
                $transaction->rollback('Error when accept request');
            }

            $rooms = Room::find("status='available'");

            if (!count($rooms))
            {
                $transaction->rollback('ไม่มีที่นั่งเหลือในห้องโครงงาน');
            }

            //send email to owner
            if (!empty($owner->email) && $owner->active == 1)
            {
                $to = $owner->email;
                $subject = "คำขอใช้ห้องโครงงานได้รับการอนุมัติ (เลือกที่นั่งได้)";
                $mes = $subject . ' เวลา ' . date('Y-m-d H:i:s');

                $this->sendMail($subject, $mes, $to);
            }

            $transaction->commit();
        }
        catch (Failed $e)
        {
            $this->flashSession->error('Transaction failure: ' . $e->getMessage());
            return $this->_redirectBack();
        }

        $this->flashSession->success('อนุมัติเรียบร้อยแล้ว');
        return $this->_redirectBack();
    }

    public function rejectAction()
    {
        $id = $this->dispatcher->getParam(0);

        try
        {
            $transaction = $this->transactionManager->get();

            $roomMap = RoomMap::findFirst([
                "conditions" => "id=:id:",
                "bind" => ["id" => $id]
            ]);
            $roomMap->setTransaction($transaction);

            if (!$roomMap)
            {
                $transaction->rollback('Data not found');
            }

            $projectMaps = $roomMap->Project->ProjectMap;
            $owner = $roomMap->User;
            $found = false;

            foreach ($projectMaps as $projectMap)
            {
                if ($projectMap->map_type == 'advisor' && $projectMap->user_id == $this->auth['id'])
                {
                    $found = true;
                    break;
                }
            }

            if ($found)
            {
                if (!$roomMap->delete())
                {
                    $transaction->rollback('Error when reject request');
                }
            }
            else
            {
                $transaction->rollback('Access denied');
            }

            //save log
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $owner->id;
            $log->description = 'คำขอใช้ห้องโครงงานถูกปฏิเสธ';

            if (!$log->save())
            {
                $transaction->rollback('Error when reject request');
            }

            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $this->auth['id'];
            $log->description = 'ปฏิเสธคำขอใช้ห้องโครงงาน ' . $roomMap->Project->project_name;

            if (!$log->save())
            {
                $transaction->rollback('Error when reject request');
            }

            //send email to owner
            if (!empty($owner->email) && $owner->active == 1)
            {
                $to = $owner->email;
                $subject = "คำขอใช้ห้องโครงงานถูกปฏิเสธ";
                $mes = $subject . ' เวลา ' . date('Y-m-d H:i:s');

                $this->sendMail($subject, $mes, $to);
            }

            $transaction->commit();
        }
        catch (Failed $e)
        {
            $this->flashSession->error('Transaction failure: ' . $e->getMessage());
            return $this->_redirectBack();
        }

        $this->flashSession->success('ปฏิเสธเรียบร้อยแล้ว');
        return $this->_redirectBack();
    }

    public function proposedAction()
    {
        $user_id = $this->auth['id'];

        $builder = $this->modelsManager->createBuilder();
        $builder->from("RoomMap");
        $builder->where("RoomMap.status='pending'");
        $builder->innerJoin("ProjectMap", "RoomMap.project_id=ProjectMap.project_id");
        $builder->andWhere("ProjectMap.user_id=:user_id:", ["user_id" => $user_id]);
        $builder->andWhere("ProjectMap.map_type='advisor'");
        $builder->innerJoin("Project", "RoomMap.project_id=Project.project_id");
        $builder->andWhere("Project.semester_id=:semester_id:", ["semester_id" => $this->current_semester]);

        $roomRequests = $builder->getQuery()->execute();

        $this->view->roomRequests = $roomRequests;
    }

    public function createNewRequestAction()
    {
        $auth = $this->auth;
        $status = $this->checkStatus();

        if ($status != 'newRequest')
        {
            $this->flashSession->error('Invalid Request');
            return $this->_redirectBack();
        }

        $projectMap = $this->getProjectMap();
        $user_id = $this->auth['id'];

        try
        {
            $transaction = $this->transactionManager->get();

            $roomMap = new RoomMap();
            $roomMap->setTransaction($transaction);
            $roomMap->project_id = $projectMap->project_id;
            $roomMap->user_id = $user_id;
            $roomMap->status = 'pending';

            if (!$roomMap->save())
            {
                $this->dbError($roomMap);
                $transaction->rollback('Error when create request');
            }

            //add to user log
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $auth['id'];
            $log->description = 'ส่งคำขออนุญาตใช้ห้องไปยังอาจารย์ที่ปรึกษา (รอการอนุมัติ)';
            if (!$log->save())
            {
                $this->dbError($log);
                $transaction->rollback('Error when create request');
            }

            //mail to advisor
            $advisorMap = ProjectMap::findFirst([
                "conditions" => "project_id=:project_id: and map_type='advisor'",
                "bind" => ["project_id" => $projectMap->project_id]
            ]);

            if (!$advisorMap)
            {
                $transaction->rollback('Cannot find advisor');
            }

            //add to advisor log
            $log = new Log();
            $log->setTransaction($transaction);
            $log->user_id = $advisorMap->user_id;
            $log->description = $auth['title'] . $auth['name'] . ' (' . $auth['user_id'] . ') ขอใช้ห้องโครงงาน ในการทำโครงงาน ' . $projectMap->Project->project_name . ' รอการอนุมัติจากอาจารย์ที่ปรึกษา';
            if (!$log->save())
            {
                $this->dbError($log);
                $transaction->rollback('Error when create request');
            }

            if (!empty($advisorMap->User->email))
            {
                $subject = $auth['title'] . $auth['name'] . ' (' . $auth['user_id'] . ') ขอใช้ห้องโครงงาน';
                $mes = $subject . 'ในการทำโครงงาน ' . $projectMap->Project->project_name . ' รอการอนุมัติจากอาจารย์ที่ปรึกษา เวลา ' . date('Y-m-d H:i:s');
                $to = $advisorMap->User->email;

                $this->sendMail($subject, $mes, $to);
            }

            $transaction->commit();
        }
        catch (Failed $e)
        {
            $this->flashSession->error('Transaction failure: ' . $e->getMessage());
            return $this->_redirectBack();
        }

        $this->flashSession->success('ส่งคำขอใช้ห้องสำเร็จ');
        return $this->response->redirect('room/index');
    }

    public function newRequestAction()
    {
        $status = $this->checkStatus();

        if ($status != 'newRequest')
        {
            $this->flashSession->error('Invalid Request');
            return $this->_redirectBack();
        }
    }

    public function indexAction()
    {
        $this->checkStatus();
    }

    protected function getProjectMap()
    {
        $user_id = $this->auth['id'];

        $builder = $this->modelsManager->createBuilder();
        $builder->from("ProjectMap");
        $builder->where("ProjectMap.user_id=:user_id:", ["user_id" => $user_id]);
        $builder->andWhere("ProjectMap.map_type='owner'");
        $builder->innerJoin("Project", "ProjectMap.project_id=Project.project_id");
        $builder->andWhere("Project.project_status='accept'");
        $builder->andWhere("Project.semester_id=:semester_id:", ["semester_id" => $this->current_semester]);

        $projectMaps = $builder->getQuery()->execute();

        if (!count($projectMaps))
        {
            return null;
        }

        return $projectMaps[0];
    }

    protected function checkStatus()
    {
        $rooms = Room::find("status='available'");

        $this->view->status = null;

        if (!count($rooms))
        {
            $this->view->current_status = 'ที่ว่างเต็ม';
            $this->view->status_type = 'error';
            return;
        }

        $user_id = $this->auth['id'];
        $projectMap = $this->getProjectMap();

        if (empty($projectMap) || $projectMap == null)
        {
            $this->view->current_status = 'ไม่พบข้อมูลโครงงานที่ยืนยันแล้ว (ไม่มีสิทธิ์ขอใช้ห้อง)';
            $this->view->status_type = 'error';
            return;
        }

        //check date project pp
        if ($projectMap->Project->project_level_id == '1')
        {
            $this->view->current_status = 'Project Prepare ไม่มีสิทธิ์ใช้ห้อง';
            $this->view->status_type = 'error';
            return;
        }
        //check date project 1
        else if ($projectMap->Project->project_level_id == '2')
        {
            if ($this->p1_start > date('Y-m-d H:i:s'))
            {
                $this->view->current_status = 'Project I เริ่มขอใช้ห้องได้ในวันที่ ' . $this->p1_start;
                $this->view->status_type = 'error';
                return;
            }
        }
        else if ($projectMap->Project->project_level_id == '3')
        {
            if ($this->p2_start > date('Y-m-d H:i:s'))
            {
                $this->view->current_status = 'Project II เริ่มขอใช้ห้องได้ในวันที่ ' . $this->p2_start;
                $this->view->status_type = 'error';
                return;
            }
        }

        $project_id = $projectMap->Project->project_id;

        //pass date condition
        //check request
        $roomMap = RoomMap::findFirst([
            "conditions" => "project_id=:project_id: AND user_id=:user_id:",
            "bind" => ["project_id" => $project_id, "user_id" => $user_id]
        ]);

        //can request
        if (!$roomMap)
        {
            $this->view->current_status = 'สามารถขอใช้ห้องได้';
            $this->view->status_type = 'warning';
            $this->view->status = 'can_request';
            return 'newRequest';
        }

        if ($roomMap->status == 'reject')
        {
            $this->view->current_status = 'สามารถขอใช้ห้องได้';
            $this->view->status_type = 'warning';
            $this->view->status = 'can_request';
            return 'newRequest';
        }

        $this->view->status = $roomMap->status;

        if ($roomMap->status == 'pending')
        {
            $this->view->current_status = 'รอการอนุมัติจาก อาจารย์ที่ปรึกษา';
            $this->view->status_type = 'warning';
            return;
        }

        if ($roomMap->status == 'accept')
        {
            $this->view->current_status = 'รอการเลือกที่นั่ง';
            $this->view->status_type = 'warning';
            $this->view->status = 'can_select_seat';
            return 'selectSeat';
        }

        if ($roomMap->status == 'wait')
        {
            $this->view->current_status = 'เลือกที่นั่งแล้ว รอการยืนยันจากเจ้าหน้าที่';
            $this->view->status_type = 'warning';
            return;
        }

        if ($roomMap->status == 'finish')
        {
            $this->view->current_status = 'ดำเนินการเรียบร้อยแล้ว';
            $this->view->status_type = 'success';
            return;
        }
    }
}
