<?
class Application extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("answers", "HasManyField", array('target_model'=>'Answer', 'group'=>'answers', 'editable'=>true));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships'));
    $this->define("date_start", "DateTimeField");
    $this->define("date_completed", "DateTimeField", array('scaffold'=>true, 'export'=>true));
    $this->define("session", "CharField", array('editable'=>false));
    $this->define("completed", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Not completed",1=>"completed"), 'scaffold'=>true, 'export'=>true));
    //marks the application as a deadend
    $this->define("deadend", "BooleanField", array('scaffold'=>true, 'export'=>true, 'choices'=>array('Not dead', 'Deadend')));
    //these will be linked to the candidate models etc
    $this->define("is_candidate", "BooleanField", array('editable'=>false,'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("is_staff", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

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
}
?>