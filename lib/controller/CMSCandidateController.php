<?
class CMSCandidateController extends CMSApplicantController{

    public $tree_layout = false;
    public $dashboard = false;
    public $module_name = "candidate";
    public $model_class = 'Candidate';
    public $model_scope = 'admin';
    public $display_name = "Candidates";
    public $sortable = false;

    public $filter_fields=array(
                              'job' => array('columns'=>array('job_id'), 'partial'=>'_filters_select'),
                              );
    public $autosave = false;
    public $list_options = array(
                            array('form_name'=>'archive', 'form_value'=>'Archive', 'class'=>'hide preview-button'),
                            array('form_name'=>'export.zip', 'form_value'=>'Export as CSV', 'class'=>'revision'),
                            array('form_name'=>'export_pdf', 'form_value'=>'Export as PDF', 'class'=>'revision'),
                          );


    protected function events(){
      parent::events();
      $this->export_group = Inflections::underscore(CONTENT_MODEL)."_id";  //this needs to change to meeting when that is hooked up

    }


}
?>