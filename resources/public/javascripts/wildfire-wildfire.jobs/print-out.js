jQuery(document).ready(function(){
  var pg_height = 800, so_far = 0;
  if(jQuery("#cms-applications").hasClass("print-view-of-page")){

    jQuery(".appfield").each(function(){
      var block = jQuery(this), h = block.outerHeight();
      if((so_far+h) >= pg_height){
        block.css({"margin-top": ((pg_height - so_far)+20) });
        so_far = 20;
      }
      so_far+=h;

    });
  }

});