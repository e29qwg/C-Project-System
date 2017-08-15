<?php

class ProjectPermission extends \Phalcon\Mvc\User\Component
{
    private $error_message;
    private $enroll;

    public function canCreateProject($current_semester, $user_id)
    {
        $auth = $this->session->get('auth');

        //get username
        if (empty($user_id))
            $user_id = $auth['id'];

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        $username = $user->user_id;

        if (empty($username))
        {
            $this->error_message = 'ไม่สามารถสร้างโครงงานได้เนื่องจากไม่พบข้อมูลผู้ใช้งานกรุณาติดต่อผู้ดูแลระบบ';
            return false;
        }

        //check enroll

        if (!$this->checkEnroll($current_semester, $username))
            return false;

        //check duplicate project
        if (!$this->checkDuplicateProject($current_semester, $user_id))
            return false;

        return true;
    }

    public function getProjectLevel()
    {
        return $this->enroll->ProjectLevel;
    }

    //return false if duplicate, true if not duplicate
    private function checkDuplicateProject($current_semester, $user_id)
    {
        $project_level = ProjectLevel::findFirst(array(
            "conditions" => "project_level_id=:id:",
            "bind" => array("id" => $this->enroll->project_level_id)
        ));

        if (!$project_level)
        {
            $this->error_message = 'ไม่สามารถสร้างโครงงานได้เนื่องจากข้อผิดพลาดของระบบ กรุณาติดต่อผู้ดูแลระบบ';
            return false;
        }

        $builder = $this->modelsManager->createBuilder();
        $builder->from("Project");
        $builder->where("Project.semester_id=:semester_id:", array("semester_id" => $current_semester));
        $builder->innerJoin("ProjectMap", "ProjectMap.project_id=Project.project_id");
        $builder->andWhere("ProjectMap.user_id=:user_id:", array("user_id" => $user_id));
        $builder->andWhere("ProjectMap.map_type='owner'");

        $projects = $builder->getQuery()->execute();

        if (count($projects))
        {
            $this->error_message = 'ไม่สามารถสร้างโครงงานได้เนื่องจากมีโครงงานอยู่แล้ว';
            return false;
        }

        return true;
    }


    /*
     * return false if not found enroll, true if found
     */
    private function checkEnroll($current_semester, $username)
    {
        $enroll = Enroll::findFirst(array(
            "conditions" => "student_id=:username: AND semester_id=:semester_id:",
            "bind" => array("username" => $username, "semester_id" => $current_semester)
        ));

        if (!$enroll)
        {
            $this->error_message = 'ไม่สามารถสร้างโครงงานได้เนื่องจากไม่พบข้อมูลการลงทะเบียน (อาจจะเกิดจากการลงทะเบียนช้า ให้รอการเพิ่มรายชื่อ)';
            return false;
        }

        $this->enroll = $enroll;
        return true;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }
}