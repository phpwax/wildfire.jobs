<?
class Candidate extends WaxModel{

  //no required fields as we need to create empty
  public function setup(){

    parent::setup();

    $this->define("first_name", "CharField", array('group'=>'details', 'label'=>'First Name <small>(%person_first_name%)</small>', 'export'=>true,'scaffold'=>true));
    $this->define("last_name", "CharField", array('group'=>'details', 'label'=>'Last Name <small>(%person_last_name%)</small>', 'export'=>true,'scaffold'=>true));

    $this->define("main_telephone", "CharField", array('group'=>'details', 'label'=>'Main Telephone <small>(%person_main_telephone%)</small>', 'export'=>true,'scaffold'=>true));
    $this->define("secondary_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Secondary Telephone <small>(%person_secondary_telephone%)</small>'));
    $this->define("mobile_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Mobile Telephone <small>(%person_mobile_telephone%)</small>'));
    $this->define("email", "CharField", array('group'=>'details', 'label'=>'Email <small>(%person_email%)</small>', 'export'=>true,'scaffold'=>true));
    $this->define("address", "TextField", array('group'=>'details', 'export'=>true,'label'=>'Address <small>(%person_address%)</small>'));
    $this->define("postcode", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Postcode <small>(%person_postcode%)</small>'));
    $this->define("gender", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput'));
    $this->define("application", "ForeignKey", array('target_model'=>"Application", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("meeting", "ForeignKey", array('target_model'=>"Meeting", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("last_meeting", "ForeignKey", array('target_model'=>"Meeting", 'editable'=>false, 'col_name'=>'last_meeting_id'));

    $this->define("is_staff", "BooleanField", array('group'=>'advanced', 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("rejected", "BooleanField", array('scaffold'=>true,'group'=>'advanced', 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));
    //so can give a person a unique start time
    $this->define("meeting_slot_start", "CharField", array('group'=>'advanced'));
    $this->define("meeting_slot_end", "CharField", array('group'=>'advanced'));
    //include a stage to track how far the person got
    $this->define("stage", "ForeignKey", array('widget'=>'SelectInput', 'target_model'=>'EmailTemplate', 'choices'=>$choices, 'group'=>'advanced'));

    $this->define("sent_notification", "BooleanField", array('group'=>'advanced', 'editable'=>false, 'default'=>1)); //set to true by default
    $this->define("sent_notification_at", "DateTimeField", array('group'=>'advanced', 'editable'=>false));
  }

  public function scope_admin(){
    return $this->filter("rejected", 0)->order("date_created DESC");
  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
  }

  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }

  public function notification($meeting){
    if(!$this->sent_notification){
      if(($emails = $meeting->email_template_get($this->stage)) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
        $notify = new WildfireJobsNotification;
        $notify->send_notification($template, $meeting, $this);
        $this->update_attributes(array('sent_notification'=>1, 'sent_notification_at'=>date("Y-m-d H:i:s")));
        return true;
      }
    }
    //return true as they have already recieved this notification
    else if($this->meeting_id = $meeting->primval) return true;
    return false;
  }

  public function hired($meeting){
    $this->update_attributes(array("is_staff"=>1, 'meeting_id'=>0, 'last_meeting_id'=>$this->meeting_id, 'meeting_slot_start'=>'', 'meeting_slot_end'=>''));
    if(!$this->sent_notification && ($emails = $meeting->email_template_get('hire') ) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
      $notify = new WildfireJobsNotification;
      $notify->send_notification($template, $meeting, $this);
      if($applicant = $this->application) $applicant->update_attributes(array("is_staff"=>1, 'locked'=>1));
      $row = $this->row;
      unset($row['stage'], $row['id'], $row['date_created'], $row['date_modified'], $row['last_meeting_id'], $row['meeting_id'], $row['is_staff'], $row['is_candidate'], $row['meeting_slot']);
      $staff = new Staff;
      $staff->update_attributes($row);
      $staff->candidate = $this;
      return true;
    }
    return false;
  }

  public function rejected($meeting, $stage="reject"){
    $this->copy_to_reject()->applicant_rejection()->update_attributes(array('rejected'=>1, 'meeting_slot_start'=>'', 'meeting_slot_end'=>''));
    if(!$this->sent_notification && ($emails = $meeting->email_template_get($stage) ) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
      $notify = new WildfireJobsNotification;
      $notify->send_notification($template, $meeting, $this);
      $this->update_attributes(array("is_staff"=>0, 'meeting_id'=>0, 'last_meeting_id'=>$this->meeting_id));
      if($applicant = $this->application) $applicant->update_attributes(array("is_staff"=>1));
      return true;
    }
    return false;
  }

  public function set_to_meeting($meeting){

    $opts = array('meeting_id'=>$meeting->primval, 'last_meeting_id'=>$this->meeting_id, 'stage'=>$meeting->stage, 'meeting_slot_start'=>date("jS F Y H:i", $meeting->date_start), 'meeting_slot_end'=>date("jS F Y H:i", $meeting->date_end));
    if($this->meeting_id != $meeting->primval) $opts['sent_notification'] = 0;
    return $this->update_attributes();
  }

  public function copy_to_reject(){
    $model = new Rejected;
    $data = $this->row;
    unset($data['id']);
    $data['rejected'] = 1;
    $model->update_attributes($data);
    return $this;
  }

  public function applicant_rejection(){
    if($app = $this->application) $app->update_attributes(array('rejected'=>1, 'locked'=>1));
    return $this;
  }
}
?>