<?
class Answer extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("question", "ForeignKey", array('target_model'=>'Question'));
    $this->define("application", "ForeignKey", array('target_model'=>'Application'));
    $this->define("answer", "TextField");
    $this->define("submitted_at", "DateTimeField");
  }
}
?>