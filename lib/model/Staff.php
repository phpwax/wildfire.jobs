<?
class Staff extends Candidate{

  public function setup(){
    parent::setup();
    unset($this->columns['is_staff'], $this->columns['email_template_id'], $this->columns['meeting'], $this->columns['last_meeting']);
    $this->define("hired_on", "DateTimeField");
    $this->define("date_of_birth", "DateTimeField");
    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'group'=>'relationships'));
    $this->define("national_insurance_number", "CharField");
    $this->define("notes", "ManyToManyField", array('target_model'=>'Note', 'group'=>'notes'));
  }
  public function before_insert(){
    parent::before_insert();
    $this->hired_on = date("Y-m-d H:i:s");
  }

  public function linked(){ return "/admin/staff/edit/$this->primval/";}
}
?>