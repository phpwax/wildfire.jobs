<?
class Answer extends WaxModel{

  public function setup(){
    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->define("question", "ForeignKey", array('target_model'=>'Question', 'widget'=>'HiddenInput', 'export'=>true));
    $this->define("application", "ForeignKey", array('target_model'=>'Application', 'widget'=>'HiddenInput', 'group'=>'relationships'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput'));
    $this->define("question_text", "CharField", array('export'=>true)); //this will be the title & sub copy from the Question model
    $this->define("question_subtext", "TextField", array('export'=>true));
    $this->define("answer", "TextField", array('export'=>true));
    $this->define("submitted_at", "DateTimeField", array('editable'=>false, 'export'=>true));
    $this->define("extra_class", "CharField");
    $this->define("deadend_copy", "CharField");
    //copy from application
    $this->define("completed", "IntegerField", array('export'=>true));
    $this->define("deadend", "IntegerField", array('export'=>true));
    $this->define("session", "CharField");
    $this->define("question_order", "IntegerField", array('widget'=>'HiddenInput'));
  }

  public function before_save(){
    parent::before_save();
    if(!$this->submitted_at) $this->submitted_at = date("Y-m-d H:i:s");
    foreach(array('answer', 'question_text', 'deadend_copy', 'question_subtext') as $col) $this->$col = str_replace("<span class='answer_required'>*</span>", "", stripslashes($this->$col));
  }
}

?>