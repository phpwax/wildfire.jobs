<?
class CMSApplicantController extends AdminComponent{

    public $tree_layout = false;
    public $dashboard = false;
    public $module_name = "applicant";
    public $model_class = 'Application';
    public $model_scope = 'admin';
    public $display_name = "Applications";
    public $sortable = false;
    public $per_page = 20;
    public $limit_revisions = 20; //limit revisions as it may cause problems

    public $filter_fields=array(
                            'job' => array('columns'=>array('job_id'), 'partial'=>'_filters_select'),
                            'date_start' => array('columns'=>array('date_start', 'date_completed'), 'partial'=>"_filters_date", 'fuzzy_right'=>true),
                            'completed' => array('columns'=>array('completed'), 'partial'=>"_filters_status"),
                            'deadend' => array('columns'=>array('deadend'), 'partial'=>"_filters_status")
                          );
    public $autosave = false;


    protected function events(){
      $this->export_group = Inflections::underscore(CONTENT_MODEL)."_id";
      parent::events();
    }

    /**
     * this just shows a list of locations to go to - export etc
     */
    public function export_archive_convert(){
      if($action = Request::param('action_type')) $this->redirect_to('/admin/'.$this->module_name.'/'.$action);
    }

    public function archive(){
      $this->per_page = false;
      $this->index();
      $this->use_view = "generic_index";
    }
    public function export_pdf(){}
    public function candidate(){}

}
?>