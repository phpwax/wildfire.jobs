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
			if(($actions = Request::param('actions')) && ($actions = array_filter($actions)) && count($actions)){
				$stages = array();
				//convert to stage based array
				foreach($actions as $id=>$stage) if($stage) $stages[$stage][] = $id;
				$controller->hirings($stages, $saved);
				$controller->rejections($stages, $saved);
				//remove part of the array so dont get duplication
				unset($stages['hire']);
				unset($stages['reject']);
				$string = $controller->other_stages($stages, $saved);
				$controller->redirect_to("/admin/".$controller->module_name."/".$string);
			}elseif($saved){
				$send_failed = $sent = 0;
				foreach($saved->candidates as $candidate){
					if($candidate->notification($saved)) $sent ++;
					else $sent_failed ++;
				}
				if($sent) $controller->session->add_message("Sent ".$sent." notifications to candidates for meeting ".$saved->title);
				if($sent_failed) $controller->session->add_error("Failed to send notifications to ".$sent_failed. " candidates");
			}

		});
	}

	public function multi_meetings(){
		if($meetings = Request::param('primvals')){
			foreach($meetings as $id){
				$prefix = "meeting-".$id;
				$meeting = new Meeting($id);
				$this->forms[] = $form = new WaxForm($meeting, false, array('form_prefix'=>$prefix, 'prefix'=>$prefix));
				if(($saved = $form->save())){
					$send_failed = $sent = 0;
					foreach($saved->candidates as $candidate){
						if($candidate->set_to_meeting($saved)->notification($saved)) $sent ++;
						else $sent_failed ++;
					}
					if($sent) $this->session->add_message("Sent ".$sent." notifications to candidates for meeting ".$saved->title);
					if($sent_failed) $this->session->add_error("Failed to send notifications to ".$sent_failed. " candidates");
				}
			}
			$this->meetings = $meetings;
		}
		else $this->redirect_to("/admin/".$this->module_name."/");
	}

	public function view(){
		$this->edit();
		$this->use_view = "edit";
	}

	public function _filter_inline_tagged(){
		parent::_filter_inline();
	}


	public function other_stages($stages, $saved){

		foreach($stages as $stage=>$candidates){
			//make a new meeting based on this stage
			$meeting = new Meeting;
			$meeting = $meeting->update_attributes(array('title'=>$saved->title, 'location'=>$saved->location, 'stage'=>$stage));
			foreach($saved->email_template_get("all") as $em) $meeting->email_template_set($em->email_template_id, $em->tag);
			$meeting->job = $saved->job;
			$meeting->prior_meeting = $saved;
			$other_meetings[] = $meeting->primval;
			//go over all candidates, remove them from current meeting, record that, join to new meeting
			foreach($candidates as $id){
				$candidate = new Candidate($id);
				$candidate->set_to_meeting($meeting)->notification($meeting);
			}
			$this->session->add_message("Moved ".count($candidates) ." candidates to ".Meeting::$stage_choices[$stage].". Set details below.");
		}
		if(count($other_meetings)) return "multi_meetings?primvals[]=".implode("&primvals[]=", $other_meetings);
		return false;
	}

	public function hirings($stages, $saved){
		//hires
		$hired_error = $hired = 0;
		foreach((array)$stages['hire'] as $primval){
			$candidate = new Candidate($primval);
			if($candidate->set_to_meeting($saved)->hired($saved)) $hired ++;
			else $hired_error ++;
		}

		if($hired) $this->session->add_message('Notified '.$hired." candidates of hiring");
		if($hired_error) $this->session->add_error('Failed to notify '.$hired_error." candidates of hiring.");
	}

	public function rejections($stages, $saved){
		//rejects
		$rejected_error = $rejected = 0;
		foreach((array)$stages['reject'] as $primval){
			$candidate = new Candidate($primval);
			if($candidate->set_to_meeting($saved)->rejected($saved)) $rejected ++;
			else $rejected_error++;
		}
		if($rejected) $this->session->add_message('Notified '.$rejected." candidates of rejection.");
		if($rejected_error) $this->session->add_error('Failed to notify '.$rejected_error." candidates of rejection.");
	}
}
?>