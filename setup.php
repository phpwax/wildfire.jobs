<?
CMSApplication::register_module("applicant", array('plugin_name'=>'wildfire.jobs', 'assets_for_cms'=>true, "display_name"=>"Applications", "link"=>"/admin/applicant/"));
CMSApplication::register_module("candidate", array("display_name"=>"Candidate", "link"=>"/admin/candidate/", 'hidden'=>true));
CMSApplication::register_module("meeting", array("display_name"=>"Meetings", "link"=>"/admin/meeting/", 'hidden'=>true));
CMSApplication::register_module("answers", array("display_name"=>"Answers", "link"=>"/admin/answers/", 'hidden'=>true));
CMSApplication::register_module("staff", array("display_name"=>"Staff", "link"=>"/admin/staff/", 'hidden'=>true));
CMSApplication::register_module("rejected", array("display_name"=>"Rejections", "link"=>"/admin/rejected/",  'hidden'=>true));
CMSApplication::register_module("emailtemplate", array("display_name"=>"Templates", "link"=>"/admin/emailtemplate/"));

//hook in to the content model and add a join
if(!defined("CONTENT_MODEL")){
  $con = new ApplicationController(false, false);
  define("CONTENT_MODEL", $con->cms_content_class);
}






WaxEvent::add(CONTENT_MODEL.".setup", function(){
  $model = WaxEvent::data();
  if($model->columns['forms']) unset($model->columns['forms']);
  $model->columns['title'][1]['label'] = 'Position <small>%job_title%</small>';
  $model->define("fields", "ManyToManyField", array('scaffold'=>false, 'target_model'=>'Question', 'group'=>'Questions', 'editable'=>true));
  $model->define("send_email_to", "CharField");
  $model->define("received_application_template", "ForeignKey", array('target_model'=>"EmailTemplate", "eager_loading"=>true));
  $model->define("edited_application_template", "ForeignKey", array('target_model'=>"EmailTemplate", "eager_loading"=>true, 'col_name'=>'edited_application_template_id'));

  $model->define("person_responsible_for_job", "CharField", array('label'=>'Person responsible'));
  $model->define("salary", "CharField");
  $model->define("location", "CharField");
  $model->define("role_type", "CharField", array('widget'=>'SelectInput', 'choices'=>array('Permanent'=>'Permanent', 'Temporary'=>'Temporary', 'Part time'=>'Part time')));
  $model->define("is_job", "BooleanField", array('group'=>'details', 'choices'=>array(''=>'-- select --',  0=>'Not a job', 1=>'Is a job') ) );
  $model->define("job_status", "CharField", array('scaffold'=>true,'group'=>'details', 'widget'=>'SelectInput',  'choices'=>array(''=>'-- select --', 'advertising'=> 'Advertising', 'interviewing'=>'Interviewing', 'completed'=>'Completed'), 'label'=>'Job status <small>(internal only)</small>' ) );
  $model->define("block_edits", "BooleanField", array('group'=>'details'));

});

?>