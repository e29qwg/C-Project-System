<?php

class MainTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $queue = $this->queue;
        $queue->choose($this->projecttube);
        $queue->watch($this->projecttube);
        while (true)
        {
            echo "Waiting for a job...\n";
            $job = $queue->reserve();

            if (!$job)
            {
                echo 'Invalid job found.';
            }
            else
            {
                $body = $job->getBody();
                $sendMail = SendEmail::findFirst(array(
                    "conditions" => "id=:id:",
                    "bind" => array("id" => $body)
                ));

                if ($sendMail)
                {
                    echo "Process email id ".$sendMail->id."\n";
                    $mail = $this->mail;
                    $mail->ClearAllRecipients();
                    $mail->Subject  = $sendMail->subject;
                    $mail->Body     =  $sendMail->body;
                    $mail->CharSet = 'UTF-8';
                    $mail->AddAddress($sendMail->to);
                    if (!$mail->Send())
                        continue;
                    $sendMail->delete();
                }

                $job->delete();
            }
        }
    }
}

?>