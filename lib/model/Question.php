<?
class Question extends WildfireCustomField{

  public function field_types(){
    return array(''=>'-- Select field type --', 'TextInput'=>'Text field', 'EmailInput'=>'Email field', 'TextareaInput'=>'Message Field', 'CheckboxInput'=>'Check box', 'RadioInput'=>'Radio button', 'SelectInput'=>'Drop down list', 'DateInput'=>'Date Picker');
  }
  public function get_column_name($test=false){
    if(!$test) $test = Inflections::underscore(str_replace("/","_",trim($this->title)));
    $model = new Question;
    if($model->filter("column_name", $test)->first()) return $this->get_column_name($test.rand(1000,9999));
    else return $test;
    return $test;
  }
}
?>