<?
class CMSMeetingController extends AdminComponent{

    public $dashboard = false;
    public $module_name = "meeting";
    public $model_class = 'Meeting';
    public $model_scope = 'admin';
    public $display_name = "Meetings";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems
    public $filter_fields=array(
                            'job' => array('columns'=>array('domain_content_id'), 'partial'=>'_filters_select')
                          );
    public $autosave = false;

}
?>