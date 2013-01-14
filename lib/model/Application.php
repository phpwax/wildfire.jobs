<?
class Application extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("first_name", "CharField", array('scaffold'=>true));
    $this->define("last_name", "CharField", array('scaffold'=>true));
    $this->define("email", "CharField", array('scaffold'=>true));
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
    $this->define("rejection_reason", "TextField", array('group'=>'advanced', 'label'=>'Rejected because <small>%person_rejection_reason%</small>'));
    $this->define("locked", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'editable'=>false, 'scaffold'=>true));
  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->completed) $this->completed = 0;
    if($email_address = $this->email_address()) $this->email = $email_address;
    if($first_name = $this->first_name()) $this->first_name = $first_name;
    if($last_name = $this->last_name()) $this->last_name = $last_name;
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
    History::log($this->job, $this->primval, $user->primval, "PDF requested", "Page requested: <a href='".$server . str_replace($user->auth_token, "", $permalink)."'>view</a>");
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }

  public function notify(){
    //only send if we have found job, questions and the email field
    if(($email_address = $this->email_address()) && ($job = $this->get_job()) && ($template = $job->received_application_template)){
      //now we need to find their answer for this question & send the email
      $this->email = $email_address;
      $this->first_name = $this->first_name();
      $this->last_name = $this->last_name();
      $notify = new WildfireJobsNotification;
      $notify->send_notification($template, false, $this, false,$job);
    }
  }


  public function get_job(){
    return $this->job;
  }

  public function email_address(){
    return $this->get_candidate_mapped_answer("email");
  }
  public function first_name(){
    return $this->get_candidate_mapped_answer("first_name");
  }
  public function last_name(){
    return $this->get_candidate_mapped_answer("last_name");
  }

  protected function get_candidate_mapped_answer($col="email"){
    if(($job = $this->get_job()) && ($fields = $job->fields) && ($answer_field = $fields->filter("candidate_field", $col)->first()) &&
       ($answers = $this->answers) && ($found = $answers->filter("question_id", $answer_field->primval)->first())){
      return $found->answer;
    }
    return false;
  }

  public function archive(){
    if($this->is_candidate || $this->is_staff || ($c = $this->candidate) ) return false;
    else{
      $this->delete();
      return true;
    }
  }

  public function rejected($template, $user){
    $notify = new WildfireJobsNotification;
    $notify->send_notification($template, false, $this, false, $this->job, $user);
    return $this->update_attributes(array('rejected'=>1, 'locked'=>1) );
  }

  public function copy_to_reject(){
    $model = new Rejected;
    $data = array('rejected'=>1,
                          'first_name'=>$this->first_name,
                          'last_name'=>$this->last_name,
                          'email'=>$this->email,
                          'application_id'=>$this->primval,
                          'is_raw_applicant'=>1,
                          'date_created'=>$this->date_completed,
                          'rejected_on'=>date("Y-m-d H:i:s"),
                          'rejection_reason'=>$this->rejection_reason,
                          'domain_content_id'=>$this->domain_content_id,
                          'email_template_id'=>$this->email_template_id
                          );

    $model->update_attributes($data);
    return $this;
  }

  public function convert_to_candidate($meeting, $extra_data){
    if($job = $this->job){
      //grab all the key answers and copy over to candidate
      foreach($job->fields as $question) if($question->candidate_field) $mapping[$question->primval] = $question->candidate_field;

      $candidate  = new Candidate;
      $model = new Answer;
      $answers = $model->filter("application_id", $this->id)->filter("question_id", array_keys($mapping))->all();
      foreach($answers as $answer){
        $col = $mapping[$answer->question_id];
        $candidate->$col = $answer->answer;
      }
      //copy over the changed meeting info
      foreach($extra_data as $k=>$v) $candidate->$k = $v;
      $candidate->sent_notification = 0;
      if($saved = $candidate->save()){
        $saved->job = $this->job;
        $saved->application = $this;
        $saved->meeting = $meeting;
        $this->update_attributes(array('is_candidate'=>1, 'candidate_id'=>$saved->primval));
        return $candidate;
      }
    }
    return false;
  }
}
?>