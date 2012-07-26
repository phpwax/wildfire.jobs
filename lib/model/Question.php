<?
class Question extends WildfireCustomField{

  public function setup(){
    parent::setup();
    $this->define("subtext", "TextField");
    $this->define("required", "IntegerField", array('widget'=>'SelectInput','choices'=>array('Optional', 'Required', 'Deadend')));
    $this->define("deadend_copy", "TextField", array('label'=>'Copy for deadend'));
    $this->define("extra_class", "CharField", array('widget'=>'SelectInput', 'choices'=>array(''=>'None', 'large'=>'large', 'xlarge'=>'extra large', 'stacked'=>'force stacked')));
    $this->define("candidate_field", "CharField", array('widget'=>'SelectInput', 'choices'=>Question::candidate_mapping()) );
  }
  public function get_column_name($test=false){
    if(!$test) $test = Inflections::underscore(str_replace("/","_",trim($this->title)));
    $model = new Question;
    if($model->filter("column_name", $test)->first()) return $this->get_column_name($test.rand(1000,9999));
    else return $test;
    return $test;
  }

  public function url(){
    return Inflections::to_url($this->title);
  }

  public function before_save(){
    parent::before_save();
    foreach(array('title', 'subtext', 'deadend_copy') as $col) $this->$col = stripslashes($this->$col);
  }

  public static function candidate_mapping(){
    $candidate = new Candidate;
    $to_mapping = array(''=>'Select Candidate Column');
    foreach($candidate->columns as $col=>$info){
      if($info[1]['group'] == "details") $to_mapping[$col] = $info[1]['label'];
    }
    return $to_mapping;
  }
}
?>