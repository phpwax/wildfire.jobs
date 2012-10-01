<?
class EmailTemplate extends WaxModel{

  public function setup(){
    parent::setup();
    $this->columns['id'][1]['widget'] = 'HiddenInput';
    $this->define("title", "CharField", array('scaffold'=>true, 'label'=>'Internal title'));
    $this->define("subject", "CharField", array('scaffold'=>true));
    $this->define("from_email", "CharField", array('scaffold'=>true));
    $this->define("from_name", "CharField", array('scaffold'=>true));
    $this->define("content", "TextField", array());

    $this->define("date_modified", "DateTimeField", array('export'=>true, 'scaffold'=>true, "editable"=>false));
    $this->define("date_created", "DateTimeField", array('export'=>true, "editable"=>false));
    $this->define("media", "ManyToManyField", array('target_model'=>"WildfireMedia", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'media', 'module'=>'media'));
  }

  public function before_save(){
    parent::before_save();
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    $this->date_modified = date("Y-m-d H:i:s");
    $this->content = stripslashes($this->content);
  }


  public function get_join($job, $tag="all", $no_template_filter=false){
    $model = new WaxModel;
    if($this->table < $job->table) $model->table = $this->table."_".$job->table;
    else $model->table = $job->table."_".$this->table;
    $job_col = $job->table."_".$job->primary_key;
    $col = $this->table."_".$this->primary_key;
    if($no_template_filter) return $model->filter($job_col, $job->primval)->filter("tag", $tag)->order('join_order ASC')->all();
    else if($tag == "all") return $model->filter($job_col, $job->primval)->filter($col, $this->primval)->order('join_order ASC')->all();
    else return $model->filter($job_col, $job->primval)->filter($col, $this->primval)->filter("tag", $tag)->order('join_order ASC')->all();
  }

  public function set_join($job, $tag, $order=0, $title=''){
    $model = new WaxModel;
    if($this->table < $job->table) $model->table = $this->table."_".$job->table;
    else $model->table = $job->table."_".$this->table;
    $job_col = $job->table."_".$job->primary_key;
    $col = $this->table."_".$this->primary_key;

    if(!$order) $order = 0;
    if(($found = $model->filter($job_col, $job->primval)->filter($col, $this->primval)->all()) && $found->count()){
      foreach($found as $r){
        $sql = "UPDATE `".$model->table."` SET `join_order`=$order, `tag`='$tag', `title`='$title' WHERE `id`=$r->primval";
        $model->query($sql);
      }
    }else{
      $sql = "INSERT INTO `".$model->table."` (`$col`, `$job_col`, `join_order`, `tag`, `title`) VALUES ('$this->primval', '$job->primval', '$order', '$tag', '$title')";
      $model->query($sql);
    }
  }

}

?>