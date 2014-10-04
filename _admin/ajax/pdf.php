<?php
require_once("class.FlipsideTicketDB.php");
require_once("class.FlipsideTicketRequestPDF.php");
require_once("class.FlipJax.php");
class PDFAjax extends FlipJaxSecure
{
    function post_pdf($source)
    {
        $pdf = new FlipsideTicketRequestPDF(FALSE, $source);
        $file_name = $pdf->generatePDF();
        if($file_name == FALSE)
        {
            return array('err_code' => self::INTERNAL_ERROR, 'reason' => "Failed to generate PDF!");
        }
        else
        {
            $file_name = substr($file_name, strpos($file_name, 'tmp/'));
            return array('pdf' => '../'.$file_name);
        }
    }

    function post_save($source)
    {
        FlipsideTicketDB::set_long_text('pdf_source', $source);
        return self::SUCCESS;
    }

    function post($params)
    {
        if(!$this->is_logged_in())
        {
            return array('err_code' => self::ACCESS_DENIED, 'reason' => "Not Logged In!");
        }
        if(isset($params['preview']))
        {
            return $this->post_pdf($params['preview']);
        }
        if(isset($params['save']))
        {
            return $this->post_save($params['save']);
        }
        else
        {
            return self::SUCCESS;
        }
    }
}

$ajax = new PDFAjax();
$ajax->run();
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
