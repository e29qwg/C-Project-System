<?php

class AdminController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function manageCoadvisorAction()
    {
        $this->view->setTemplateAfter('adminside');

        $request = $this->request;

        //post request
        if ($request->isPost())
        {
            $project_ids = $request->getPost('project_id');
            $coadvisors = $request->getPost('coadvisor');

            //TODO change co advisor

            print_r($project_ids);
            echo "<br>";
            print_r($coadvisors);

            $count = 0;

            //TODO optimize
            foreach ($project_ids as $project_id)
            {
                $projectMaps = ProjectMap::find("project_id='$project_id' AND map_type='coadvisor'");

                foreach ($projectMaps as $projectMap)
                {
                    $projectMap->delete();
                }

                for ($i = 0; $i < 2; $i++, $count++)
                {
                    $projectMap = new ProjectMap();
                    $projectMap->user_id = $coadvisors[$count];
                    $projectMap->project_id = $project_id;
                    $projectMap->map_type = 'coadvisor';
                    $projectMap->save();
                }
            }

            $this->flashSession->success('บันทึกสำเร็จ');
            $this->response->redirect('admin/manageCoadvisor');
        }
    }

    public function summaryTopicExportAction()
    {
        //inherit call updatetopic
        $this->DownloadFile->download('Topic');
        $this->view->disable();
    }

    public function advisorProfileAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function summaryTopicAction()
    {
        $currentSemester = Settings::findFirst("name='current_semester'");

        if (!$currentSemester)
        {
            $this->flash->error('setting error');
            return $this->forward('admin');
        }

        $semester = $this->request->getPost('semester');

        if (empty($semester))
        {
            $projects = Project::find(array(
                "conditions" => "semester_id=:semester_id:",
                "bind" => array("semester_id" => $currentSemester->value)
            ));
            $this->view->setVar('semester', $currentSemester->value);
        }
        else
        {
            $projects = Project::find(array(
                "conditions" => "semester_id=:semester_id:",
                "bind" => array("semester_id" => $semester)

            ));
            $this->view->setVar('semester', $semester);
        }

        $this->Topic->updateTopic($projects);
        $this->view->setVar('projects', $projects);

        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);

        $this->view->setTemplateAfter('adminside');
    }


    public function indexAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function setViewAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function changeViewAction()
    {
        $this->view->setTemplateAfter('adminside');
        $auth = $this->session->get('auth');
        $view = $this->request->getPost('view');
        $auth['view'] = $view;
        $this->session->set('auth', $auth);

        if ($view == 'Student')
            return $this->forward('student');

        if ($view == 'Advisor')
            return $this->forward('advisor');

        if ($view == 'Admin')
            return $this->forward('admin');
    }
}

?>
