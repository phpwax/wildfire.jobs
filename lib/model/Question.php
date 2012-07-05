<?
class Question extends WildfireCustomField{

  public function field_types(){
    return array(''=>'-- Select field type --', 'TextInput'=>'Text field', 'EmailInput'=>'Email field', 'TextareaInput'=>'Message Field', 'CheckboxInput'=>'Check box', 'RadioInput'=>'Radio button', 'SelectInput'=>'Drop down list', 'DateInput'=>'Date Picker');
  }
}
?>