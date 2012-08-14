<?
class Meeting extends WaxModel{

  public static $dev_emails = array('charles@oneblackbear.com');
  public static $stage_choices = array(''=>'-- Select stage --', 'general'=>'General Assessment', 'written'=>'Written Assessment', 'driving'=>'Driving Assesstment', 'final'=>'Final Interview', 'reject'=>'Rejection');
  public function setup(){

    parent::setup();
    $this->define("title", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true));
    $this->define("stage", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true, 'widget'=>'SelectInput', 'choices'=>Meeting::$stage_choices));
    $this->define("send_notifications", "BooleanField", array('group'=>'details'));

    $this->define("description", "TextField", array('widget'=>"TinymceTextareaInput"));
    $this->define("location", "TextField", array('widget'=>"TextareaInput"));
    $this->define("date_start", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("candidates", "HasManyField", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'candidates', 'editable'=>true));

    $this->define("emails", "ManyToManyField", array('target_model'=>"EmailTemplate", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'templates'));

  }


  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
  }

  /**
   * if sending the notifications,
   *  reset the field
   *  check if
   */
  public function before_save(){
    parent::before_save();
    // //check not at the same stage?
    // $matching = true;
    // if($this->primval){
    //   $old = new Meeting($this->primval);
    //   if($old->stage != $this->stage) $matching = false;
    // }
    if(!$matching && $this->send_notifications && ($emails = $this->email_meta_get($this->stage) ){
      $this->send_notifications = 0;

    }
  }



  //this will need updating when the framework can handle manipulating join columns
  public function email_meta_set($id, $tag, $order=0, $title=''){
    $model = new WaxModel;
    if($this->table < "email_template") $model->table = $this->table."_email_template";
    else $model->table = "email_template_".$this->table;

    $col = $this->table."_".$this->primary_key;
    if(!$order) $order = 0;
    if(($found = $model->filter($col, $this->primval)->filter("email_template_id", $id)->all()) && $found->count()){
      foreach($found as $r){
        $sql = "UPDATE `".$model->table."` SET `join_order`=$order, `tag`='$tag', `title`='$title' WHERE `id`=$r->primval";
        $model->query($sql);
      }
    }else{
      $sql = "INSERT INTO `".$model->table."` (`email_template_id`, `$col`, `join_order`, `tag`, `title`) VALUES ('$id', '$this->primval', '$order', '$tag', '$title')";
      $model->query($sql);
    }
  }

  public function email_meta_get($id=false, $tag=false){
    $model = new WaxModel;
    if($this->table < "email_template") $model->table = $this->table."_email_template";
    else $model->table = "email_template_".$this->table;
    $col = $this->table."_".$this->primary_key;
    if($id) return $model->filter($col, $this->primval)->filter("email_template_id", $id)->order('join_order ASC')->first();
    elseif($tag=="all") return $model->filter($col, $this->primval)->order('join_order ASC')->all();
    elseif($tag) return $model->filter($col, $this->primval)->filter("tag", $tag)->order('join_order ASC')->all();
    else return false;
  }

}
?>