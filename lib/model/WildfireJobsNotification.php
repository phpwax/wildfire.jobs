<?
class Wildfirejobsnotification extends WaxEmail{

  public static $dev_emails = array();

  public function application_complete($job, $applicant, $from, $to=false){
    $this->from = $from;
    $this->subject = "Job application recieved [".$job->title."]";
    $this->job = $job;
    $this->applicant = $applicant;
    if(!$to) $this->to = $job->send_email_to;
    else $this->to = $to;
    foreach((array)Meeting::$dev_emails as $email) $this->add_bcc_address($email);
  }


  public function notification($email_template, $data_item, $recipient, $bcc=false){
    if($data_item){
      $email_template = $this->parse_template($email_template, $data_item);
      $email_template = $this->parse_template($email_template, $recipient, "person_");
    }
    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
    $this->email_template = $email_template;
    $this->to = $recipient->email;
    foreach((array)Wildfirejobsnotification::$dev_emails as $email) $this->add_bcc_address($email);
    //add file attachments
    if($media = $email_template->media) foreach($media as $file) $this->AddAttachment(PUBLIC_DIR.$file->permalink(false), $file->title);
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