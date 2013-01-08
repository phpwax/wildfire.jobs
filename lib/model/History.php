<?
class History extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("title", "CharField");
    $this->define("content", "TextField");
    $this->define("applicant", "ForeignKey", array('target_model'=>'Application'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL));
    $this->define("actioned_by", "ForeignKey", array('target_model'=>'WildfireUser'));
    $this->define("date_occurred", "DateTimeField");
  }

  public function before_insert(){
    $this->date_occurred = date("Y-m-d H:i:s");
  }

  public static function completed_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Completed application for '.$job->title, 'application_id'=>$application->primval, 'message'=>'Submitted completed application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function reset_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Reset application for '.$job->title, 'application_id'=>$application->primval, 'message'=>'Reset application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function deadend_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Dead end application for '.$job->title, 'application_id'=>$application->primval, 'message'=>'Dead end application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function sent_application_email($job, $application, $sent_to){
    $model = new History;
    return $model->update_attributes(array('title'=>'Sent notification of application', 'application_id'=>$application->primval, 'message'=>'Sent notification of  application to: '. implode(", ", $sent_to), 'job_id'=>$job->primval));
  }
  public static function sent_email($job, $application, $sent_to, $type){
    $model = new History;
    return $model->update_attributes(array('title'=>'Sent notification - '.$type, 'application_id'=>$application->primval, 'message'=>'Sent notification of  '.$type.' to: '. $sent_to, 'job_id'=>$job->primval));
  }
  public static function log($job, $application, $user_id, $title, $notes=false){
    $model = new History;
    if(!$notes) $notes = $title;
    return $model->update_attributes(array('title'=>"Update: ".$title, 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'message'=>$notes, 'job_id'=>$job->primval, 'wildfire_user_id'=>$user_id));
  }

}
?>