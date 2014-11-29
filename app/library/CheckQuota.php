<?php

class CheckQuota extends Phalcon\Mvc\User\Component
{
    public function acceptProject($user_id)
    {
        $currentSemester = Semester::maximum(array("column" => "semester_id"));
        $record = $this->modelsManager->createBuilder()
            ->from(array("ProjectMap", "Project"))
            ->where("ProjectMap.user_id='$user_id'")
            ->andWhere("ProjectMap.map_type='advisor'")
            ->andWhere("Project.project_status='Accept'")
            ->andWhere("Project.project_id = ProjectMap.project_id")
            ->andWhere("Project.semester_id='$currentSemester'")
            ->getQuery()
            ->execute();
        return count($record);
    }

    public function pendingProject($user_id)
    {
        $currentSemester = Semester::maximum(array("column" => "semester_id"));
        $record = $this->modelsManager->createBuilder()
            ->from(array("ProjectMap", "Project"))
            ->where("ProjectMap.user_id='$user_id'")
            ->andWhere("ProjectMap.map_type='advisor'")
            ->andWhere("Project.project_status='Pending'")
            ->andWhere("Project.project_id = ProjectMap.project_id")
            ->andWhere("Project.semester_id='$currentSemester'")
            ->getQuery()
            ->execute();
        return count($record);
    }

    public function getLoad($user_id)
    {
        $currentSemester = Semester::maximum(array("column" => "semester_id"));

        $record = $this->modelsManager->createBuilder()
            ->from(array("ProjectMap", "Project"))
            ->where("ProjectMap.user_id='$user_id'")
            ->andWhere("ProjectMap.map_type!='owner'")
            ->andWhere("Project.project_status='Accept'")
            ->andWhere("Project.project_id = ProjectMap.project_id")
            ->andWhere("Project.semester_id='$currentSemester'")
            ->getQuery()
            ->execute();
        return count($record);
    }
}

?>
