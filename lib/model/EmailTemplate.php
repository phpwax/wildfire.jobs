<?
class EmailTemplate extends WaxModel{

  public function setup(){
    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->define("subject", "CharField", array('scaffold'=>true)); //this will be the title & sub copy from the Question model
    $this->define("from_email", "CharField", array('scaffold'=>true));
    $this->define("from_name", "CharField", array('scaffold'=>true));
    $this->define("content", "TextField", array());
    $this->define("template_type", "CharField");
    $this->define("date_start", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));

    $this->define("date_modified", "DateTimeField", array('export'=>true, 'scaffold'=>true, "editable"=>false));
    $this->define("date_created", "DateTimeField", array('export'=>true, "editable"=>false));
  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
  }

}

?>