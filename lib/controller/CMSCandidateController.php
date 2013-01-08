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
                              'text' => array('columns'=>array('first_name', 'last_name', 'email', 'postcode'), 'partial'=>'_filters_text', 'fuzzy'=>true),
                              'job' => array('columns'=>array('job_id'), 'partial'=>'_filters_select'),
                              'date_created' => array('columns'=>array('date_created'), 'partial'=>"_filters_date", 'fuzzy_right'=>true)
                              );
    public $autosave = false;


    protected function events(){
      parent::events();
      $this->export_group = Inflections::underscore(CONTENT_MODEL)."_id";  //this needs to change to meeting when that is hooked up
    }

    // public function arrange_meeting(){
    //   if($this->use = Request::param('primval')){
    //     $this->form = new WaxForm(new Meeting);
    //     //if an existing id is posted then join the candidates to it
    //     if($exisiting = Request::param('exisiting')){
    //       $meeting = new Meeting($exisiting);
    //       //force true as meeting details already exist
    //       $this->candidates_to_meeting($this->use, $meeting, true);
    //       $this->session->add_message("Candidates have been added to ".$meeting->title. " (".$meeting->date_start.")");
    //       $this->redirect_to("/admin/meeting/edit/".$meeting->primval."/");
    //     //creating a new meeting
    //     }elseif($saved = $this->form->save()){
    //       $this->candidates_to_meeting($this->use, $saved, false);
    //       $candidate = new Candidate($this->use[0]);
    //       $saved->job = $candidate->job;
    //       $this->session->add_message("Meeting has been created. Candidates have been added to ".$saved->title. " (".$saved->date_start.")");
    //       $this->redirect_to("/admin/meeting/edit/".$saved->primval."/");
    //     }
    //   }else{
    //     $this->session->add_message("Please select candidates");
    //     $this->redirect_to("/admin/".$this->module_name."/");
    //   }
    // }


    // public function candidates_to_meeting($candidate_ids, $meeting, $send=true){
    //   foreach($candidate_ids as $id){
    //     $candidate = new Candidate($id);
    //     //reset the meeting join, send notifications as details already present
    //     $candidate->update_attributes(array('stage'=>$meeting->stage,'meeting_id'=>$meeting->primval, 'last_meeting_id'=>$candidate->meeting_id, 'sent_notification'=>0));
    //     //
    //     if($send){

    //       $candidate->notification($meeting);
    //     }
    //   }

    //   $candidate = new Candidate($this->use[0]);
    //   $meeting->job = $candidate->job;
    // }

}
?>