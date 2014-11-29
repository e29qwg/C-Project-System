<?php

class SettingsController extends ControllerBase
{
    private $auth;

    public function initialize()
    {
        $this->auth = $this->session->get('auth');
        $this->view->setTemplateAfter('adminside');
    }

    public function deleteSemesterAction()
    {

    }

    public function addSemesterAction()
    {
        $request = $this->request;

        if ($request->isPost())
        {
            $term = $request->getPost('term');
            $year = $request->getPost('year');

            if (empty($term) || empty($year))
            {
                $this->flash->error('Invalid data');
                return;
            }

            $semester = new Semester();
            $semester->semester_term = $term;
            $semester->semester_year = $year;

            $semester->save();

            $this->flashSession->success('เพิ่มปีการศึกษาสำเร็จ');
            return $this->response->redirect('settings');
        }
    }

    public function indexAction()
    {
        $settings = Settings::find();

        $this->view->setVar('settings', $settings);

        //set current semester
        $settingCurrentSemester = Settings::findFirst("name='current_semester'");
        if (!$settingCurrentSemester)
            return $this->settingError();

        $currentSemester = Semester::findFirst(array(
                'conditions' => 'semester_id=:semester_id:',
                'bind' => array('semester_id' => $settingCurrentSemester->value)
            ));

        if (!$currentSemester)
            return $this->settingError();

        $this->view->setVar('currentSemester', $currentSemester);

        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);

    }

    private function settingError()
    {
        $this->flash->error('Settings error please check database');
        return $this->forward('admin');
    }

}

?>
