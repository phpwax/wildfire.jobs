<?
class CMSAnswersController extends AdminComponent{

    public $dashboard = false;
    public $module_name = "answers";
    public $model_class = 'Answer';
    public $model_scope = 'admin';
    public $display_name = "Answers";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems
    // public $filter_fields=array(
    //                         'job' => array('columns'=>array('domain_content_id'), 'partial'=>'_filters_select'),
    //                         'submitted_at' => array('columns'=>array('submitted_at'), 'partial'=>"_filters_date", 'fuzzy_right'=>true),
    //                         'completed' => array('columns'=>array('completed'), 'partial'=>"_filters_status"),
    //                         'deadend' => array('columns'=>array('deadend'), 'partial'=>"_filters_status")
    //                       );
    public $autosave = false;

}
?>