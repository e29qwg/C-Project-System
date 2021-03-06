<?php

class ProgressTask extends \Phalcon\Cli\Task
{
    public function notificationAction()
    {
        $progressQueue = $this->queue;

        $progressQueue->choose($this->config->queue->progresstube);
        $progressQueue->watch($this->config->queue->progresstube);


        while (true)
        {
            echo "Waiting for a Job...\n";

            $job = $progressQueue->reserve();

            if (!$job)
            {
                echo 'Invalid job found.';
                exit;
            }

            $body = $job->getBody();
            $this->checkProgress($body['progress_id']);
            $job->delete();
        }
    }

    private function checkProgress($progress_id)
    {
        echo "Do job progress id " . $progress_id . "\n";

        $this->db->connect();

        $progress = Progress::findFirst(array(
            "conditions" => "progress_id=:progress_id:",
            "bind" => array("progress_id" => $progress_id)
        ));

        if (!$progress)
            return;

        $progresss = Progress::find(array(
            "conditions" => "project_id=:project_id:",
            "bind" => array("project_id" => $progress->project_id),
            "order" => "create_date DESC"
        ));

        $progress = $progresss[0];
        $user = $progress->User;
        $project = $progress->Project;

        $last_date = $progress->create_date;
        $next_date = new DateTime($last_date);
        $next_date->modify('next monday');

        if ($next_date->format('Y-m-d H:i:s') < date('Y-m-d H:i:s'))
        {
            //send notification
            if (empty($user->email) || $user->active == '0')
            {
                $this->db->close();
                return;
            }

            $subject = "แจ้งเตือนการบันทึกความก้าวหน้าโครงงาน ".$project->project_name;
            $mes = "โครงงาน ".$project->project_name."สามารถบันทึกความก้าวหน้าได้แล้ว";
            $to = $user->email;

            $this->sendMail($subject, $mes, $to);
        }

        $this->db->close();
    }

    private function sendMail($subject, $message, $to)
    {
        if (empty($to))
            return;

        $this->queue->choose($this->config->queue->tube);
        $this->queue->put(array(
            'from' => 'CoE-Project',
            'send_to' => $to,
            'subject' => $subject,
            'message' => $message
        ));
    }
}