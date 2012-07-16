<?
class Application extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL));
    $this->define("answers", "HasManyField", array('target_model'=>'Answer'));
    $this->define("date_start", "DateTimeField");
    $this->define("date_completed", "DateTimeField");
    $this->define("session", "CharField");
    $this->define("completed", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Not completed",1=>"completed")));
    //these will be linked to the candidate models etc
    $this->define("is_candidate", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));
    $this->define("is_staff", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->status) $this->status = 0;
  }
}
?>