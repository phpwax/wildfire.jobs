<?
class Note extends WaxModel{

  public function setup(){
    $this->define("content", "TextField");

    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));

  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
  }
}
?>