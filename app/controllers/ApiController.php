<?php

class ApiController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function getStatusProjectFarmAction()
    {
        $this->view->disable();

        $request = $this->request;
        $id = $request->getQuery('id');

        $builder = $this->modelsManager->createBuilder();
        $builder->from("User");
        $builder->where("User.user_id=:id:", array("id" => $id));
        $builder->innerJoin("ProjectMap", "User.id=ProjectMap.user_id");
        $builder->andWhere("ProjectMap.map_type='owner'");
        $builder->innerJoin("Project", "Project.project_id=ProjectMap.project_id");
        $builder->andWhere("Project.project_level_id=3");
        $builder->andWhere("Project.project_farm=1");

        $users = $builder->getQuery()->execute();

        if (count($users))
        {
            echo json_encode(array("status" => true));
        }
        else
        {
            echo json_encode(array("status" => false));
        }
    }
}

?>
