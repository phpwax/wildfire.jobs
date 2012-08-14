<?
class Wildfirejobsnotification extends WaxEmail{

  //$email_template is email content (to, subject, message)
  public function notification($email_template, $data_item){
    $email_template = $this->parse_template($email_template, $data_item);
    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
  }

}
?>