<?
class Answer extends WaxModel{

  public function setup(){
    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->define("question", "ForeignKey", array('target_model'=>'Question', 'widget'=>'HiddenInput'));
    $this->define("application", "ForeignKey", array('target_model'=>'Application', 'widget'=>'HiddenInput'));
    $this->define("question_text", "CharField"); //this will be the title & sub copy from the Question model
    $this->define("question_subtext", "TextField");
    $this->define("answer", "TextField");
    $this->define("submitted_at", "DateTimeField", array('editable'=>false));
    $this->define("deadend_copy", "CharField");
  }

  public function before_save(){
    parent::before_save();
    if(!$this->submitted_at) $this->submitted_at = date("Y-m-d H:i:s");
    $this->answer = stripslashes($this->answer);
  }
}

?>