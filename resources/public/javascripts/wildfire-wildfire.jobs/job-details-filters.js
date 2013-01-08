jQuery(document).ready(function(){
  var table_filter_timer = false;
  jQuery(".generic_cms_block input.filter_table").live("keyup", function(e){
    var input = jQuery(this),
          parent = jQuery(this).parents(".generic_cms_block"),
          dest = input.attr("data-dest"),
          val = input.val(),
          data = {inlinefilter: val}
          ;
    clearTimeout(table_filter_timer);
    table_filter_timer = setTimeout(function(){
      input.addClass("loading");
      jQuery.ajax({
        url:dest,
        data:data,
        success:function(res){
          parent.replaceWith(res);
        }
      })
    }, 800);
  });

});