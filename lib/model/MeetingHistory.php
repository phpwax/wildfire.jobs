<?
class MeetingHistory extends WaxModel{


  public function setup(){

    parent::setup();
    $this->define("candidate", "ForeignKey", array('target_model'=>"Candidate", 'export'=>true, 'group'=>'attendees', 'editable'=>true)) ;
    $this->define("meeting", "ForeignKey", array('target_model'=>"Meeting", 'export'=>true, 'group'=>'attendees', 'editable'=>true)) ;
    $this->define("meeting_slot_start", "CharField", array('group'=>'advanced'));
    $this->define("meeting_slot_end", "CharField", array('group'=>'advanced'));

    $this->define("date_created", "DateTimeField");
  }

  public function before_save(){
   parent::before_save();
   if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
  }


  public static function set_records($meeting){
    if(($candidates = $meeting->candidates) && $candidates->count() ){
      foreach($candidates as $row){
        $model = new MeetingHistory;
        if($f = $model->filter("candidate_id", $row->primval)->filter("meeting_id", $meeting->primval)->first() ) $model = $f;
        $model->update_attributes(array('candidate_id'=>$row->primval, 'meeting_id'=>$meeting->primval, 'meeting_slot_end'=>$row->meeting_slot_end, 'meeting_slot_start'=>$row->meeting_slot_start));
      }
    }
  }

  public function get_records($meeting){
    $model = new MeetingHistory;
    return $model->filter("meeting_id", $meeting->primval)->all();
  }


}
?>