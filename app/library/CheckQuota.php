<?php

class CheckQuota extends Phalcon\Mvc\User\Component
{
    public function acceptProject($user_id, $semester_id = "")
    {
        $record = $this->modelsManager->createBuilder();
        $record->from(array(
            "ProjectMap",
            "Project"
        ));
        $record->where("ProjectMap.user_id=:user_id:", array("user_id" => $user_id));
        $record->andWhere("ProjectMap.map_type='advisor'");
        $record->andWhere("Project.project_status='Accept'");
        $record->andWhere("Project.project_id = ProjectMap.project_id");
        $record->andWhere("Project.semester_id=:current_semester:", array("current_semester" => $semester_id));
        $record = $record->getQuery()->execute();
        return count($record);
    }

    public function pendingProject($user_id, $semester_id = "")
    {
        $record = $this->modelsManager->createBuilder();
        $record->from(array(
            "ProjectMap",
            "Project"
        ));
        $record->where("ProjectMap.user_id=:user_id:", array("user_id" => $user_id));
        $record->andWhere("ProjectMap.map_type='advisor'");
        $record->andWhere("Project.project_status='Pending'");
        $record->andWhere("Project.project_id = ProjectMap.project_id");
        $record->andWhere("Project.semester_id=:current_semester:", array("current_semester" => $semester_id));
        $record = $record->getQuery()->execute();
        return count($record);
    }

    public function getLoad($user_id, $semester_id)
    {
        $record = $this->modelsManager->createBuilder();
        $record->from(array(
            "ProjectMap",
            "Project"
        ));
        $record->where("ProjectMap.user_id=:user_id:", array("user_id" => $user_id));
        $record->andWhere("ProjectMap.map_type!='owner'");
        $record->andWhere("Project.project_status='Accept'");
        $record->andWhere("Project.project_id = ProjectMap.project_id");
        $record->andWhere("Project.semester_id=:current_semester:", array("current_semester" => $semester_id));
        $record = $record->getQuery()->execute();
        return count($record);
    }
}

?>
