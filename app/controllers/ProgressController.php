<?php
    
class ProgressController extends ControllerBase 
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    //insert evaluate to database
    public function doEvaluateAction()
    {
        $request = $this->request;
        $project_ids = array_unique($this->request->getPost("project_id"));
        
        if (count($project_ids) != 1)
        {
            $this->flash->error('Access Denied.');
            return $this->forward('index');
        }
        
        $project_id = $project_ids[0];

        //check is advisor in this project
        if (!$this->_checkAdvisorPermission($project_id))
            return false; 
        
        $progress_ids = $request->getPost("progress_id");
        $evaluates = $request->getPost("evaluate");

        for ($i = 0 ; $i < count($progress_ids) ; $i++)
        {
            $progress_id = $progress_ids[$i];
            $progressEvaluate = ProgressEvaluate::findFirst("progress_id='$progress_id'");
            
            if ($progressEvaluate)
            {
                $progressEvaluate->evaluation = $evaluates[$i];
                $progressEvaluate->save();
            }
        }

        $this->flashSession->success("Update evaluation success");
        return $this->response->redirect("progress/evaluate/".$project_id);
    }

    //show evaluate page for advisor
    public function evaluateAction()
    {   
        $params = $this->dispatcher->getParams();
        return $this->_checkAdvisorPermission($params[0]);
    }

    //delete progress
    public function deleteAction()
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];
        $params = $this->dispatcher->getParams();

        $progress_id = $params[1];

        $progress = Progress::findFirst("progress_id='$progress_id' AND user_id='$user_id'");

        if (!$progress)
        {
            $this->flash->error('Access Denied');
            return $this->forward('projects/me');
        }

        $progress->delete();
        
        $this->flashSession->success('Delete Success');
        return $this->response->redirect('progress/index/'.$params[0]);
    }

    //view progress
    public function viewAction()
    {
    }
    
    //add progress to db
    public function doAddProgressAction()
    {
        $request = $this->request;
        $auth = $this->session->get('auth');
        
        $project_id = $request->getPost('id');
        if (!$this->_checkPermission($project_id))
            return false;

        $progress_finish = $request->getPost('progress_finish');
        $progress_working = $request->getPost('progress_working');
        $progress_todo = $request->getPost('progress_todo');
        $progress_summary = $request->getPost('progress_summary');
        $progress_target = $request->getPost('progress_target');

        $progress = new Progress();
        $progress->project_id = $project_id;
        $progress->user_id = $auth['id'];
        $progress->progress_finish = $progress_finish;
        $progress->progress_working = $progress_working;
        $progress->progress_todo = $progress_todo;
        $progress->progress_summary = $progress_summary;
        $progress->progress_target = $progress_target;

        if (!$progress->save())
        {
            $this->flash->error('Database Failure');
            return $this->dispatcher->forward(array(
                'controller' => 'progress',
                'action' => 'newProgress',
                'params' => array($project_id)
            ));
        }

        $evaluate = new ProgressEvaluate();
        $evaluate->progress_id = $progress->progress_id;
        $evaluate->evaluation = '0';
        if (!$evaluate->save())
        {
            $progress->delete();
            $this->flash->error('Database Failure');
            return $this->dispatcher->forward(array(
                'controller' => 'progress',
                'action' => 'newProgress',
                'params' => array($project_id)
            ));
        }

        $this->flashSession->success('Add progress success');
        $this->response->redirect('progress/index/'.$project_id);
    }

    //show add progress page
    public function newProgressAction()
    {
        $params = $this->dispatcher->getParams();
        $this->_checkPermission($params[0]);
    }

    //show progress page
    public function indexAction()
    {
        $params = $this->dispatcher->getParams();
        $this->_checkPermission($params[0]);
    }
}
?>
