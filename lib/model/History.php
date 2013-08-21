<?
class History extends WaxModel{

  public function setup(){
    parent::setup();
    $this->define("title", "CharField");
    $this->define("content", "TextField");
    $this->define("applicant", "ForeignKey", array('target_model'=>'Application'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'col_name'=>'job_id'));
    $this->define("actioned_by", "ForeignKey", array('target_model'=>'WildfireUser'));
    $this->define("date_occurred", "DateTimeField");
  }

  public function before_insert(){
    $this->date_occurred = date("Y-m-d H:i:s");
  }

  public static function completed_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Completed application for '.$job->title, 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>'Submitted completed application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function reset_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Reset application for '.$job->title, 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>'Reset application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function deadend_application($job, $application){
    $model = new History;
    return $model->update_attributes(array('title'=>'Dead end application for '.$job->title, 'application_id'=>(is_numeric($application)) ? $application : $application->primval ,'content'=>'Dead end application for '.$job->title, 'job_id'=>$job->primval));
  }
  public static function sent_application_email($job, $application, $sent_to){
    $model = new History;
    return $model->update_attributes(array('title'=>'Sent notification of application', 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>'Sent notification of  application to: '. implode(", ", $sent_to), 'job_id'=>$job->primval));
  }
  public static function sent_application_edited_email($job, $application, $sent_to){
    $model = new History;
    return $model->update_attributes(array('title'=>'Sent notification of edited application', 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>'Sent notification of  application to: '. implode(", ", $sent_to), 'job_id'=>$job->primval));
  }
  public static function sent_email($job, $application, $sent_to, $type, $user_id, $sent=true){
    $model = new History;
    return $model->update_attributes(array('title'=>'Notification'. ((!$sent) ? ' (no email)': '' ). ': '.$type, 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>'Notification'. ((!$sent) ? ' (no email)': '' ).  ' of  '.$type.' to: '. $sent_to, 'job_id'=>$job->primval,'wildfire_user_id'=>(is_numeric($user_id)) ? $user_id : $user_id->primval  ));
  }
  public static function log($job, $application, $user_id, $title, $notes=false){
    $model = new History;
    if(!$notes) $notes = $title;
    return $model->update_attributes(array('title'=>"Update: ".$title, 'application_id'=>(is_numeric($application)) ? $application : $application->primval, 'content'=>$notes, 'job_id'=>$job->primval, 'wildfire_user_id'=>$user_id));
  }

  public static function get_log($application_id){
    $model = new History;
    return $model->filter("application_id", $application_id)->all();
  }

}
?>