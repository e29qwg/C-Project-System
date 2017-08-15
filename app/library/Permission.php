<?php

class Permission extends \Phalcon\Mvc\User\Component
{
    //check for owner and advisor
    public function canManageProgress($user_id, $progress_id)
    {
        if (empty($user_id) || empty($progress_id))
            return false;

        $progress = Progress::findFirst(array(
            "conditions" => "progress_id=:progress_id:",
            "bind" => array("progress_id" => $progress_id)
        ));

        if (!$this->checkPermission($user_id, $progress->project_id))
            return false;
        if ($progress->user_id != $user_id && $this->getRole($user_id) != 'Admin' && $this->getRole($user_id) != 'Advisor')
            return false;

        return true;
    }

    //check can access project
    public function checkPermission($user_id, $project_id)
    {
        $auth = $this->session->get('auth');

        $projectMap = ProjectMap::findFirst(array(
            "conditions" => "project_id=:project_id: AND user_id=:user_id:",
            "bind" => array("project_id" => $project_id, "user_id" => $user_id)
        ));

        if (!$projectMap || empty($project_id))
        {
            if (!empty($project_id) && $auth['type'] == 'Admin')
                return true;

            return false;
        }
        return true;
    }

    public function checkAdvisorPermission($project_id)
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $projectMap = ProjectMap::findFirst([
            "conditions" => "user_id=:user_id: AND project_id=:project_id: AND map_type='advisor'",
            "bind" => ["user_id" => $user_id, "project_id" => $project_id]
        ]);

        if (!$projectMap || empty($project_id))
            return false;

        return true;
    }

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
        $next_date = new DateTime($last_date);
        $next_date->modify('next monday');

        if (date('Y-m-d H:i:s') >= $next_date->format('Y-m-d H:i:s'))
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



    public function getRole($id)
    {
        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $id)
        ));

        $auth = $this->session->get('auth');

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