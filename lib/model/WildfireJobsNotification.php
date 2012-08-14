<?
class Wildfirejobsnotification extends WaxEmail{

  //$email_template is email content (subject, message)
  public function notification($email_template, $data_item, $recipient, $bcc){

    $email_template = $this->parse_template($email_template, $data_item);
    $email_template = $this->parse_template($email_template, $recipient, "person_");
    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
    $this->email_template = $email_template;
    $this->to = $recipient->email;
    foreach((array)$bcc as $email) $this->add_bcc_address($email);
  }

  protected function parse_template($template, $data_item, $prefix=""){
    $email_cols = array_keys($template->columns);
    $data_cols = array_keys($data_item->columns);
    foreach($email_cols as $ecol){
      if($ecol != "id"){
        foreach($data_cols as $dcol){
          $col = $data_item->get_col($dcol);
          if($dcol != "id" && !$col->is_association) $template->$ecol = str_replace("%".$prefix.$dcol."%", $data_item->$dcol, $template->$ecol);
        }
      }
    }
    return $template;
  }
}
?>