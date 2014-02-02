(function($){
	$(function(){
		
		// SHOW/HIDE MAIN PANELS, ADD ACTIVE CSS CLASS TO SELECTED NAV LINK
		$('#pdw-main-nav a').click(function(e){
			e.preventDefault();
			$('#pdw-main-nav a').removeClass('active');
			
			$(this).addClass('active');
			var name = $(this).attr('href');
			name = name.replace("#", "");
			var selector = '#pdw-panel-' + name;
			var $panel = $(selector);
			$('.pdw-panel:not(' + selector + ')').hide();
			if($panel.is(':visible')) {
				$panel.hide();
				$(this).removeClass('active');
			} else {
				$panel.show();
			}
		});
		
		// CLOSE PANEL/REMOVE ACTIVE CLASS FROM NAV LINK
		$('.pdw-panel-close').click(function(e){
			e.preventDefault();
			$(this).closest('.pdw-panel').hide();
			
			// remove menubar active classes
			$('#pdw-main-nav a').removeClass('active');
		});
		
		// TOGGLE MAIN BUTTON
		$('#pdw-icon').click(function(){
			if(localStorage.getItem('pdwIsOpen') == true){
				$(document).trigger('pdw-close');
			} else {
				$(document).trigger('pdw-open');
			}
		});
		
		$(document).on('pdw-open', function(){
			$('#pdw-toolbar').show();
			localStorage.setItem('pdwIsOpen', 1);
		});
		
		$(document).on('pdw-close', function(){
			$('#pdw-toolbar').hide();
			localStorage.setItem('pdwIsOpen', 0);
		});
		
		if(localStorage.getItem('pdwIsOpen') == true) {
			$(document).trigger('pdw-open');
		}
		
		/* Collapsers */
		$('.collapser').click(function(){
			if($(this).hasClass('closed')){
				$(this).removeClass('closed');
				$(this).next().show();
			}else{
				$(this).addClass('closed');
				$(this).next().hide();
			}
		});
		
	});
}(jQuery));