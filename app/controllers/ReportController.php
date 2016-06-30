<?php

class ReportController extends ControllerBase
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

    public function downloadAction()
    {
        $project_id = $this->dispatcher->getParam(0);

        $this->_checkPermission($project_id);

        $fileLocation = __DIR__ . '/../upload/report/report' . $project_id . '.pdf';

        if (!file_exists($fileLocation))
        {
            $this->flashSession->error('File not found');
            return;
        }

        $this->view->disable();
        $file = file_get_contents($fileLocation);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . 'report'.$project_id.'.pdf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($file));
        ob_clean();
        flush();
        echo $file;
    }

    public function indexAction()
    {
        $project_id = $this->dispatcher->getParam(0);

        $this->_checkPermission($project_id);

        $project = Project::findFirst(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project_id)
        ));

        $this->view->project = $project;

        //fetch report
        if (file_exists(__DIR__ . '/../upload/report/report' . $project_id . '.pdf'))
            $this->view->foundReport = true;
        else
            $this->view->foundReport = false;

        $reportComments = ReportComment::find(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $project_id)
        ));

        $this->view->reportComments = $reportComments;
    }

    public function doUploadAction()
    {
        $request = $this->request;
        $project_id = $request->getPost('project_id');

        $this->_checkPermission($project_id);

        if (!$request->hasFiles())
        {
            $this->flashSession->error('File not found');
            return $this->_redirectBack();
        }

        foreach ($request->getUploadedFiles() as $file)
        {
            $type = $file->getType();

            if ($type != 'application/pdf')
            {
                $this->flashSession->error('Invalid file type');
                return $this->_redirectBack();
            }


            $file->moveTo(__DIR__ . '/../upload/report/' . 'report' . $project_id . '.pdf');


            //update comment
            $reportComment = new ReportComment();
            $reportComment->project_id = $project_id;
            $reportComment->user_id = $this->auth['id'];
            $reportComment->comment = $this->auth['name'] . ' อัพโหลดรายงาน';
            $reportComment->status = 'Pending';
            $reportComment->save();

            //mail to advisor

            $this->flashSession->success('Upload success');
            return $this->response->redirect('report/index/' . $project_id);
        }

        $this->flashSession->error('Error when upload');
        return $this->_redirectBack();
    }

    public function uploadAction()
    {
    }
}

?>
