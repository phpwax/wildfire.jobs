<?
class Meeting extends WaxModel{

  public static $from_email = "";
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
    if(($candidates = $this->candidates) && $candidates->count()){
      foreach($candidates as $candidate){
        $notfiy = new Meetingnotification();
        if($candidate->email){
          $notify->{"send_".$type}($meeting, $candidate, Meeting::$from_email, $this->send_email_to, Meeting::$dev_emails);
          $notified++;
        }else $failed ++;
      }
    }
    return array('failed'=>$failed, 'notified'=>$notified);
  }

}
?>