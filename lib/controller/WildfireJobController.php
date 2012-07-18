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

  //from a job id, generate the form
  public function __job(){
    WaxEvent::run("job.start", $this);
    $content_class = $this->cms_content_class;
    $content = new $content_class($this->job_primval);

    $this->session_id = $this->session_cookie();
    WaxEvent::run("job.session", $this);

    $this->application_primval = $this->get_application($content, $this->job_primval, Session::get('application'), $this->session_id);
    $application = new Application($this->application_primval);
    WaxEvent::run("job.application", $this);

    $this->answer_forms = $this->get_forms($content);
    WaxEvent::run("job.answer_forms", $this);
    $this->saving($application);
    //deadend var
    $this->deadend = $application->deadend;
    WaxEvent::run("job.save", $this);
    WaxEvent::run("job.active_form.before", $this);
    $answered = 0;
    //check existsing saves to increase the position counter
    foreach($this->answer_forms as $k=>$form){
      if($form->handler->bound_to_model && $form->handler->bound_to_model->primval){
        $this->active_form = $k+1;
        $answered ++;
      }
    }
    WaxEvent::run("job.active_form.questions", $this);
    //this allows a manual override of the active form
    if($this->setform !== null) $this->active_form = $this->setform;
    else if($posted !== null) $this->active_form = $posted + 1;

    //if completed in the post data force it to be
    if(Request::param("completed_application") && !$this->deadend && $answered == count($this->answer_forms)){
      $this->completed = true;
      $application = $application->update_attributes(array('completed'=>1, 'date_completed'=>date("Y-m-d H:i:s")));
    }
    //check for reset params
    if($this->reset_application && !$application->locked){
      $application->update_attributes(array('completed'=>0, 'deadend'=>0));
      $this->deadend = $this->completed = false;
      $this->active_form = 0;
    }

    WaxEvent::run("job.active_form.after", $this);
    Cookie::set($this->job_primval."-current", $this->active_form);
    WaxEvent::run("job.end", $this);
  }

  protected function saving($application){
    //if form is being posted & its within range
    $this->posted_form = $posted = Request::param('_form');
    foreach($this->answer_forms[$posted] as $i=>$to_save){
      if($to_save && ($saved = $to_save->save())){
        $application->answers = $saved;
        $this->answer_forms[$posted] = new WaxForm($saved);
        $this->saved_forms[$posted] = $saved;
      }
      //check for errors - empty fields that are require
      if($to_save && ($errors = $to_save->errors())) $this->error_forms[$posted] = $errors;
      if($dead = $this->deadend($to_save)) $application->update_attributes(array('deadend'=>$posted));
    }
  }
  //if any dead end question has been answered incorrectly, then flag as a deadend
  protected function deadend($form){
    $test = $form->handler->bound_to_model;
    foreach($test->columns as $col){
      $setup = $col[1];
      $choice = array_shift($setup['choices']);
      //answer has been set, its a deadend and doesnt match
      if($test->answer && $col[1]['deadend'] && $test->answer != $choice) return true;
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
        $a = $this->setup_answer($q);
        $prefix = "answer-".$q->url()."-".$k;
        $form = new WaxForm($a, false, array('prefix'=>$prefix));
        $answer_forms[$q->url()][$k] = $form;
      }
    }
    return $answer_forms;
  }

  protected function get_application($content, $job_primval, $application_primval, $session_id){
    //get / set the application model
    $application = new Application($application_primval);
    if(!$this->application_primval || !($application->primval == $application_primval) ){
      $saved = $application->update_attributes(array('session'=>$session_id, $content->table."_".$content->primary_key=>$job_primval));
      Session::set('application', $saved->primval);
      $application_primval = $saved->primval;
      return $application_primval;
    }
    return Session::get('application');
  }

  protected function setup_answer($q){
    $answer = $a = new Answer;
    //see if it exists already
    if($found = $answer->filter("question_id", $q->primval)->filter("application_id", $this->application_primval)->first()){
      $a = $found;
      //set them so they arent editable any more
      $a->columns['question_text'][1]['disabled'] = "disabled";
    }else{
      //set joins to the application and question
      $a->question_id = $q->primval;
      $a->application_id = $this->application_primval;
      //set the title
      $a->question_text = $q->title;
      $a->question_subtext = $q->subtext;
      $a->deadend_copy = $q->deadend_copy;
    }
    $a->columns['question_subtext'][1]['widget'] = $a->columns['question_text'][1]['widget'] = "HiddenInput";
    //make it a required field
    if($q->required == 1 || $q->required == 2) $a->columns['answer'][1]['required'] = true;
    if($q->required == 2) $a->columns['answer'][1]['deadend'] = "deadend";
    //change the answer
    $a->columns['answer'][1]['widget'] = $q->field_type;
    if($q->choices){
      $c = explode("\n", $q->choices);
      foreach($c as $v) $choices[trim($v)] = trim($v);
      $a->columns['answer'][1]['choices'] = $choices;
    }
    return $a;
  }

  protected function bot_check(){
    $posted = Request::param('check-in');
    if(($v = array_shift($posted)) && $v) return true;
    else return false;
  }

}
?>