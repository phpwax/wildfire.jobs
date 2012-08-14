<?
class CMSEmailtemplateController extends AdminComponent{

    public $dashboard = false;
    public $module_name = "emailtemplate";
    public $model_class = 'EmailTemplate';
    public $model_scope = 'admin';
    public $display_name = "Email templates";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems
    public $autosave = false;
    public $filter_fields=array(
                          'text' => array('columns'=>array('subject', 'from_name', 'from_email'), 'partial'=>'_filters_text', 'fuzzy'=>true)
                          );

}
?>