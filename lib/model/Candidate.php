<?
class Candidate extends WaxModel{

  //no required fields as we need to create empty
  public function setup(){

    parent::setup();

    $this->define("first_name", "CharField", array('group'=>'details', 'label'=>'First Name', 'export'=>true,'scaffold'=>true));
    $this->define("last_name", "CharField", array('group'=>'details', 'label'=>'Last Name', 'export'=>true,'scaffold'=>true));

    $this->define("main_telephone", "CharField", array('group'=>'details', 'label'=>'Main Telephone', 'export'=>true,'scaffold'=>true));
    $this->define("secondary_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Secondary Telephone'));
    $this->define("mobile_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Mobile Telephone'));
    $this->define("email", "CharField", array('group'=>'details', 'label'=>'Email', 'export'=>true,'scaffold'=>true));
    $this->define("address", "TextField", array('group'=>'details', 'export'=>true,'label'=>'Address'));
    $this->define("postcode", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Postcode'));
    $this->define("gender", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput'));
    $this->define("application", "ForeignKey", array('target_model'=>"Application", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("meeting", "ForeignKey", array('target_model'=>"Meeting", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("last_meeting", "ForeignKey", array('target_model'=>"Meeting", 'editable'=>false, 'col_name'=>'last_meeting_id'));

    $this->define("is_staff", "BooleanField", array('group'=>'advanced', 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));
    //include a stage to track how far the person got
    $choices = Meeting::$stage_choices;
    unset($choices['hire'], $choices['reject']);
    $this->define("stage", "CharField", array('group'=>'details', 'widget'=>'SelectInput', 'choices'=>$choices, 'group'=>'advanced'));
  }

  public function before_save(){
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

  public function hired($meeting){
    if($meeting->send_notification && ($emails = $meeting->email_template_get('hired') ) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
      $notify = new Wildfirejobsnotification;
      $notify->send_notification($template, $meeting, $this);
      $this->update_attributes(array("is_staff"=>1));
      if($applicant = $this->application) $applicant->update_attributes(array("is_staff"=>1));
      $row = $this->row;
      unset($row['id'], $row['date_created'], $row['date_modified'], $row['last_meeting_id'], $row['meeting_id']);
      $staff = new Staff;
      $staff->update_attributes($row);
      $staff->candidate = $this;
      return true;
    }
    return false;
  }

  public function rejected($meeting){
    if($meeting->send_notification && ($emails = $meeting->email_template_get('reject') ) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
      $notify = new Wildfirejobsnotification;
      $notify->send_notification($template, $meeting, $this);
      return true;
    }
    return false;
  }

}
?>