<?
class Meetingnotification extends WaxEmail{

  public static $meeting_invite_subject = "Meeting Invitation";
  public static $meeting_changed_subject = "Meeting Update";
  public static $meeting_cancelled_subject = "Meeting Cancelled";


  public function meeting_invite($meeting, $candidate, $from, $to, $dev_emails=array()){
    $this->standard_meeting_email($meeting, $candidate, $from, $to, $dev_emails, Meetingnotification::$meeting_invite_subject);
  }
  public function meeting_changed($meeting, $candidate, $from, $to, $dev_emails=array()){
    $this->standard_meeting_email($meeting, $candidate, $from, $to, $dev_emails, Meetingnotification::$meeting_changed_subject);
  }
  public function meeting_cancelled($meeting, $candidate, $from, $to, $dev_emails=array()){
    $this->standard_meeting_email($meeting, $candidate, $from, $to, $dev_emails, Meetingnotification::$meeting_cancelled_subject);
  }

  protected function standard_meeting_email($meeting, $candidate, $from, $to, $dev_emails, $subject){
    $this->from = $from;
    $this->add_to_address($candidate->email);
    $all = explode(",", str_replace(";", ",", $to));
    foreach($all as $em) $this->add_bcc_address($em);
    $this->candidate = $candidate;
    $this->subject = $subject;
    $this->meeting = $meeting;
    foreach($dev_emails as $em) $this->add_bcc_address($em);
  }
}
?>