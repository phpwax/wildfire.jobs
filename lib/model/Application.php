<?
class Application extends WaxModel{

  public static $hold_options = array(
    "no",
    "Reference Requested",
    "DDC TBA",
    "WA TBA"
  );

  public function setup(){
    $this->define("history", "HasManyField", array('target_model'=>'History', 'group'=>'History', 'editable'=>true) );
    parent::setup();
    $this->define("last_name", "CharField", array('scaffold'=>true));
    $this->define("first_name", "CharField", array('scaffold'=>true));
    $this->define("email", "CharField", array('scaffold'=>true));

    $this->define("main_telephone", "CharField", array('scaffold'=>false));
    $this->define("secondary_telephone", "CharField");
    $this->define("mobile_telephone", "CharField");
    $this->define("address", "TextField");
    $this->define("postcode", "CharField", array('scaffold'=>true));

    $this->define("national_insurance_number", "CharField", array('scaffold'=>false, 'label'=>'NI Number'));
    $this->define("user_agent", "CharField", array('scaffold'=>false, 'label'=>'UA', 'widget'=>'HiddenInput', 'group'=>'highlights'));

    $this->define("answers", "HasManyField", array('target_model'=>'Answer', 'group'=>'answers', 'editable'=>true));

    $this->define("date_start", "DateTimeField", array('disabled'=>'disabled'));
    $this->define("date_completed", "DateTimeField", array('scaffold'=>true, 'export'=>true, 'disabled'=>'disabled'));
    $this->define("session", "CharField", array('editable'=>false));
    $this->define("completed", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Not completed",1=>"completed"), 'scaffold'=>true, 'export'=>true));
    //marks the application as a deadend
    $this->define("deadend", "BooleanField", array('scaffold'=>true, 'export'=>true, 'choices'=>array('Not dead', 'Deadend')));
    //these will be linked to the candidate models etc
    $this->define("is_candidate", "BooleanField", array('editable'=>false,'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("is_staff", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("rejected", "BooleanField", array('scaffold'=>true, 'editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("date_rejected", "DateTimeField", array('scaffold'=>false, 'export'=>true, 'disabled'=>'disabled'));
    $this->define("rejection_reason", "TextField", array('group'=>'advanced', 'label'=>'Rejected because', 'scaffold'=>false));
    $this->define("locked", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'editable'=>false, 'scaffold'=>false));
    $this->define("need_fix", "BooleanField", array('editable'=>false));
    $this->define("archived", "BooleanField", array('editable'=>false));
    $this->define("on_hold", "IntegerField", array('group'=>'advanced', 'choices'=>self::$hold_options));


    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true,  'scaffold'=>true, 'choices'=>self::all_jobs() ));
    $this->define("media", "ManyToManyField", array('target_model'=>"WildfireMedia", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'media', 'module'=>'media'));
  }

  public function scope_dead(){
    return $this->filter("rejected", 1);
  }

  public static function all_jobs(){
    $nm = constant("CONTENT_MODEL");
    $model = new $nm;
    return $model->filter("is_job", 1)->filter("revision", 0)->order("title ASC")->all();
  }
  public static function live_jobs(){
    $nm = constant("CONTENT_MODEL");
    $model = new $nm;
    return $model->scope("live")->filter("is_job", 1)->order("title ASC")->all();
  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->completed) $this->completed = 0;
    if($this->primval && ($email_address = $this->email_address())) $this->email = $email_address;
    if($this->primval && ($first_name = $this->first_name())) $this->first_name = $first_name;
    if($this->primval && ($last_name = $this->last_name())) $this->last_name = $last_name;

    if($this->primval && ($main_telephone = $this->main_telephone())) $this->main_telephone = $main_telephone;
    if($this->primval && ($secondary_telephone = $this->secondary_telephone())) $this->secondary_telephone = $secondary_telephone;
    if($this->primval && ($mobile_telephone = $this->mobile_telephone())) $this->mobile_telephone = $mobile_telephone;
    if($this->primval && ($address = $this->address())) $this->address = $address;
    if($this->primval && ($postcode = $this->postcode())) $this->postcode = strtoupper($postcode);
    if($this->primval && ($national_insurance_number = $this->national_insurance_number())) $this->national_insurance_number = strtoupper($national_insurance_number);

    if($this->rejected && !$this->date_rejected) $this->date_rejected = date("Y-m-d H:i:s");
  }

  public function reinstate($user){
    if($saved = $this->update_attributes(array("rejected"=>0, "date_rejected"=>"1970-01-01 00:00:00", "deadend"=>0) ) ){

      //reset any candidate flag
      $c = new Candidate;
      if($candidate = $c->filter("application_id", $this->primval)->first()){
        History::log($this->job, $this->primval, $user->primval, "Reinstated candidate");
        $candidate->update_attributes( array("rejected"=>0, "date_rejected"=>"1970-01-01 00:00:00")  );
      }

      History::log($this->job, $this->primval, $user->primval, "Reinstated application");
      return $saved;
    }
    return false;
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

  public function create_pdf($module_name, $server, $hash, $folder, $user, $url=false, $settings=false){
    $job = $this->job;
    $file = $folder.$hash."/". str_replace(array("/", ".", "#"), "", $job->title . " - ".$this->first_name. " " . $this->last_name ."(".$this->primval.")" ). ".pdf";
    if(!$url) $url = $server."/admin/".$module_name."/edit/".$this->primval;
    $url .= "/.print?auth_token=".$user->auth_token;

    // $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;

    $pdf_engine_options = array("enableEscaping"=>false, 'javascript-delay'=>2500, "load-error-handling"=>"ignore");
    if($settings) foreach($settings as $k=>$v) $pdf_engine_options [$k] = $v;
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

    History::log($this->job, $this->primval, $user->primval, "PDF requested", "Page requested: <a href='".$server . str_replace($user->auth_token, "", $permalink)."'>view</a>");
    WaxLog::log('error', '[pdf: '.$file.'] '.$url, "pdf");
    if(!$pdf->saveAs($file)) throw new WaxException('Could not create PDF: '.$pdf->getError());
  }

  public function notify(){
    //only send if we have found job, questions and the email field
    if(($email_address = $this->email_address()) && ($job = $this->get_job()) && ($template = $job->received_application_template)){
      //now we need to find their answer for this question & send the email
      $this->email = $email_address;
      $this->first_name = $this->first_name();
      $this->last_name = $this->last_name();
      $notify = new WildfireJobsNotification;
      $notify->send_notification($template, false, $this, false,$job, 0, false);
      //record the email content
      History::log_email($notify, $this, $job, false);
    }
  }

  public function notify_edit(){
    //only send if we have found job, questions and the email field
    if(($email_address = $this->email_address()) && ($job = $this->get_job()) && ($template = $job->edited_application_template)){
      //now we need to find their answer for this question & send the email
      $this->email = $email_address;
      $this->first_name = $this->first_name();
      $this->last_name = $this->last_name();
      $notify = new WildfireJobsNotification;
      $notify->send_notification($template, false, $this, false,$job, 0, false);
      //record the email content
      History::log_email($notify, $this, $job, false);
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
  public function national_insurance_number(){
    return $this->get_candidate_mapped_answer("national_insurance_number");
  }

  public function main_telephone(){
    return $this->get_candidate_mapped_answer("main_telephone");
  }
  public function secondary_telephone(){
    return $this->get_candidate_mapped_answer("secondary_telephone");
  }
  public function mobile_telephone(){
    return $this->get_candidate_mapped_answer("mobile_telephone");
  }
  public function address(){
    return $this->get_candidate_mapped_answer("address");
  }
  public function postcode(){
    return $this->get_candidate_mapped_answer("postcode");
  }


  public function convictions(){
    $convictions = false;

    $data = $this->get_section_answers("Convictions & County Court Judgements");
    foreach($data as $row){
      $answer = str_replace("'", "", trim(strtolower($row->answer) ) );
      $sub = trim(strtolower($row->question_subtext));
      if($sub == "conviction" && $answer != "not applicable"  && $answer != "not aplicable" && $answer != "na" && $answer != "n/a") $convictions[] = $answer;
    }
    return $convictions;
  }

  public function self_previous_applications(){
    $data = $this->get_section_answers("Previous Applications");
    if($pre = $data->filter("question_subtext", "Have you applied for employment with us in the past?")->filter("answer", "Yes")->first() ) return $data;
    return false;
  }

  public function bus_meeting_history(){
    $history = new History;
    $data = array(
      'WRITTEN ASSESSMENT'=> "",
      'DRIVING ASSESSMENT'=> "",
      'GENERAL ASSESSMENT'=> "",
      'MEDICAL ASSESSMENT'=> "",
      'MODULE 1A+ 1B' =>'',
      'MODULE 2'=>'',
      'MODULE 3'=>'',
      'MODULE 4'=>'',
      );
    $ids = array();
    foreach($history->filter("application_id", $this->primval)->filter("title LIKE '%Invite to meeting%'")->order("id ASC")->all() as $h){
      $info = explode("<br>", $h->content);
      preg_match("#<a href='/admin/meeting/([0-9]+)/'>#i", $info[0], $meeting);
      if(is_numeric($meeting[1])){
        $m = new Meeting($meeting[1]);
        $ids[] = $m->email_template_id;
      }
      $title = trim(strtoupper(substr(str_ireplace(array("Bus - "), "", $info[1]), 0, -1)) );
      $data[$title] = $info[2];
    }
    $m = new EmailTemplate;
    $stages = array();


    return $data;
  }

  public function possible_other_applications(){
    $data = false;
    $model = new Application;
    //check for other applications on this job based on session token
    if($this->email && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id", $this->domain_content_id)->filter("session", $this->session)->first() ) ) $data['same job, matching session'] = $same->primval;
    //check for other applications on this job based on email
    if($this->session && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id", $this->domain_content_id)->filter("LENGTH(email) > 0")->filter("email", $this->email)->first() ) ) $data['same job, matching email'] = $same->primval;
    //check for same job, on postcode
    if($this->postcode && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id", $this->domain_content_id)->filter("LENGTH(postcode) > 0")->filter("postcode", $this->postcode)->first() ) ) $data['same job, matching postcode'] = $same->primval;

    if($this->email && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id != $this->domain_content_id")->filter("session", $this->session)->first() ) ) $data['different job, matching session'] = $same->primval;

    if($this->session && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id != $this->domain_content_id")->filter("LENGTH(email) > 0")->filter("email", $this->email)->first() ) ) $data['different job, matching email'] = $same->primval;

    if($this->postcode && ($same = $model->clear()->filter("id != ".$this->primval)->filter("domain_content_id != $this->domain_content_id")->filter("LENGTH(postcode) > 0")->filter("postcode", $this->postcode)->first() ) ) $data['different job, matching postcode'] = $same->primval;

    return $data;
  }

  protected function get_candidate_mapped_answer($col="email"){
    if($this->columns[$col] && $this->$col) return $this->$col;
    if($this->primval && ($job = $this->get_job()) && ($fields = $job->fields) && ($answer_field = $fields->filter("candidate_field", $col)->first()) &&
       ($answers = $this->answers) && ($found = $answers->filter("application_id", $this->primval)->filter("question_id", $answer_field->primval)->first())){
      return $found->answer;
    }
    return false;
  }

  public function get_section_answers($section_title){
    $model = new Answer;
    if( $answers = $model->filter("application_id", $this->id)->filter("question_text", $section_title)->all() ) return $answers;
    return false;
  }

  public function archive(){
    if($this->is_candidate || $this->is_staff || ($c = $this->candidate) ) return false;
    else{
      $this->delete();
      return true;
    }
  }

  public function rejected($template, $user, $dont_send=false){
    $notify = new WildfireJobsNotification;
    if($dont_send) $notify->notification($template, false, $this, false, $this->job, $user, true);
    else $notify->send_notification($template, false, $this, false, $this->job, $user);
    //record the email content
    History::log_email($notify, $this, $this->job, $user);


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
      unset($extra_data['send_email']);
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

  public function initials(){
    $str = "";
    foreach(explode(" ",$this->first_name) as $part) $str .= $part[0];
    return $str;
  }



  public function main_search($value){
    if(substr_count($value, " ")){
      $exploded = explode(" ", $value);

      $sql = "((";
      foreach($exploded as $i=>$part)  $sql .= (($i>0) ? " or ": "") ."`first_name` like '%$part%' or `last_name` like '%$part%' ";
      $sql .= ") or `email` like '%$value%' or CONCAT_WS(' ', `first_name`, `last_name`) LIKE '%$value%')";
      $res = $this->filter($sql)->all();
    }else $res = $this->filter("( `first_name` LIKE '%$value%' or `last_name` LIKE '%$value%' or `email` LIKE '%$value%' or CONCAT_WS(' ', `first_name`, `last_name`) LIKE '%$value%')")->all();
    $results = array();
    foreach($res as $row) $results[$row->id] = $row;
    return $results;
  }

  public function named(){return $this->first_name . " ". $this->last_name . "<br>(".$this->email.")";}

  public function linked(){ return "/admin/applicant/edit/$this->primval/";}
}
?>
