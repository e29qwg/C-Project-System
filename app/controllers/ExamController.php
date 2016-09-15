<?php

class ExamController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();

        $this->loadOwnerProject();

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();
    }

    public function midtermListAction()
    {
        $builder = $this->modelsManager->createBuilder();
        $builder->from(["ProjectMap"]);
        $builder->where("ProjectMap.map_type='owner'");
        $builder->innerJoin("Project", "Project.project_id=ProjectMap.project_id");
        $builder->andWhere("Project.semester_id=:semester_id:", ["semester_id" => $this->current_semester]);
        $builder->innerJoin("User", "User.id=ProjectMap.user_id");
        $builder->orderBy("User.user_id ASC");

        $projectMaps = $builder->getQuery()->execute();

        $lastDate = $this->loadSetting('last_progress_midterm_date');
        $canExamUsers = [];
        $cantExamUsers = [];
        $countProgresss = [];


        foreach ($projectMaps as $projectMap)
        {
            $progresss = Progress::find([
                "conditions" => "user_id=:user_id: AND project_id=:project_id: AND create_date <= :last_date:",
                "bind" => ["user_id" => $projectMap->user_id, "project_id" => $projectMap->project_id, "last_date" => $lastDate]
            ]);

            if (count($progresss) >= 4)
                array_push($canExamUsers, $projectMap->User);
            else
                array_push($cantExamUsers, $projectMap->User);

            $countProgresss[''.$projectMap->user_id] = count($progresss);
        }

        $this->view->setVars([
            'lastDate' => $lastDate,
            'canExamUsers' => $canExamUsers,
            'cantExamUsers' => $cantExamUsers,
            'countProgresss' => $countProgresss
        ]);
    }

    public function finalListAction()
    {

    }

    public function showExamAction()
    {
        $this->downloadAction();
    }

    public function downloadAction()
    {
        if ($this->DownloadFile->download("Exam" . $this->current_semester))
        {
            $this->view->disable();
            return;
        }
        else
            $this->flash->error('ไม่พบข้อมูล');
    }

    public function doUploadAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('exam/manage');
        }

        if ($this->request->hasFiles())
        {
            foreach ($this->request->getUploadedFiles() as $file)
            {
                $examFile = file_get_contents($file->getTempName());
                $name = $file->getName();
                unlink($file->getTempName());
                $excelFile = ExcelFile::findFirst(array(
                    "conditions" => "common_name=:name:",
                    "bind" => array("name" => 'Exam' . $request->getPost('semester_id'))
                ));

                if (!$excelFile)
                {
                    $excelFile = new ExcelFile();
                    $excelFile->common_name = 'Exam' . $request->getPost('semester_id');
                }

                $excelFile->filename = $name;
                $excelFile->file = $examFile;
                $excelFile->user_id = 0;
                $excelFile->public = 1;
                $excelFile->save();

                $this->flashSession->success('อัพโหลดตารางสอบสำเร็จ');
                return $this->response->redirect('exam/manage');
            }
        }

        $this->flashSession->error('ไม่พบไฟล์ที่เลือก');
        return $this->response->redirect('exam/manage');
    }

    public function uploadAction()
    {
        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);
    }

    public function editAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('exam/manage');
        }

        if (!$this->DownloadFile->download("Exam" . $request->getPost('semester_id')))
        {
            $this->flash->error('ไม่พบตารางสอบ');
        }
    }

    public function manageAction()
    {
        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);
    }

    public function generateAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('admin');
        }

        $this->Exam->generateExamTable($request->getPost('semester_id'));
        $this->DownloadFile->download("ExamDraff");
        $this->view->disable();
    }
}


