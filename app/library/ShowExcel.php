<?php

class ShowExcel extends Phalcon\Mvc\User\Component
{
    public function show($commonName)
    {
        $excel = ExcelFile::findFirst("common_name='$commonName'");
        $file = $excel->file;
		$file_name = $this->security->getToken().'xlsx';

		file_put_contents('excel/'.$file_name, $file);
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$obj = $objReader->load('excel/'.$file_name);
		$objWriter = PHPExcel_IOFactory::createWriter($obj, 'HTML');
		$objWriter->save('php://output');
		unlink('excel/'.$file_name);
		//TODO

    }
}
?>
