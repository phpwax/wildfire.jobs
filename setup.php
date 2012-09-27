<?
CMSApplication::register_module("applicant", array('plugin_name'=>'wildfire.jobs', 'assets_for_cms'=>true, "display_name"=>"Applications", "link"=>"/admin/applicant/"));
CMSApplication::register_module("candidate", array("display_name"=>"Candidate", "link"=>"/admin/candidate/"));
CMSApplication::register_module("meeting", array("display_name"=>"Meetings", "link"=>"/admin/meeting/"));
CMSApplication::register_module("answers", array("display_name"=>"Answers", "link"=>"/admin/answers/", 'hidden'=>true));
CMSApplication::register_module("staff", array("display_name"=>"Staff", "link"=>"/admin/staff/"));
CMSApplication::register_module("rejected", array("display_name"=>"Rejections", "link"=>"/admin/rejected/"));
CMSApplication::register_module("emailtemplate", array("display_name"=>"Templates", "link"=>"/admin/emailtemplate/"));

//hook in to the content model and add a join
if(!defined("CONTENT_MODEL")){
  $con = new ApplicationController(false, false);
  define("CONTENT_MODEL", $con->cms_content_class);
}

WaxEvent::add(CONTENT_MODEL.".setup", function(){
  $model = WaxEvent::data();
  if($model->columns['forms']) unset($model->columns['forms']);
  $model->define("fields", "ManyToManyField", array('scaffold'=>true, 'target_model'=>'Question', 'group'=>'Questions', 'editable'=>true));
  $model->define("send_email_to", "CharField");
});

?>