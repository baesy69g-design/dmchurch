jQuery(function ($) {
	var keep_msg = $('#warning');
	keep_msg.hide();
	$('#keepid_opt').change(function () {
		if ($(this).is(':checked')) {
			keep_msg.slideDown(200);
		} else {
			keep_msg.slideUp(200);
		}
	});
});
