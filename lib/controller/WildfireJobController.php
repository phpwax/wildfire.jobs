<?
class WildfireJobController extends ApplicationController{

  public $answer_forms = array(); //array of forms that need to be answered
  public $active_form = 0;
  public $total_questions = 0;
  public $setform;
  //from a job id, generate the form
  public function __job(){
    WaxEvent::run("job.start", $this);
    $content_class = $this->cms_content_class;
    $content = new $content_class($this->job_primval);

    $this->session_id = $this->session_cookie();
    WaxEvent::run("job.session", $this);
    $this->application_primval = $this->get_application($content, $this->job_primval, Session::get('application'), $this->session_id);
    WaxEvent::run("job.application", $this);
    $this->answer_forms = $this->get_forms($content);
    WaxEvent::run("job.answer_forms", $this);
    $cookie = $this->job_primval."-current";

    //if form is being posted & its within range
    $posted = Request::param('_form');
    if($posted !== null && ($to_save = $this->answer_forms[$posted]) && ($saved = $to_save->save())){
      $application->answers = $saved;
      $this->answer_forms[$posted] = new WaxForm($saved);
    }
    WaxEvent::run("job.save", $this);
    WaxEvent::run("job.active_form.before", $this);
    //check existsing saves to increase the position counter
    foreach($this->answer_forms as $k=>$form){
      if($form->handler->bound_to_model && $form->handler->bound_to_model->primval) $this->active_form = $k+1;
    }
    WaxEvent::run("job.active_form.questions", $this);
    //this allows a manual override of the active form
    if($this->setform !== null) $this->active_form = $this->setform;
    else if($posted !== null) $this->active_form = $posted + 1;
    WaxEvent::run("job.active_form.after", $this);
    Cookie::set($this->job_primval."-current", $this->active_form);
    WaxEvent::run("job.end", $this);
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
      foreach($questions->order('`order` ASC')->all() as $q){
        $a = $this->setup_answer($q);
        $form = new WaxForm($a);
        $answer_forms[] = $form;
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
    }
    $a->columns['question_subtext'][1]['widget'] = $a->columns['question_text'][1]['widget'] = "HiddenInput";
    //make it a required field
    if($q->required == 1 || $q->required == 2) $a->columns['answer'][1]['required'] = true;
    if($q->required == 2) $a->columns['answer'][1]['deadend'] = true;
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