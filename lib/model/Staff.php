<?
class Staff extends Candidate{

  public function setup(){
    parent::setup();
    unset($this->columns['is_staff'], $this->columns['stage'], $this->columns['meeting'], $this->columns['last_meeting']);
    $this->define("date_of_birth", "DateTimeField");
    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'group'=>'relationships'));
    $this->define("national_insurance_number", "CharField");
    $this->define("notes", "ManyToManyField", array('target_model'=>'Note', 'group'=>'notes'));
  }
}
?>