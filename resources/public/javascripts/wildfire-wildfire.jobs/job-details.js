jQuery(document).ready(function(){

  //whenever a checkbox is triggered update all the buttons to have the correct id in the params
  jQuery(".generic_cms_block input[type=checkbox]").live("click", function(e){
    var tab = jQuery(this).parents(".generic_cms_block"),
          buttons = tab.find(".global_actions a"),
          inputs = tab.find("input[type=checkbox]"),
          string = "?"
          ;
      inputs.each(function(){
        var val = jQuery(this).is(":checked");
        if(val) string += "ids[]="+val+"&";
      });
      buttons.each(function(){
        var href = jQuery(this).attr("data-href") + string;
        jQuery(this).attr("href", href);
      });
  });
});