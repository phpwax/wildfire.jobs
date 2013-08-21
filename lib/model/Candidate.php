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
    $this->define("national_insurance_number", "CharField", array('scaffold'=>false, 'label'=>'NI Number', 'group'=>'details'));

    $this->define("gender", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'label'=>'Gender <small>(%person_gender%)</small>'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput'));
    $this->define("application", "ForeignKey", array('target_model'=>"Application", 'export'=>true, 'group'=>'application', 'widget'=>'HiddenInput'));
    $this->define("meeting", "ForeignKey", array('target_model'=>"Meeting", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("last_meeting", "ForeignKey", array('target_model'=>"Meeting", 'editable'=>false, 'col_name'=>'last_meeting_id'));

    $this->define("is_staff", "BooleanField", array('group'=>'advanced', 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("rejected", "BooleanField", array('scaffold'=>true,'group'=>'advanced', 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("rejection_reason", "TextField", array('group'=>'advanced', 'label'=>'Rejected because'));
    $this->define("date_rejected", "DateTimeField", array('scaffold'=>false, 'export'=>true, 'disabled'=>'disabled'));

    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));
    //so can give a person a unique start time
    $this->define("meeting_slot_start", "CharField", array('group'=>'advanced'));
    $this->define("meeting_slot_end", "CharField", array('group'=>'advanced'));
    //include a stage to track how far the person got
    $this->define("stage", "ForeignKey", array('widget'=>'SelectInput', 'target_model'=>'EmailTemplate', 'choices'=>$choices, 'group'=>'advanced'));
    //an on hold flag
    $this->define("on_hold", "BooleanField", array('group'=>'advanced'));
    $this->define("hold_notes", "TextField", array('group'=>'advanced'));

    $this->define("sent_notification", "BooleanField", array('group'=>'advanced', 'editable'=>false, 'default'=>1)); //set to true by default
    $this->define("sent_notification_at", "DateTimeField", array('group'=>'advanced', 'editable'=>false));
  }

  public function scope_admin(){
    return $this->order("date_created DESC");
  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    if($this->rejected && !$this->date_rejected) $this->date_rejected = date("Y-m-d H:i:s");
    $this->postcode = strtoupper($this->postcode);
    $this->date_modified = date("Y-m-d H:i:s");
  }

  public function create_pdf($module_name, $server, $hash, $folder, $user){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $url = $server."/admin/applicant/edit/".$this->application_id."/.print?auth_token=".$user->auth_token;

    $pdf_engine_options = array("enableEscaping"=>false, 'javascript-delay'=>3500, "load-error-handling"=>"ignore");
    $pdf = new WkHtmlToPdf($pdf_engine_options);
    WaxLog::log("error", $url, "pdf");
    $curl = new WaxBackgroundCurl(array('url'=>$url, 'cache'=>false) );
    $contents = $curl->fetch();

    $contents = str_replace("\"/stylesheets/", "\"".$server."/stylesheets/", $contents);
    $contents = str_replace("\"/images/", "\"".$server."/images/", $contents);
    $contents = str_replace("\"/files/", "\"".$server."/files/", $contents);
    $contents = str_replace("'/files/", "'\"".$server."/files/", $contents);
    $contents = str_replace("\"/m/", "\"".$server."/m/", $contents);
    $contents = str_replace("'/m/", $server."/m/", $contents);
    $contents = str_replace("//www.google", "http://www.google", $contents);
    $contents = str_replace("\"/tinymce/", "\"".$server."/tinymce/", $contents);
    $contents = str_replace("\"/javascripts/", "\"".$server."/javascripts/", $contents);
    file_put_contents($file.".html", $contents);
    $pdf->addPage($contents);


    History::log($this->job, $this->application_id, $user->primval, "PDF requested", "Page requested: <a href='".$server . str_replace($user->auth_token, "", $permalink)."'>view</a>");
    WaxLog::log('error', '[pdf: '.$file.'] '.$url, "pdf");
  }

  public function notification($meeting, $user_id){
    if(!$this->sent_notification){
      if(($template = $meeting->stage)){
        $notify = new WildfireJobsNotification;
        if($template->dont_send) $notify->notification($template, $meeting, $this, false, $this->job, $user_id);
        else $notify->send_notification($template, $meeting, $this, false, $this->job, $user_id);
        History::log_email($notify, $this->application, $this->job, $user_id);
        $this->update_attributes(array('sent_notification'=>1, 'sent_notification_at'=>date("Y-m-d H:i:s")));
        return true;
      }
    }
    //return true as they have already recieved this notification
    else if($this->meeting_id = $meeting->primval) return true;
    return false;
  }


  public function hired($template, $user_id){
    if($saved = $this->update_attributes(array("is_staff"=>1, 'meeting_id'=>0, 'last_meeting_id'=>$this->meeting_id)) ){

      $notify = new WildfireJobsNotification;
      if($template->dont_send) $notify->notification($template, false, $this, false, $this->job, $user_id);
      else $notify->send_notification($template, false, $this, false, $this->job, $user_id);
      History::log_email($notify, $this->application, $this->job, $user_id);
      $saved->update_attributes(array('sent_notification'=>1, 'sent_notification_at'=>date("Y-m-d H:i:s")));

      if($applicant = $saved->application) $applicant->update_attributes(array("is_staff"=>1, 'locked'=>1));
      $row = $this->row;
      unset($row['date_start'], $row['date_completed'], $row['locked'], $row['deadend'], $row['completed'],
                $row['session'],$row['stage'], $row['notes'], $row['id'], $row['date_created'], $row['date_modified'],
                $row['last_meeting_id'], $row['meeting_id'], $row['is_staff'], $row['is_candidate'], $row['meeting_slot']
                );
      $staff = new Staff;
      $staff->update_attributes($row);
      $staff->candidate = $saved;
      return $saved;
    }
    return false;

  }

  public function rejected($template, $user_id){
    if($saved = $this->update_attributes(array("is_staff"=>0, 'rejected'=>1, 'last_meeting_id'=>$this->meeting_id)) ){

      $notify = new WildfireJobsNotification;
      if($template->dont_send) $notify->notification($template, false, $this, false, $this->job, $user_id);
      else $notify->send_notification($template, false, $this, false, $this->job, $user_id);
      History::log_email($notify, $this->application, $this->job, $user_id);
      $saved->update_attributes(array('sent_notification'=>1, 'sent_notification_at'=>date("Y-m-d H:i:s")));

      if($applicant = $saved->application) $applicant->update_attributes(array("is_staff"=>0, 'locked'=>1, 'rejected'=>1, 'rejection_reason'=>$this->rejection_reason));
      return $saved;
    }
    return false;

  }

  public function set_to_meeting($meeting){
    $opts = array('meeting_id'=>$meeting->primval, 'last_meeting_id'=>$this->meeting_id, 'email_template_id'=>$meeting->email_template_id);
    if($this->meeting_id != $meeting->primval) $opts['sent_notification'] = 0;
    return $this->update_attributes($opts);
  }

  public function copy_to_reject(){
    $model = new Rejected;
    $data = $this->row;
    unset($data['id']);
    $data['rejected'] = 1;
    $saved = $model->update_attributes($data);

    return $this;
  }

  public function applicant_rejection(){
    if($app = $this->application) $app->update_attributes(array('rejected'=>1, 'locked'=>1));
    return $this;
  }

  public function main_search($value){
    if(substr_count($value, " ")){
      $exploded = explode(" ", $value);
      $sql = "((";
      foreach($exploded as $part)  $sql .= "`first_name` like '%$part%' or `last_name` like '%$part%'";
      $sql .= ") or `email` like '%$value%')";
      $res = $this->filter($sql)->all();
    }else $res = $this->filter("( `first_name` LIKE '%$value%' or `last_name` LIKE '%$value%' or `email` LIKE '%$value%' )")->all();
    $results = array();
    foreach($res as $row) $results[$row->application_id] = $row;
    return $results;
  }

  public function named(){return $this->first_name . " ". $this->last_name. "<br>(".$this->email.")";}

  public function linked(){ return "/admin/candidate/edit/$this->primval/";}
}
?>