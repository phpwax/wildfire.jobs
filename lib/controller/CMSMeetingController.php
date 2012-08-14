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
							'date_start' => array('columns'=>array('date_start'), 'partial'=>"_filters_date", 'fuzzy_right'=>true),
							'stage' => array('columns'=>array('stage'), 'partial'=>"_filters_status"),
						  );
	public $autosave = false;
	public $operation_actions = array('view');
	public $list_options = array(
							array('form_name'=>'export_pdf', 'form_value'=>'Export as PDF', 'class'=>'revision')
						  );

	public $quick_links = array();

	public function events(){
		parent::events();
		WaxEvent::clear("cms.layout.sublinks");
		$this->quick_links = array();
	}


	public function view(){
		$this->edit();
		$this->use_view = "edit";
	}

	public function _filter_inline_tagged(){
		parent::_filter_inline();
	}
}
?>