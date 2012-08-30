<?
class Meeting extends WaxModel{

  public static $stage_choices = array(''=>'-- select --','general'=>'General Assessment', 'written'=>'Written Assessment', 'driving'=>'Driving Assesstment', 'final'=>'Final Interview', 'cancelled'=>'Cancelled', 'changed'=>'Changed', 'reject'=>'Rejection', 'hire'=>'Hired');
  public function setup(){

    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->columns['id'][1]['editable'] = true;
    $this->define("title", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true, 'label'=>'Title <small>(%title%)</small>'));
    $this->define("stage", "CharField", array('group'=>'details', 'export'=>true,'scaffold'=>true, 'required'=>true, 'widget'=>'SelectInput', 'choices'=>Meeting::$stage_choices, 'label'=>'Stage <small>(%stage%)</small>'));

    $this->define("description", "TextField", array('label'=>'Description <small>(%description%)</small>'));
    $this->define("location", "TextField", array('widget'=>"TextareaInput", 'label'=>'Location <small>(%location%)</small>'));
    $this->define("date_start", "DateTimeField", array('label'=>'Date start <small>(%date_start%)</small>','export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y",'input_format'=> 'j F Y H:i', 'info_preview'=>1));
    $this->define("date_end", "DateTimeField", array('label'=>'Date end <small>(%date_end%)</small>','export'=>true,'scaffold'=>true, 'default'=>"tomorrow", 'output_format'=>"j F Y", 'input_format'=> 'j F Y H:i','info_preview'=>1));
    $this->define("job", "ForeignKey", array('target_model'=>CONTENT_MODEL, 'scaffold'=>true, 'export'=>true, 'group'=>'relationships', 'widget'=>'HiddenInput', 'editable'=>false));
    $this->define("emails", "ManyToManyField", array('target_model'=>"EmailTemplate", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'emails'));
    $this->define("candidates", "HasManyField", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'further actions', 'editable'=>true));
    $this->define("prior_meeting", "ForeignKey", array('target_model'=>"Meeting", 'editable'=>false, 'col_name'=>'prior_meeting_id'));
    $this->define("date_created", "DateTimeField", array('group'=>'advanced'));
    $this->define("date_modified", "DateTimeField", array('group'=>'advanced'));
  }


 public function before_save(){
   parent::before_save();
   if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
   $this->date_modified = date("Y-m-d H:i:s");
   if(!$this->title) $this->title = "Enter meeting name";
 }

  public function create_pdf($module_name, $server, $hash, $folder, $auth_token){
    $file = $folder.$hash."/".$module_name."-".$this->primval.".pdf";
    $permalink = "/admin/".$module_name."/edit/".$this->primval."/.print?auth_token=".$auth_token;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
  }



  public function email_template_set($fileid, $tag, $order=0, $title=''){
    $model = new WaxModel;
    if($this->table < "email_template") $model->table = $this->table."_email_template";
    else $model->table = "email_template_".$this->table;

    $col = $this->table."_".$this->primary_key;
    if(!$order) $order = 0;
    if(($found = $model->filter($col, $this->primval)->filter("email_template_id", $fileid)->all()) && $found->count()){
      foreach($found as $r){
        $sql = "UPDATE `".$model->table."` SET `join_order`=$order, `tag`='$tag', `title`='$title' WHERE `id`=$r->primval";
        $model->query($sql);
      }
    }else{
      $sql = "INSERT INTO `".$model->table."` (`email_template_id`, `$col`, `join_order`, `tag`, `title`) VALUES ('$fileid', '$this->primval', '$order', '$tag', '$title')";
      $model->query($sql);
    }
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