<?
class Application extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL));
    $this->define("answers", "HasManyField", array('target_model'=>'Answer'));
    $this->define("date_start", "DateTimeField");
    $this->define("date_completed", "DateTimeField");
    $this->define("session", "CharField");
    $this->define("status", "BooleanField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Not completed",1=>"completed")));
  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->status) $this->status = 0;
  }
}
?>