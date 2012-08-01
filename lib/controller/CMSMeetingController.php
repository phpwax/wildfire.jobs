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
							'cancelled' => array('columns'=>array('cancelled'), 'partial'=>"_filters_status"),
						  );
	public $autosave = false;
	public $operation_actions = array();
	public $list_options = array(
							array('form_name'=>'meeting_cancelled_notify', 'form_value'=>'Cancel & Notify', 'class'=>'hide preview-button'),
							array('form_name'=>'export_pdf', 'form_value'=>'Export as PDF', 'class'=>'revision'),
							array('form_name'=>'meeting_created_notify', 'form_value'=>'Notify Candidates', 'class'=>'revision'),
							array('form_name'=>'meeting_changed_notify', 'form_value'=>'Change & Notify', 'class'=>'revision')
						  );

	public $quick_links = array();

	public function events(){
		parent::events();
		WaxEvent::clear("cms.layout.sublinks");
		$this->quick_links = array();
	}

	public function meeting_created_notify(){
		$res = $this->meeting_notification(Request::param('primval'));
		if(!$res) $this->session->add_error("Please select a meeting");
		if($res && $res['failed']) $this->session->add_error("Failed to be notify ".$res['failed']." candidates.");
		if($res && $res['notified']) $this->session->add_message("Notified ".$res['notified']." candidates.");
		$this->redirect_to("/admin/".$this->module_name."/");
	}

	public function meeting_changed_notify(){
		$res = $this->meeting_notification(Request::param('primval'), "meeting_changed");
		if(!$res) $this->session->add_error("Please select a meeting");
		if($res && $res['failed']) $this->session->add_error("Failed to be notify ".$res['failed']." candidates.");
		if($res && $res['notified']) $this->session->add_message("Notified ".$res['notified']." candidates.");
		$this->redirect_to("/admin/".$this->module_name."/");
	}

	public function meeting_cancelled_notify(){
		$res = $this->meeting_notification(Request::param('primval'), "meeting_cancelled");
		if(!$res) $this->session->add_error("Please select a meeting");
		if($res && $res['failed']) $this->session->add_error("Failed to be notify ".$res['failed']." candidates.");
		if($res && $res['notified']) $this->session->add_message("Notified ".$res['notified']." candidates.");

		//turn off the meetings
		foreach(Request::param('primval') as $primval){
			$meeting = new Meeting($primval);
			$meeting->update_attributes(array('cancelled'=>1));
		}

		$this->redirect_to("/admin/".$this->module_name."/");
	}

	protected function meeting_notification($use, $param="meeting_invite", $func="send_notifications"){
		$failed = $notified = 0;
		if($use){
			foreach($use as $primval){
				$meeting = new Meeting($primval);
				$results = $meeting->$func($param);
				if($results){
					$failed += $results['failed'];
					$notified += $results['notified'];
				}
			}
			return array('failed'=>$failed, 'notified'=>$notified);
		}
		return false;
	}
}
?>