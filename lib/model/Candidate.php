<?
class Candidate extends WaxModel{

  //no required fields as we need to create empty
  public function setup(){
    parent::setup();
    $this->define("first_name", "CharField", array('group'=>'details'));
    $this->define("last_name", "CharField", array('group'=>'details'));
    $this->define("date_of_birth", "DateTimeField", array('group'=>'details'));

    $this->define("main_telephone", "CharField", array('group'=>'details'));
    $this->define("secondary_telephone", "CharField", array('group'=>'details'));
    $this->define("email", "CharField", array('group'=>'details'));

    $this->define("address", "TextField", array('group'=>'details'));
    $this->define("postcode", "CharField", array('group'=>'details'));

  }

}
?>