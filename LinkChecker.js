(function($) {
	
	ShowWhereLinkIs = function(link){
		$('.gp_admin_box_close').click();
		var $el = $('a[href$="'+link+'"]').not($('#gp_admin_html *')).not($('[data-gp_hidden="true"] *')) ;
		var $elv = $el.add( $el.closest(':visible') );

		$el.add($elv)
		.addClass('LChighlight')		
		.delay(10000)
		.queue(function(next){
			$(this).removeClass("LChighlight");
			next();
		});

		$("HTML, BODY").animate({scrollTop: $elv.offset().top-150},500);
	}
	
})(jQuery);