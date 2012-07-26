<?
CMSApplication::register_module("applicant", array("display_name"=>"Applications", "link"=>"/admin/applicant/"));
CMSApplication::register_module("answers", array("display_name"=>"Answers", "link"=>"/admin/answers/", 'hidden'=>true));

//hook in to the content model and add a join
if(!defined("CONTENT_MODEL")){
  $con = new ApplicationController(false, false);
  define("CONTENT_MODEL", $con->cms_content_class);
}

WaxEvent::add(CONTENT_MODEL.".setup", function(){
  $model = WaxEvent::data();
  if($model->columns['forms']) unset($model->columns['forms']);
  $model->define("fields", "ManyToManyField", array('scaffold'=>true, 'target_model'=>'Question', 'group'=>'Questions', 'editable'=>true));
});

?>