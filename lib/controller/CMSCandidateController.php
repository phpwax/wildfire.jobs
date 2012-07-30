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
                            array('form_name'=>'arrange_meeting', 'form_value'=>'Arrange meeting', 'class'=>'revision')
                          );


    protected function events(){
      parent::events();
      $this->export_group = Inflections::underscore(CONTENT_MODEL)."_id";  //this needs to change to meeting when that is hooked up
    }

    public function arrange_meeting(){
      if($this->use = Request::param('primval')){
        $this->form = new WaxForm(new Meeting);
        //if an existing id is posted then join the candidates to it
        if($exisiting = Request::param('exisiting')){
          $meeting = new Meeting($exisiting);
          foreach($this->use as $candidate) $meeting->candidates = $candidate;
          $candidate = new Candidate($this->use[0]);
          $meeting->job = $candidate->job;
          $this->session->add_message("Candidates have been added to ".$meeting->title. " (".date("d/m/Y H:i", strtotime($meeting->date_start) ) .")");
          $this->redirect_to("/admin/meeting/edit/".$meeting->primval."/");
        //creating a new meeting
        }elseif($saved = $this->form->save()){
          foreach($this->use as $candidate) $saved->candidates = $candidate;
          $candidate = new Candidate($this->use[0]);
          $saved->job = $candidate->job;
          $this->session->add_message("Meeting has been created.");
          $this->redirect_to("/admin/meeting/edit/".$saved->primval."/");
        }
      }else{
        $this->session->add_message("Please select candidates");
        $this->redirect_to("/admin/".$this->module_name."/");
      }
    }

}
?>