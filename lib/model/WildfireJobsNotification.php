<?
class WildfireJobsNotification extends WaxEmail{

  public static $dev_emails = array();
  public $log_action = true;

  public function application_complete($job, $applicant, $from){
    $this->email_template = $template = $job->received_application_template;
    $this->from = $template->from_email;
    $this->from_name = $template->from_name;
    $this->add_replyto_address($template->from_email, $template->from_name);
    $this->subject = "Job application recieved [".$job->title."]";
    $this->job = $job;
    $this->applicant = $applicant;
    $all_emails = $emails = explode(",", trim(str_replace(";", ",", $this->job->send_email_to)));
    $this->to = array_shift($emails);
    foreach((array)$emails as $email) $this->add_cc_address($email);
    foreach((array)WildfireJobsNotification::$dev_emails as $email) $this->add_bcc_address($email);

    if($this->log_action) History::sent_application_email($job, $applicant, $all_emails);
  }

  public function application_edited($job, $applicant, $from){
    $this->email_template = $template = $job->received_application_template;
    $this->from = $template->from_email;
    $this->from_name = $template->from_name;
    $this->add_replyto_address($template->from_email, $template->from_name);
    $this->subject = "Job application edited [".$job->title."]";
    $this->job = $job;
    $this->applicant = $applicant;
    $all_emails = $emails = explode(",", trim(str_replace(";", ",", $this->job->send_email_to)));
    $this->to = array_shift($emails);
    foreach((array)$emails as $email) $this->add_cc_address($email);
    foreach((array)WildfireJobsNotification::$dev_emails as $email) $this->add_bcc_address($email);

    if($this->log_action) History::sent_application_edited_email($job, $applicant, $all_emails);
  }


  public function notification($email_template, $data_item, $recipient, $bcc=false, $job=false, $user=0, $dont_send=false){
    if($recipient instanceOf Candidate){
      $app = $recipient->application;
      $copy = array_flip(array_keys($app->row));
      unset($copy['id'], $copy['candidate_id'], $copy['rejected'], $copy['media'], $copy['rejection_reason']);
      foreach($copy as $col=>$x) $recipient->row[$col] = $app->row[$col];
    }
    if($job = $recipient->job) $cc_emails = explode(",", trim(str_replace(";", ",", $job->cc_invite_email) ) );
    else $cc_emails = array();

    $this->applicant = $recipient;
    $this->email_template = $email_template;
    if($data_item) $email_template = $this->parse_template($email_template, $data_item);
    if($recipient) $email_template = $this->parse_template($email_template, $recipient, "person_");
    if($job) $email_template = $this->parse_template($email_template, $job, "job_");

    $this->from = $email_template->from_email;
    $this->from_name = $email_template->from_name;
    $this->subject = $email_template->subject;
    $this->email_template = $email_template;
    $this->to = $recipient->email;
    $this->add_replyto_address($email_template->from_email, $email_template->from_name);
    foreach((array)WildfireJobsNotification::$dev_emails as $email) $this->add_bcc_address($email);
    foreach((array)$cc_emails as $em) $this->add_cc_address($em);

    if($bcc) $this->add_bcc_address($bcc);
    //add file attachments
    if($media = $email_template->media) foreach($media as $file) $this->AddAttachment(PUBLIC_DIR.$file->permalink(false), $file->title.".".$file->ext);
    if(get_class($recipient) == "Application" ) $app_id = $recipient->primval;
    else $app_id = $recipient->application_id;
    if($this->log_action) History::sent_email($job, $app_id, $this->to, $email_template->title, $user, !$dont_send);
  }

  protected function parse_template($template, $data_item, $prefix=""){
    $email_cols = array_keys($template->columns);
    $data_cols = array_keys($data_item->columns);
    foreach($email_cols as $ecol){
      $ecol_i = $template->get_col($ecol);
      if($ecol != "id" && !$ecol_i->is_association && $ecol != "rejection_reason"){
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

  /**
   * Override waxemails MailSend to include the right envelope from
   * @access private
   * @return bool
   */
  function MailSend($header, $body) {
      $header = preg_replace('#(?<!\r)\n#si', "\n", $header);
      $additional_parameters = "-f".$this->from;
      if($rt = mail($to, $this->EncodeHeader($this->subject), $body, $header, $additional_parameters)) {
          return true;
      } else {
          throw new WaxEmailException("Couldn't Send Email", $header."\n".$body);
      }
  }
}
?>