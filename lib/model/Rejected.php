<?
class Rejected extends Candidate{

  public function setup(){
    parent::setup();
    $this->define("rejected_on", "DateTimeField");
  }

  public function before_save(){
    parent::before_save();
    if(!$this->rejected_on) $this->rejected_on = date("Y-m-d H:i:s");
  }
}
?>