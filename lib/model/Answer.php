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
    $this->define("choices", "TextField"); //only for dropdowns & radio buttons
    $this->define("field_type", "CharField");
    $this->define("required", "IntegerField", array('widget'=>'SelectInput','choices'=>array('Optional', 'Required', 'Deadend')));
    //copy from application
    $this->define("completed", "IntegerField", array('export'=>true, "choices"=>array(''=>'Completed?', 0=>"No",1=>"Yes")));
    $this->define("deadend", "IntegerField", array('export'=>true,"choices"=>array(''=>'Dead End?', 0=>"No",1=>"Yes")));
    $this->define("session", "CharField");
    $this->define("question_order", "IntegerField", array('widget'=>'HiddenInput'));
  }

  public function before_save(){
    parent::before_save();
    if(!$this->submitted_at) $this->submitted_at = date("Y-m-d H:i:s");
    foreach(array('answer', 'question_text', 'deadend_copy', 'question_subtext') as $col) $this->$col = str_replace("<span class='answer_required'>*</span>", "", stripslashes($this->$col));
  }
  public function error_message() {
    if(!$this->errors) return "";
    $output = "<ul class='user_errors'>";
    foreach($this->errors as $k=>$error)
      foreach($error as $err) $output .= "<li class='error_message'>$err</li>";
    return $output ."</ul>";
  }

  //custom email validation on EmailInput
  public function validate(){
    parent::validate();

    if($this->field_type == "EmailInput"){
      $field = $this->get_col("answer");
      $field->validations[] = "email";
      $field->is_valid();

      if($field->errors) {
        $this->errors["answer"] = $field->errors;
      }
    }
    if(count($this->errors)) return false;
    return true;
  }
}

?>