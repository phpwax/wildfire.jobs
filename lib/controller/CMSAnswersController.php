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
    public $filter_fields=array();
    public $autosave = false;

}
?>