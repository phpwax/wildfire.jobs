<?
class CMSMeetingController extends CMSApplicantController{

    public $dashboard = false;
    public $module_name = "meeting";
    public $model_class = 'Meeting';
    public $model_scope = 'admin';
    public $display_name = "Meetings";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems
    public $filter_fields=array(
                            'job' => array('columns'=>array('domain_content_id'), 'partial'=>'_filters_select'),
                            'date_start' => array('columns'=>array('date_start'), 'partial'=>"_filters_date", 'fuzzy_right'=>true)
                          );
    public $autosave = false;
    public $list_options = array(
                            array('form_name'=>'export_pdf', 'form_value'=>'Export as PDF', 'class'=>'revision')
                          );

}
?>