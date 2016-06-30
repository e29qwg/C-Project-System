<?php


class Permission extends \Phalcon\Mvc\User\Component
{
    public function canAddNewProgress($user_id, $project_id)
    {
        if (empty($user_id) || empty($project_id))
            return false;

        $progresss = Progress::find(array(
            "conditions" => "project_id=:project_id: AND user_id=:user_id:",
            "bind" => array("project_id" => $project_id, "user_id" => $user_id),
            "order" => "create_date DESC"
        ));

        if (!count($progresss))
            return true;

        $last_date = $progresss[0]->create_date;
        $next_date = date('Y-m-d H:i:s', strtotime("+7 day", DateTime::createFromFormat('Y-m-d H:i:s', $last_date)->getTimestamp()));

        if (date('Y-m-d H:i:s') >= $next_date)
            return true;

        return false;
    }

    public function quotaAvailable($advisor_id, $current_semester)
    {
        $quota = Quota::findFirst(array(
            "conditions" => "advisor_id=:advisor_id:",
            "bind" => array("advisor_id" => $advisor_id)
        ));

        if (!$quota)
            return 0;

        $ac = $this->CheckQuota->acceptProject($advisor_id, $current_semester);

        return $quota->quota_pp - $ac;
    }

    public function canCreateProject($current_semester, $user_id)
    {
        $auth = $this->session->get('auth');
        if (empty($user_id))
            $user_id = $auth['id'];

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        $username = $user->user_id;
        
        if (empty($username))
           return null;

        //check enroll
        $enroll = Enroll::findFirst(array(
            "conditions" => "student_id=:username: AND semester_id=:semester_id:",
            "bind" => array("username" => $username, "semester_id" => $current_semester)
        ));

        if (!$enroll)
            return null;

        $project_level = ProjectLevel::findFirst(array(
            "conditions" => "project_level_id=:id:",
            "bind" => array("id" => $enroll->project_level_id)
        ));

        if (!$project_level)
            return null;

        $builder = $this->modelsManager->createBuilder();
        $builder->from("Project");
        $builder->where("Project.semester_id=:semester_id:", array("semester_id" => $current_semester));
        $builder->innerJoin("ProjectMap", "ProjectMap.project_id=Project.project_id");
        $builder->andWhere("ProjectMap.user_id=:user_id:", array("user_id" => $user_id));
        $builder->andWhere("ProjectMap.map_type='owner'");

        $projects = $builder->getQuery()->execute();


        if (count($projects))
            return null;

        return $project_level;
    }

    public function getRole($id)
    {
        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $id)
        ));

        $auth =  $this->session->get('auth');

        if ($auth)
        {
            if ($auth['type'] == 'Admin')
                return 'Admin';
        }

        if (!$user)
            return 'Guest';

        if (!$user->isComplete())
            return 'Incomplete';

        return $user->type;
    }
}