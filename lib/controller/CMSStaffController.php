<?
class CMSStaffController extends AdminComponent{
  public $dashboard = false;
  public $module_name = "staff";
  public $model_class = 'Staff';
  public $model_scope = 'admin';
  public $display_name = "Staff";
  public $sortable = false;
  public $per_page = 20;
  public $limit_revisions = 20; //limit revisions as it may cause problems
  public $filter_fields=array();
  public $autosave = false;


  public function events(){
    parent::events();
    WaxEvent::add("cms.joins.handle", function(){
      $obj = WaxEvent::data();
      $saved = $obj->model;
      //handle new fields
      $saved->notes->unlink($saved->notes);
      foreach(Request::param('new_field') as $field){
        $model = new Note;
        if($s = $model->update_attributes($field)) $saved->notes = $s;
      }
      $joins = Request::param('joins');
      $fjoins = $joins['fields'];
      //handle existing joins
      foreach(Request::param('fields') as $field){
        $model = new Note($field['primval']);
        unset($field['primval']);
        if($fjoins[$model->primval][$model->primary_key] && ($s = $model->update_attributes($field))) $saved->notes = $s;
      }
    });
  }

}
?>