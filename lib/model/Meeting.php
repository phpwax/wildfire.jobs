<?
class Meeting extends WaxModel{

  public static $stage_choices = array(''=>'-- select --','general'=>'General Assessment', 'written'=>'Written Assessment', 'driving'=>'Driving Assesstment', 'final'=>'Final Interview', 'cancelled'=>'Cancelled', 'changed'=>'Changed', 'reject'=>'Rejection');
  public function setup(){

    parent::setup();
    $this->define("title", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true));
    $this->define("stage", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true, 'widget'=>'SelectInput', 'choices'=>Meeting::$stage_choices));
    $this->define("send_notification", "BooleanField", array('group'=>'details'));

    $this->define("description", "TextField", array('widget'=>"TinymceTextareaInput"));
    $this->define("location", "TextField", array('widget'=>"TextareaInput"));
    $this->define("date_start", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("emails", "ManyToManyField", array('target_model'=>"EmailTemplate", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'emails'));
    $this->define("candidates", "HasManyField", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'candidates', 'editable'=>true));

  }


  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
  }


  public function notifications(){
    $sent=0;
    if($this->send_notification && $this->stage && ($emails = $this->email_template_get($this->stage) ) && ($join = $emails->first()) && ($template = new EmailTemplate($join->email_template_id))){
      if($candidates = $this->candidates){
        foreach($this->candidates as $candidate){
          $notify = new Wildfirejobsnotification;
          $notify->send_notification($template, $this, $candidate);
          $sent++;
        }

      }
    }
    $this->update_attributes(array('send_notification'=>0));
    return $sent;
  }


  public function email_template_get($tag=false, $id=false){
    $model = new WaxModel;
    if($this->table < "email_template") $model->table = $this->table."_email_template";
    else $model->table = "email_template_".$this->table;
    $col = $this->table."_".$this->primary_key;
    if($id){
      if($tag && $tag != "all") $model->filter("tag", $tag);
      return $model->filter($col, $this->primval)->filter("email_template_id", $id)->order('join_order ASC')->first();
    }
    elseif($tag=="all") return $model->filter($col, $this->primval)->order('join_order ASC')->all();
    elseif($tag) return $model->filter($col, $this->primval)->filter("tag", $tag)->order('join_order ASC')->all();
    else return false;
  }

}
?>