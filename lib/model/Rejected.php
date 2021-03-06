<?
class Rejected extends Candidate{

  public function setup(){
    parent::setup();
    $this->define("rejected_on", "DateTimeField");
    $this->define("is_raw_applicant", "BooleanField");
  }

  public function before_save(){
    parent::before_save();
    if(!$this->rejected_on) $this->rejected_on = date("Y-m-d H:i:s");
  }

  public function scope_admin(){
    return $this->order("date_created DESC");
  }

  public function linked(){ return "/admin/rejected/edit/$this->primval/";}
}
?>