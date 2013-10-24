<?
class Meeting extends WaxModel{


  public function setup(){

    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->columns['id'][1]['editable'] = true;
    $this->define("title", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true, 'label'=>'Title <small>(%title%)</small>'));
    $this->define("stage", "ForeignKey", array('group'=>'details', 'target_model'=>'EmailTemplate', 'export'=>true,'scaffold'=>true, 'required'=>true, 'widget'=>'SelectInput', 'label'=>'Stage <small>(%stage%)</small>'));

    $this->define("description", "TextField", array('label'=>'Description <small>(%description%)</small>'));
    $this->define("location", "TextField", array('widget'=>"TextareaInput", 'label'=>'Location <small>(%location%)</small>'));
    $this->define("date_start", "DateTimeField", array('label'=>'Date start <small>(%date_start% | %person_meeting_slot_start%)</small>','export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('label'=>'Date end <small>(%date_end% | %person_meeting_slot_end%)</small>','export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("candidates", "HasManyField", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'attendees', 'editable'=>true));
    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));
    $this->define("contact_name", "CharField", array('label'=>'Contact name <small>(%contact_name%)</small>'));
    $this->define("contact_email", "CharField", array('label'=>'Contact email <small>(%contact_email%)</small>'));
    $this->define("contact_telephone", "CharField", array('label'=>'Contact telephone <small>(%contact_telephone%)</small>'));
  }


  public static function stage_choices(){
    $model = new EmailTemplate;
    $choices = array();
    foreach($model->order("title ASC")->all() as $template) $choices[$template->primval] = $choices->template;
    return $choices;
  }

  public function after_save(){
    parent::after_save();
    MeetingHistory::set_records($this);
  }

  public function before_save(){
   parent::before_save();
   if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
   $this->date_modified = date("Y-m-d H:i:s");
   if(!$this->title) $this->title = "Enter meeting name";
  }

  public function create_pdf($module_name, $server, $hash, $folder, $user, $url=false, $settings=false){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    if(!$url) $url = $server."/admin/".$module_name."/edit/".$this->primval;
    $url .= "/.print?auth_token=".$user->auth_token;
    $pdf_engine_options = array("enableEscaping"=>false, 'javascript-delay'=>3500, "load-error-handling"=>"ignore");
    if($settings) foreach($settings as $k=>$v) $pdf_engine_options [$k] = $v;
    $pdf = new WkHtmlToPdf($pdf_engine_options);
    WaxLog::log("error", $url, "pdf");
    $curl = new WaxBackgroundCurl(array('url'=>$url, 'cache'=>false) );
    $contents = $curl->fetch();

    $contents = str_replace("\"/stylesheets/", "\"".$server."/stylesheets/", $contents);
    $contents = str_replace("\"/images/", "\"".$server."/images/", $contents);
    $contents = str_replace("\"/files/", "\"".$server."/files/", $contents);
    $contents = str_replace("'/files/", "'\"".$server."/files/", $contents);
    $contents = str_replace("\"/m/", "\"".$server."/m/", $contents);
    $contents = str_replace("'/m/", $server."/m/", $contents);
    $contents = str_replace("//www.google", "http://www.google", $contents);
    $contents = str_replace("\"/tinymce/", "\"".$server."/tinymce/", $contents);
    $contents = str_replace("\"/javascripts/", "\"".$server."/javascripts/", $contents);
    file_put_contents($file.".html", $contents);
    $pdf->addPage($contents);
    if(!$pdf->saveAs($file)) throw new Exception('Could not create PDF: '.$pdf->getError());
  }


  public function email_template_get($tag){
    if($job = $this->job){
      $template = new EmailTemplate;
      return $template->get_join($job, $this->stage, true);
    }else return false;
  }


  public function main_search($value){
    $results = array();
    foreach($this->filter("( `title` LIKE '%$value%' )")->all() as $row) $results["m".$this->id] = $row;
    return $results;
  }

  public function named(){return $this->title. "(".date("jS F Y", strtotime($this->email)).")";}

  public function linked(){ return "/admin/meeting/edit/$this->primval/";}

}
?>