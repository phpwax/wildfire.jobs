<?
class DeadApplication extends Application{

  public $table = "application";
  public function setup(){
    parent::setup();
    $this->columns['deadend'][1]['scaffold'] = false;
    $this->columns['main_telephone'][1]['scaffold'] = false;
    $this->columns['email'][1]['scaffold'] = false;
    $this->columns['postcode'][1]['scaffold'] = false;
    $this->columns['completed'][1]['scaffold'] = false;
    $this->columns['date_completed'][1]['scaffold'] = false;
  }

}
?>