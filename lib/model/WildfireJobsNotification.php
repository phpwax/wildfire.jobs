<?
class Wildfirejobsnotification extends WaxEmail{

  //$email_template is email content (to, subject, message)
  public function notification($email_template, $data_item){
    $email_template = $this->parse_template($email_template, $data_item);
    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
    $this->template = $email_template;
  }

  protected function parse_template($template, $data_item){
    $email_cols = array_keys($template->columns);
    $data_cols = array_keys($data_item->columns);
    foreach($email_cols as $ecol) foreach($data_cols as $dcol) str_replace("%".$dcol."%", $data_item->$dcol, $template->$ecol);
    return $template;
  }
}
?>