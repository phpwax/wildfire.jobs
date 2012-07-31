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
    public $list_options = array(
                            array('form_name'=>'archive', 'form_value'=>'Archive', 'class'=>'hide preview-button'),
                            array('form_name'=>'export.zip', 'form_value'=>'Export as CSV', 'class'=>'revision'),
                            array('form_name'=>'export_pdf', 'form_value'=>'Export as PDF', 'class'=>'revision'),
                            array('form_name'=>'candidate', 'form_value'=>'Convert to candidate', 'class'=>'revision')
                          );


    protected function events(){
      $this->export_group = Inflections::underscore(CONTENT_MODEL)."_id";
      parent::events();

    }
    public function _list(){
      parent::_list();
      $this->use_view = "_selectable_list";
    }
    /**
     * this just shows a list of locations to go to - export etc
     */
    public function export_archive_convert(){
      if($action = Request::param('action_type')) $this->redirect_to('/admin/'.$this->module_name.'/'.$action);
    }
    public function index(){
      parent::index();
      $this->use_view = "generic_index";
      if(($ex = Request::param('ex')) && ($action = array_shift(array_keys($ex))) && ($use = Request::param('use'))){
        $url = '/admin/'.$this->module_name.'/'.$action."?".http_build_query($use);
        $this->redirect_to($url);
      }
    }

    public function export(){
      $this->model_class = "Answer";
      $this->export_group = "application_id";
      parent::export();
    }

    public function export_pdf(){

      $this->use_view = $this->use_layout = false;
      if($use = Request::param('primval')){
        $server = "http://".$_SERVER['HTTP_HOST'];
        $hash = "ex".date("Ymdhis");
        $folder = WAX_ROOT."tmp/export/";

        $this->create_pdfs($folder, $hash, $server, $use);
        $this->create_and_output_zips($folder, $hash);
      }
    }

    protected function create_and_output_zips($folder, $hash){
      //afterwards, create zip
      $cmd = "cd ".$folder." && zip -j ".$hash.".zip $hash/*";
      exec($cmd);
      WaxLog::log('error', '[zip] '.$cmd, "pdf");
      $content = "";
      if(is_file($folder.$hash.".zip") && ($content = file_get_contents($folder.$hash.".zip"))){
        $name = str_replace("/", "-", $this->module_name). "-".date("Ymdh").".zip";
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=".$name);
        header("Pragma: no-cache");
        header("Expires: 0");
      }
      //tidy up
      unlink($folder.$hash.".zip");
      foreach(glob($folder.$hash."/*") as $f) unlink($f);
      rmdir($folder.$hash);
      echo $content;
    }

    protected function create_pdfs($folder, $hash, $server, $ids){
      mkdir($folder.$hash, 0777, true);
      foreach($ids as $primval){
        $m = new $this->model_class($primval);
        $m->create_pdf($this->module_name, $server, $hash, $folder, $this->current_user->auth_token);
      }
    }

    /**
     * check if they are from the same question, if not return with an error,
     * this is for column mapping between answers and canidate (ie job 1
     * might call it Last Name & job 2 calls its surname)
     *
     * create set of mapping arrays to post to the next page
     */
    public function candidate(){
      if($use = Request::param('primval')){
        if(!$this->from_same_question($use)){
          $this->session->add_error("Candidates have to be for the same position");
          $this->redirect_to("/admin/".$this->module_name."/");
        }
        //fetch the original job
        $app = new Application($use[0]);
        $job = $app->job;
        $mapping = $ignored = $not_completed = $candidates = array();
        //find the mapping columns
        foreach($job->fields as $question) if($question->candidate_field) $mapping[$question->primval] = $question->candidate_field;
        //do the conversion
        foreach($use as $id){
          $app = new Application($id);
          if($app->is_candidate) $ignored[] = $id;
          elseif(!$app->completed) $not_completed[] = $id;
          else{
            $model = new Answer;
            $answers = $model->filter("application_id", $id)->filter("question_id", array_keys($mapping))->all();
            $candidate = new Candidate;
            foreach($answers as $answer){
              $col = $mapping[$answer->question_id];
              $candidate->$col = $answer->answer;
            }
            if($saved = $candidate->save()){
              $saved->job = $app->job;
              $saved->application = $app;
              $app->update_attributes(array('is_candidate'=>1, 'candidate_id'=>$saved->primval));
              $candidates[] = $saved->primval;
            }
          }
        }
        if($c = count($ignored)) $this->session->add_error($c . " applicants are already candidates so have been ignored");
        if($i = count($not_completed)) $this->session->add_error($i . " applicants are incomplete so have been ignored");
        if($d = count($candidates)) $this->session->add_message($d . " applicants have been converted.");
      }
      $this->redirect_to("/admin/".$this->module_name."/");
    }

    /**
     * almost the same as pdf, put deletes the records as well
     * - do they want this to enforce rules, such as applicantions assigned to a candidate / staff cannot be
     *   archived
     */
    public function archive(){
      $this->use_view = $this->use_layout = false;
      if($use = Request::param('primval')){
        $server = "http://".$_SERVER['HTTP_HOST'];
        $hash = "ex".date("Ymdhis");
        $folder = WAX_ROOT."tmp/export/";
        $this->create_pdfs($folder, $hash, $server, $use);

        foreach($use as $primval){
          $m = new $this->model_class($primval);
          $m->delete();
        }
        $this->create_and_output_zips($folder, $hash);
      }
    }


    protected function from_same_question($ids){
      $question = false;
      foreach($ids as $id){
        $app = new Application($id);
        if(!$question) $question = $app->job->primval;
        if($question != $app->job->primval) return false;
      }
      return true;
    }

}
?>