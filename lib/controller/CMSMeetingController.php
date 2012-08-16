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
		WaxEvent::add("cms.save.success", function(){
			$controller = WaxEvent::data();
			$saved = $controller->model;
			if($sent = $saved->notifications()) $controller->session->add_message("Sent ".$sent." notifications to candidates");

			if($actions = Request::param('actions')){
				$stages = array();
				foreach($actions as $id=>$stage) if($stage) $stages[$stage][] = $id;
				//hires
				$hired_error = $hired = 0;
				foreach((array)$stages['hire'] as $primval){
					$candidate = new Candidate($primval);
					if($candidate->hired($saved)) $hired ++;
					else $hired_error ++;
				}
				unset($stages['hire']);
				if($hired) $controller->session->add_message('Notified '.$hired." candidates of hiring");
				if($hired_error) $controller->session->add_error('Failed to notify '.$hired_error." candidates of hiring.");
				//rejects
				$rejected_error = $rejected = 0;
				foreach((array)$stages['reject'] as $primval){
					$candidate = new Candidate($primval);
					if($candidate->rejected($saved)) $rejected ++;
					else $rejected_error++;
				}
				unset($stages['rejects']);
				if($rejected) $controller->session->add_message('Notified '.$rejected." candidates of rejection.");
				if($rejected_error) $controller->session->add_error('Failed to notify '.$rejected_error." candidates of rejection.");
				//others need to have meetings removed before joining to a new meeting
				$other_meetings = array();

				foreach($stages as $stage=>$candidates){
					//make a new meeting based on this stage
					$meeting = new Meeting;
					$meeting = $meeting->update_attributes(array('title'=>$saved->title, 'location'=>$saved->location, 'stage'=>$stage));
					$meeting->emails = $saved->emails;
					$meeting->job = $saved->job;
					$meeting->prior_meeting = $saved;
					$other_meetings[] = $meeting->primval;
					//go over all candidates, remove them from current meeting, record that, join to new meeting
					foreach($candidates as $id){
						$candidate = new Candidate($id);
						$candidate = $candidate->update_attributes(array('meeting_id'=>$meeting->primval, 'last_meeting_id'=>$saved->primval));
					}
					$controller->session->add_message("Moved ".count($candidates) ." candidates to ".Meeting::$stage_choices[$stage].". Set details below.");
				}
				if(count($other_meetings)){
					$string = "primvals[]=".implode("&primvals[]=", $other_meetings);
					$controller->redirect_to("/admin/".$controller->module_name."/multi_meetings?".$string);
				}

			}

		});
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