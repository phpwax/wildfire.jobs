<?
class Meeting extends WaxModel{

  public static $dev_emails = array();

  public function setup(){

    parent::setup();
    $this->define("title", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true));
    $this->define("description", "TextField", array('widget'=>"TinymceTextareaInput"));
    $this->define("location", "TextField", array('widget'=>"TextareaInput"));
    $this->define("date_start", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));
    $this->define("send_email_to", "CharField", array('group'=>'details'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("candidates", "HasManyField", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'relationships', 'editable'=>true));
    $this->define("cancelled", "BooleanField", array('scaffold'=>true, "widget"=>"SelectInput", "choices"=>array(''=>'-- cancelled? --', 0=>"No",1=>"Yes")));
    //joins to the meetings
    $this->define("meeting_invite", "ForeignKey", array('group'=>'details', 'target_model'=>'EmailTemplate', 'col_name'=>'meeting_invite_id', 'label'=>'Meeting invite email'));
    $this->define("meeting_changed", "ForeignKey", array('group'=>'details', 'target_model'=>'EmailTemplate', 'col_name'=>'meeting_changed_id', 'label'=>'Meeting changed email'));
    $this->define("meeting_cancelled", "ForeignKey", array('group'=>'details', 'target_model'=>'EmailTemplate', 'col_name'=>'meeting_cancelled_id', 'label'=>'Meeting cancelled email'));

  }


  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }


  public function send_notifications($type){
    $notified = $failed = 0;
    if(($candidates = $this->candidates) && $candidates->count() && ($email = $this->$type) && $email->primval){
      foreach($candidates as $candidate){
        $notify = new Widlfirejobsnotification;
        if($candidate->email){
          $notify->send_notification($email, $this, $candidate);
          $notified++;
        }else $failed ++;
      }
    }
    return array('failed'=>$failed, 'notified'=>$notified);
  }

}
?>