<?
class Candidate extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("first_name", "CharField");
    $this->define("last_name", "CharField");
    $this->define("date_of_birth", "DateTimeField");
    $this->define("telephone", "CharField");
  }

}
?>