<?
class Question extends WildfireCustomField{

  public function setup(){
    parent::setup();
    $this->define("required", "IntegerField", array('choices'=>array('Optional', 'Required', 'Deadend')));
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