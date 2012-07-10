<?
class WildfireJobController extends ApplicationController{

  public $answer_forms = array(); //array of forms that need to be answered
  public $active_form = 0;
  //from a job id, generate the form
  public function __job(){
    $content_class = $this->cms_content_class;
    $content = new $content_class($this->job_primval);
    //track this via a cookie
    if(!$this->session_id){
      $this->session_id = Session::get_hash();
      Cookie::set("hashed", $this->session_id);
    }
    //get / set the application model
    $application = new Application($this->application_primval);
    if(!$this->application_primval || !($application->primval == $this->application_primval) ){
      $saved = $application->update_attributes(array('session'=>$this->session_id,$content->table."_".$content->primary_key=>$this->job_primval));
      Session::set('application', $saved->primval);
      $this->application_primval = $saved->primval;
    }
    //work this stuff out only if there are questions to answer!
    if($content && $content->primval && ($questions = $content->fields) && $questions->count()){
      $this->questions = $questions;
      foreach($questions->order('field_group ASC')->all() as $q){
        $a = $this->setup_answer($q);
        $form = new WaxForm($a);

        $this->answer_forms[] = $form;
      }
    }
    //check existsing saves to increase the position counter
    foreach($this->answer_forms as $k=>$form){
      if($form->handler->bound_to_model && $form->handler->bound_to_model->primval) $this->active_form = $k+1;
    }
    //handle saves
    $posted = Request::param("_form");
    if(($posted !== false) && ($form = $this->answer_forms[$posted])){
      $saved = $form->save();
      $application->answers = $saved;
      $this->answer_forms[$posted] = new WaxForm($saved);
      $this->active_form = $posted + 1;
    }

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