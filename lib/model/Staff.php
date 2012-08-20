<?
class Staff extends Candidate{

  public function setup(){
    parent::setup();
    unset($this->columns['is_staff'], $this->columns['stage'], $this->columns['meeting'], $this->columns['last_meeting']);
    $this->define("candidate", "ForeignKey", array('target_model'=>'Candidate', 'group'=>'relationships'));
  }
}
?>