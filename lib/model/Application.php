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
    $this->define("rejection_reason", "TextField", array('group'=>'advanced', 'label'=>'Rejected because'));
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
    //answers
    $answered = array();
    foreach($this->answers as $a){
      $answered[$a->question_id] = true;
      //check requirements of the answer
      if($a->required == 2){
        $choice = array_shift(explode("\n", $a->choices));
        if(trim($choice) != trim($a->answer)) return false;
      }else if($a->required == 1 && !$a->answer) return false;
    }
    //no answers
    if(!count($answered)) return false;
    //check whats been answered, if the field is required then must be in the array
    foreach($job->fields as $field) if($field->required && !$answered[$field->primval]) return false;
    return true;
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



  public function main_search($value){
    if(substr_count($value, " ")){
      $exploded = explode(" ", $value);
      $sql = "((";
      foreach($exploded as $part)  $sql .= "`first_name` like '%$part%' or `last_name` like '%$part%'";
      $sql .= ") or `email` like '%$value%')";
      $res = $this->filter($sql)->all();
    }else $res = $this->filter("( `first_name` LIKE '%$value%' or `last_name` LIKE '%$value%' or `email` LIKE '%$value%' )")->all();
    $results = array();
    foreach($res as $row) $results[$row->id] = $row;
    return $results;
  }

  public function named(){return $this->first_name . " ". $this->last_name . "<br>(".$this->email.")";}

  public function linked(){ return "/admin/applicant/edit/$this->primval/";}
}
?>