<?php

class DownloadFile extends Phalcon\Mvc\User\Component
{
    public function download($commonName)
    {
        $excel = ExcelFile::findFirst("common_name='$commonName'");
        $file = $excel->file;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $excel->filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($file));
        ob_clean();
        flush();
        echo $file;
    }
}

?>
