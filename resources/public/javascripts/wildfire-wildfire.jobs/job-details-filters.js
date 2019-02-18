jQuery(document).ready(function(){
  var table_filter_timer = false;
  jQuery(".generic_cms_block input.filter_table, .generic_cms_block .filter_stage, .generic_cms_block .filter_page").live("keyup change", function(e){
    var parent = jQuery(this).parents(".generic_cms_block"),
          input = parent.find(".filter_table"),
          select = parent.find(".filter_stage"),
          page = parent.find(".filter_page"),
          dest = input.attr("data-dest"),
          data = { inlinefilter: input.val(), type: select.val(), page: page.val() };
    clearTimeout(table_filter_timer);
    table_filter_timer = setTimeout(function(){
      input.addClass("loading");
      jQuery.ajax({
        url:dest,
        data:data,
        success:function(res){
          parent.replaceWith(res);
        }
      });
    }, 800);
  });

});