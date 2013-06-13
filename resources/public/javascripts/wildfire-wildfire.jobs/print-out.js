jQuery(document).ready(function(){
  var pg_height = 100, so_far = 0;

    jQuery(".appfield").each(function(){
      var block = jQuery(this), h = block.outerHeight();
        block.css({"margin-top": 800});
    });

});