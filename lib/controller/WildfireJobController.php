<?
class WildfireJobController extends ApplicationController{

  public $answer_forms = array(); //array of forms that need to be answered
  public $active_form = 0;
  public $total_questions = 0;
  public $setform;
  public $posted_form = false;
  public $saved_forms = array();
  public $error_forms = array();
  public $deadend = false;
  public $completed = false;
  public $send_application_notification_from = false;

  //from a job id, generate the form
  public function __job(){
    WaxEvent::run("job.start", $this);
    $content_class = $this->cms_content_class;
    $this->job_content = $content = new $content_class($this->job_primval);
    $this->session_id = $this->session_cookie();
    WaxEvent::run("job.session", $this);

    $application_ids = Session::get('application');
    if(!$application_ids[$this->job_primval]) $application_ids[$this->job_primval] = 0;


    if(param("new_application")){
      $url_params = parse_url($_SERVER['REQUEST_URI']);
      unset($application_ids[$this->job_primval]);
      Session::set('application', $application_ids);
      $this->redirect_to($url_params["path"]);
    }

    $this->application_primval = $this->get_application($content, $this->job_primval, $application_ids[$this->job_primval], $this->session_id);

    $application = new Application($this->application_primval);
    WaxEvent::run("job.application", $this);

    $this->answer_forms = $this->get_forms($content);
    WaxEvent::run("job.answer_forms", $this);
    $application = $this->saving($application);
    //deadend var
    $this->deadend = $application->deadend;
    if($this->deadend) History::deadend_application($content, $application);
    WaxEvent::run("job.save", $this);
    WaxEvent::run("job.active_form.before", $this);
    //work out totals
    if(($answers = $application->answers) && $answers && $answers->count()) $answered = $answers->count();
    if(($all_questions = $content->fields) && $all_questions && $all_questions->count()) $this->total_questions = $all_questions->count();

    WaxEvent::run("job.active_form.questions", $this);
    //this allows a manual override of the active form
    if($this->setform !== null) $this->active_form = $this->setform;
    else if($posted !== null) $this->active_form = $posted + 1;

    //if completed in the post data force it to be
    if(Request::param("_form") == "declaration-completion" && !$this->deadend && $application->complete($content)){
      $this->completed = 1;
      $application = $application->update_attributes(array('completed'=>1, 'date_completed'=>date("Y-m-d H:i:s")));
      $this->send_application_complete_notification($content, $application);
    }
    //check for reset params
    if($this->reset_application && !$application->locked && !$content->block_edits){
      History::reset_application($content, $application);
      if($application->completed) $this->send_application_edited_notification($content, $application);
      $application->update_attributes(array('completed'=>0, 'deadend'=>0));
      $this->deadend = $this->completed = false;
      $this->redirect_to($content->permalink($this->domain_base_content)."apply/");
    }

    WaxEvent::run("job.active_form.after", $this);
    Cookie::set($this->job_primval."-current", $this->active_form);
    WaxEvent::run("job.end", $this);

    //copy current settings to this applications answers
    foreach($application->answers as $answer) $answer->update_attributes(array('completed'=>$application->completed, 'deadend'=>$application->deadend, 'session'=>$application->session));
  }

  protected function send_application_complete_notification($job, $application){
    History::completed_application($job, $application);
    if($job->send_email_to){
      $notify = new WildfireJobsNotification;
      $notify->send_application_complete($job, $application, $this->send_application_notification_from);
      History::log_email($notify, $application, $job, false);
    }
    $application->notify();

  }

  protected function send_application_edited_notification($job, $application){
    if($job->send_email_to){
      $notify = new WildfireJobsNotification;
      $notify->send_application_edited($job, $application, $this->send_application_notification_from);
      History::log_email($notify, $application, $job, false);
    }
    $application->notify_edit();

  }

  protected function saving($application){
    //if form is being posted & its within range
    $this->posted_form = $posted = Request::param('_form');
    $deadend_application = 0;
    $like = "answer-".$posted;
    //merge the file data in to it
    foreach($_FILES as $k=>$data) $_POST[$k]['_file'] = $data;

    foreach($_POST as $k=>$data){
      $question_id = $data['question'];
      $answer = new Answer($data['id']);
      if(strstr($k, $like) ){

        if($answer->primval){
          $answer->answer = $data['answer'];
          $question = new Question($answer->question_id);
        }else if($data['question']) {
          $answer->question_id = $data['question'];
          $question = new Question($answer->question_id);
          //copy data over
          $answer->session = $this->session_cookie();
          $answer->application_id = $data['application'];
          $answer->question_id = $data['question'];
          $answer->domain_content_id = $data['job'];
          $answer->question_order = $data['question_order'];
          $answer->answer = $data['answer'];
          $answer->field_type = $question->field_type;
          $answer->required = $question->required;
          $answer->choices = $question->choices;
          $answer->question_text = $question->title;
          $answer->question_subtext = $question->subtext;
          $answer->extra_class = $question->extra_class;
          $answer->deadend_copy = $question->deadend_copy;
          $answer->completed = $answer->deadend = 0;
        }
        //dont overwrite
        if($data['_file'] && !$answer->answer){
          $file = $this->process_upload($data['_file']);
          $answer->answer = "<a href='".$file."' target='_new'>".$file."</a>";
        }

        if($question->required == 1 || $question->required == 2){
          $answer->columns['answer'][1]['required'] = true;
          if($question->field_type != "RadioInput") $answer->question_subtext = str_replace("<span class='answer_required'>*</span>", "", $answer->question_subtext) . " <span class='answer_required'>*</span>";
        }
        if($question->required == 2) $answer->columns['answer'][1]['deadend'] = "deadend";

        $indexes = explode("-", str_replace("answer-".$posted."-", "", $k));
        //save!
        if($saved = $answer->save()){
          $this->answer_forms[$posted][$indexes[0]][$indexes[1]] = new WaxForm($saved);
          $this->saved_forms[$posted] = $saved;
        }else{
          $this->error_forms[$posted] = $answer->error_message();
        }
        if($dead = $this->deadend($answer)) $deadend_application = 1;
      }

    }
    return $application->update_attributes(array('deadend'=>$deadend_application));
  }

  protected function process_upload($upload, $sub_dir="uploaded"){
    if($upload){
      $filename = $upload['name']['answer'];
      //from the file name find the extension
      $ext = (substr(strrchr($filename,'.'),1));
      $check = strtolower($ext);
      $setup = WildfireMedia::$allowed;
      if($setup && ($class= $setup[$check])){
        $path = PUBLIC_DIR."files/". $sub_dir."/";
        if(!is_dir($path)) mkdir($path, 0777, true);
        $filename = File::safe_file_save($path, $filename);
        $file = $path.$filename;
        move_uploaded_file($upload['tmp_name']['answer'], $file);
        return str_replace(PUBLIC_DIR, "/", $file);
      }
    }
    return "";
  }

  //if any dead end question has been answered incorrectly, then flag as a deadend
  protected function deadend($model){
    $test = $model;
    if($model->required == 2){
      $choice = trim(array_shift(explode("\n", $model->choices)));
      if($choice != $model->answer) return true;
    }
    return false;
  }

  protected function session_cookie(){
    if(!$session_id = Cookie::get("hashed")){
      $session_id = Session::get_hash();
      //set the cookie for a year
      Cookie::set("hashed", $session_id, (60*60*24*365));
      //cookie wont set until next header, so return the value
      return $session_id;
    }
    return Cookie::get("hashed");
  }

  protected function get_forms($content){
    $answer_forms = array();
    if($content && $content->primval && ($questions = $content->fields) && $questions->count()){

      foreach($questions->order('`order` ASC')->all() as $k=>$q){
        $answers = $this->setup_answer($q);
        foreach($answers as $i=>$a){
          $prefix = "answer-".$q->url()."-".$k."-".$i;
          $form = new WaxForm($a, $data, array('prefix'=>$prefix));
          $answer_forms[$q->url()][$k][$i] = $form;
        }
      }

    }

    return $answer_forms;
  }

  protected function get_application($content, $job_primval, $application_primval, $session_id){
    //get / set the application model
    $application = new Application($application_primval);
    $application->user_agent = $_SERVER['HTTP_USER_AGENT'];
    if(!$this->application_primval || !($application->primval == $application_primval) ){

      $saved = $application->update_attributes(array('session'=>$session_id, Inflections::underscore(get_class($content))."_".$content->primary_key=>$job_primval));
      $applications = Session::get('application');
      $applications[$job_primval] = $saved->primval;
      Session::set('application', $applications);
      $application_primval = $saved->primval;

      return $application_primval;
    }
    $application_ids = Session::get('application');
    return $application_ids[$job_primval];
  }

  protected function setup_answer($q){
    $answer = new Answer;
    $answers = array();
    //see if it exists already
    if($q->extra_class != "repeating") $answer->group("question_id");
    if(($found = $answer->filter("question_id", $q->primval)->filter("application_id", $this->application_primval)->order("question_order ASC")->all()) && $found->count()){
      foreach($found as $a){
        //set them so they arent editable any more
        $a->columns['question_text'][1]['disabled'] = "disabled";
        $a->columns['answer'][1]['label'] = $a->question_subtext;
        $answers[] = $a;
      }

    }else{
      $answers[]= $this->empty_answer($q);
    }

    foreach($answers as $a){
      $a->columns['question_subtext'][1]['widget'] = $a->columns['question_text'][1]['widget'] = "HiddenInput";
      //make it a required field
      if($q->required == 1 || $q->required == 2){
        $a->columns['answer'][1]['required'] = true;
        if($q->field_type != "RadioInput") $a->question_subtext = str_replace("<span class='answer_required'>*</span>", "", $a->question_subtext) . " <span class='answer_required'>*</span>";
      }
      if($q->required == 2) $a->columns['answer'][1]['deadend'] = "deadend";
      //change the answer
      $a->columns['answer'][1]['widget'] = $q->field_type;
      if($q->field_type == "DateInput") $a->columns['answer'][1]['input_format'] = "j F Y";
      if($q->choices){
        if($q->field_type == "SelectInput") $q->choices = "\n".trim($q->choices);
        $c = explode("\n", $q->choices);
        foreach($c as $v) $choices[trim($v)] = trim($v);
        $a->columns['answer'][1]['choices'] = $choices;
      }
      $a->field_type = $q->field_type;
    }

    return $answers;
  }

  protected function bot_check(){
    $posted = Request::param('check-in');
    if(($v = array_shift($posted)) && $v) return true;
    else return false;
  }

  protected function empty_answer($q){
    $a = new Answer;
    //set joins to the application and question
    $a->question_id = $q->primval;
    $a->application_id = $this->application_primval;
    //set the title
    $a->question_text = $q->title;
    $a->question_subtext = $q->subtext;
    $a->deadend_copy = $q->deadend_copy;
    $a->columns['answer'][1]['label'] = $q->subtext;
    $a->extra_class = $q->extra_class;
    $a->field_type = $q->field_type;
    $a->question_order = $q->order;
    $a->required = $q->required;
    $a->choices = $q->choices;
    return $a;
  }

}
?>


