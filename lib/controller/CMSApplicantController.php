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
        $hash = "ex".date("Ymdh");
        $folder = WAX_ROOT."tmp/export/";
        mkdir($folder.$hash, 0777, true);

        foreach($use as $primval){
          $m = new $this->model_class($primval);
          $m->create_pdf($module_name, $server, $hash, $folder, $this->current_user->primval);
        }
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



      }else $this->redirect_to("/admin/".$this->module_name."/");
    }

    protected function from_same_question($ids){
      $question = false;
      foreach($ids as $id){
        $app = new Application($id);
        if(!$question) $question = $app->job->primval;
        if($question != $app->job->primval) return false;
      }
      return false;
    }

}
?>