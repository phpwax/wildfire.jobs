<?
class EmailTemplate extends WaxModel{

  public function setup(){
    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->define("title", "CharField", array('scaffold'=>true, 'label'=>'Internal title'));
    $this->define("subject", "CharField", array('scaffold'=>true));
    $this->define("from_email", "CharField", array('scaffold'=>true));
    $this->define("from_name", "CharField", array('scaffold'=>true));
    $this->define("content", "TextField", array());

    $this->define("date_modified", "DateTimeField", array('export'=>true, 'scaffold'=>true, "editable"=>false));
    $this->define("date_created", "DateTimeField", array('export'=>true, "editable"=>false));
  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
    $this->content = stripslashes($this->content);
  }

}

?>