<?php

class NewsController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function doEditAction()
    {
        $data = $this->request->getPost('news');
        $id = $this->request->getPost('id');

        $news = News::findFirst([
            "conditions" => "id=:id:",
            "bind" => ["id" => $id]
        ]);

        if ($news)
        {
            $news->news = $data;
            if ($news->save())
            {
                $this->flashSession->success('Edit Success');
                return $this->response->redirect('news/manageNews');
            }
        }

        $this->flash->error('Error when edit');
        return $this->forward('news/manageNews');
    }

    public function deleteAction()
    {
        $params = $this->dispatcher->getParams();
        $id = $params[0];

        $news = News::findFirst([
            "conditions" => "id=:id:",
            "bind" => ["id" => $id]
        ]);

        if ($news)
        {
            if ($news->delete())
            {
                $this->flashSession->success('Delete Success');
                return $this->response->redirect('news/manageNews');
            }
        }

        $this->flash->error('Error when delete');
        return $this->forward('news/manageNews');
    }

    public function editAction()
    {
    }

    public function doCreateAction()
    {
        $data = $this->request->getPost('news');

        if (empty($data))
        {
            $this->flash->error('Empty data');
            return $this->forward('news/manageNews');
        }

        $news = new News();

        $news->news = $data;
        $news->save();

        $this->flashSession->success('Create news success');
        return $this->response->redirect('news/manageNews');
    }

    public function createAction()
    {
    }

    public function manageNewsAction()
    {
    }

    public function indexAction()
    {
    }
}

?>
