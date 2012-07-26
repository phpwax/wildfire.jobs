<?
class Candidate extends WaxModel{

  //no required fields as we need to create empty
  public function setup(){
    parent::setup();
    $this->define("first_name", "CharField", array('group'=>'details', 'label'=>'First Name'));
    $this->define("last_name", "CharField", array('group'=>'details', 'label'=>'Last Name'));

    $this->define("main_telephone", "CharField", array('group'=>'details', 'label'=>'Main Telephone'));
    $this->define("secondary_telephone", "CharField", array('group'=>'details', 'label'=>'Secondary Telephone'));
    $this->define("mobile_telephone", "CharField", array('group'=>'details', 'label'=>'Mobile Telephone'));
    $this->define("email", "CharField", array('group'=>'details', 'label'=>'Email'));

    $this->define("address", "TextField", array('group'=>'details', 'label'=>'Address'));
    $this->define("postcode", "CharField", array('group'=>'details', 'label'=>'Postcode'));

  }

}
?>