<?
class DeadApplication extends Application{

  public $table = "application";
  public function setup(){
    parent::setup();
    $this->columns['date_rejected'][1]['scaffold'] = true;
    $this->columns['rejection_reason'][1]['scaffold'] = true;
    $this->columns['rejection_reason'][1]['label'] = 'Rejection Reason';
    $this->columns['rejected'][1]['scaffold'] = false;
    $this->columns['deadend'][1]['scaffold'] = false;
    $this->columns['main_telephone'][1]['scaffold'] = false;
    $this->columns['email'][1]['scaffold'] = false;
    $this->columns['postcode'][1]['scaffold'] = false;
    $this->columns['completed'][1]['scaffold'] = false;
    $this->columns['date_completed'][1]['scaffold'] = false;
  }

}
?>
