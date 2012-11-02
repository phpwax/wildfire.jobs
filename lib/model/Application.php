<?
class Application extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("answers", "HasManyField", array('target_model'=>'Answer', 'group'=>'answers', 'editable'=>true));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'scaffold'=>true));
    $this->define("date_start", "DateTimeField");
    $this->define("date_completed", "DateTimeField", array('scaffold'=>true, 'export'=>true));
    $this->define("session", "CharField", array('editable'=>false));
    $this->define("completed", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Not completed",1=>"completed"), 'scaffold'=>true, 'export'=>true));
    //marks the application as a deadend
    $this->define("deadend", "BooleanField", array('scaffold'=>true, 'export'=>true, 'choices'=>array('Not dead', 'Deadend')));
    //these will be linked to the candidate models etc
    $this->define("is_candidate", "BooleanField", array('editable'=>false,'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("is_staff", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("rejected", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("locked", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'editable'=>false, 'scaffold'=>true));
  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->completed) $this->completed = 0;
  }

  public function complete($job){
    if($this->deadend) return false;
    $complete = 1;
    //answers
    $answered = array();
    foreach($this->answers as $a) $answered[$a->question_id] = trim($a->answer);
    //no answers
    if(!count($answered)) return false;
    //check whats been answered
    foreach($job->fields as $field){
      //if the field is required then must be in the array
      if($field->required && !$answered[$field->primval]) return false;
      //if the field is a deadend then the value must match first
      $c = explode("\n", $field->choices);
      //get the first one
      $should_be = trim(array_shift($c));
      if($field->required == 2 && $should_be != $answered[$field->primval]) return false;
    }
    return $complete;
  }

  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }

  public function notify(){
    //only send if we have found job, questions and the email field
    if(($job = $this->job) && ($fields = $job->fields) && ($email_field = $fields->filter("candidate_field", "email")->first()) &&
       ($answers = $this->answers) && ($email = $answers->filter("question_id", $email_field->primval)->first())){
      //now we need to find their answer for this question & send the email
      $template = new EmailTemplate;
      if(($use = $template->get_join($job, "completed_application", true)) && ($join = $use->first()) && ($email_address = $email->answer)){
        $this->email = $email_address;
        $notify = new WildfireJobsNotification;
        $notify->send_notification(new EmailTemplate($join->email_template_id), $job, $this);
      }
    }
  }


  public function archive(){
    if($this->is_candidate || $this->is_staff || ($c = $this->candidate) ) return false;
    else{
      $this->delete();
      return true;
    }
  }
}
?>