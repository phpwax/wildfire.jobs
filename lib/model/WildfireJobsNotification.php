<?
class WildfireJobsNotification extends WaxEmail{

  public static $dev_emails = array();

  public function application_complete($job, $applicant, $from){
    $template = $job->received_application_template;
    $this->from = $template->from_email;
    $this->from_name = $job->from_name;
    $this->subject = "Job application recieved [".$job->title."]";
    $this->job = $job;
    $this->applicant = $applicant;
    $all_emails = $emails = explode(",", trim(str_replace(";", ",", $this->job->send_email_to)));
    $this->to = array_shift($emails);
    foreach((array)$emails as $email) $this->add_cc_address($email);
    foreach((array)WildfireJobsNotification::$dev_emails as $email) $this->add_bcc_address($email);

    History::sent_application_email($job, $applicant, $all_emails);
  }


  public function notification($email_template, $data_item, $recipient, $bcc=false, $job=false){
    if($data_item) $email_template = $this->parse_template($email_template, $data_item);
    if($recipient) $email_template = $this->parse_template($email_template, $recipient, "person_");
    if($job) $email_template = $this->parse_template($email_template, $job, "job_");

    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
    $this->email_template = $email_template;
    $this->to = $recipient->email;
    foreach((array)WildfireJobsNotification::$dev_emails as $email) $this->add_bcc_address($email);
    //add file attachments
    if($media = $email_template->media) foreach($media as $file) $this->AddAttachment(PUBLIC_DIR.$file->permalink(false), $file->title);
    History::sent_email($job, $applicant, $this->to, $email_template->title);
  }

  protected function parse_template($template, $data_item, $prefix=""){
    $email_cols = array_keys($template->columns);
    $data_cols = array_keys($data_item->columns);
    foreach($email_cols as $ecol){
      $ecol_i = $template->get_col($ecol);
      if($ecol != "id" && !$ecol_i->is_association){
        foreach($data_cols as $dcol){
          $dcol_i = $data_item->get_col($dcol);
          if($dcol != "id" && !$dcol_i->is_association){
            $template->$ecol = str_replace("%".$prefix.$dcol."%", $data_item->$dcol, $template->$ecol);
          }
        }
      }
    }
    return $template;
  }
}
?>