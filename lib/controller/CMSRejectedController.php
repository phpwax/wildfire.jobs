<?
class CMSRejectedController extends CMSApplicantController{

    public $dashboard = false;
    public $module_name = "rejected";
    public $model_class = 'Rejected';
    public $model_scope = 'admin';
    public $display_name = "Rejections";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems
    public $filter_fields=array(
                            'text' => array('columns'=>array('first_name', 'last_name', 'email', 'main_telephone'), 'partial'=>'_filters_text', 'fuzzy'=>true),
                            'job' => array('columns'=>array('domain_content_id'), 'partial'=>'_filters_select'),
                            'date_created' => array('columns'=>array('date_created'), 'partial'=>"_filters_date", 'fuzzy_right'=>true)
                          );
    public $autosave = false;
    public $list_options = array(
                            array('form_name'=>'export.zip', 'form_value'=>'Export as CSV', 'class'=>'revision')
                          );



}
?>