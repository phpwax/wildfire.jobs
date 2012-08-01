<?
class Meetingnotification extends WaxEmail{

  public static $meeting_invite_subject = "Meeting Invitation";


  public function meeting_invite($candidate, $from, $to, $dev_emails=array()){
    $this->standard_meeting_email($candidate, $from, $to, $dev_emails, Meetingnotification::$meeting_invite_subject);
  }

  protected function standard_meeting_email($candidate, $from, $to, $dev_emails, $subject){
    $this->from = $from;
    $all = explode(",", str_replace(";", ",", $to));
    $this->add_to_address(array_shift($all));
    foreach($all as $em) $this->add_bcc_address($em);
    $this->candidate = $candidate;
    $this->subject = $subject;
    foreach($dev_emails as $em) $this->add_bcc_address($em);
  }
}
?>