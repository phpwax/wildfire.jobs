<?
class Candidate extends WaxModel{

  //no required fields as we need to create empty
  public function setup(){

    parent::setup();

    $this->define("first_name", "CharField", array('group'=>'details', 'label'=>'First Name', 'export'=>true,'scaffold'=>true));
    $this->define("last_name", "CharField", array('group'=>'details', 'label'=>'Last Name', 'export'=>true,'scaffold'=>true));

    $this->define("main_telephone", "CharField", array('group'=>'details', 'label'=>'Main Telephone', 'export'=>true,'scaffold'=>true));
    $this->define("secondary_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Secondary Telephone'));
    $this->define("mobile_telephone", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Mobile Telephone'));
    $this->define("email", "CharField", array('group'=>'details', 'label'=>'Email', 'export'=>true,'scaffold'=>true));
    $this->define("address", "TextField", array('group'=>'details', 'export'=>true,'label'=>'Address'));
    $this->define("postcode", "CharField", array('group'=>'details', 'export'=>true,'label'=>'Postcode'));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput'));
    $this->define("application", "ForeignKey", array('target_model'=>"Application", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("meeting", "ForeignKey", array('target_model'=>"Meeting", 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));

    $this->define("is_staff", "BooleanField", array('editable'=>false, 'default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"No",1=>"Yes")));

    $this->define("date_created", "DateTimeField");
    $this->define("date_modified", "DateTimeField");
  }

  public function before_save(){
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
  }

  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }
  /**
   * return false for now as not sure rules for this; is should probably remove application as well
   */
  public function archive(){
    return false;
  }
}
?>