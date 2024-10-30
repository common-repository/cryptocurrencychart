jQuery(document).ready(function() {
	jQuery('.ccc-select2').select2();

	jQuery(document).on('widget-updated', function(e, widget){
		widget.find('.ccc-select2').select2();
	});
});

