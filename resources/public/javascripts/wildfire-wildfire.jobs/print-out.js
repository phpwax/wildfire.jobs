jQuery(document).ready(function(){
  var pg_height = 890, so_far = 0, pgs=0;
  if(jQuery("html").hasClass("print-view-of-page")){

    jQuery(".appfield").each(function(){
      var block = jQuery(this),
            h = block.outerHeight(),
            p = block.offset().top,
            b = p+h,
            pg
            ;
      pg = b - (pg_height * (pgs+1));
      //console.log(p+":"+b+":"+pg);
      //this means it would wrap
      if(pg > 0){
        block.css({"page-break-before":"always", "margin-top": 50  });
        pgs++;
      }
    });
  }

});