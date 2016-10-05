jQuery(document).ready(function() {
	jQuery("#time_switch").click(function() {
		if (jQuery(this).is(":checked")) {
			jQuery("#time_start").prop("disabled", true);
			jQuery("#time_end").prop("disabled", true);
			jQuery("#reserv_switch").prop("disabled", true);
			jQuery("#reserv_switch").prop("checked", false);
		} else {
			jQuery("#time_start").prop("disabled", false);
			jQuery("#time_end").prop("disabled", false);
			jQuery("#reserv_switch").prop("disabled", false);
		}
	});

	if(jQuery("#time_switch").is(":checked"))
	{
		jQuery("#time_start").prop("disabled", true);
		jQuery("#time_end").prop("disabled", true);
		jQuery("#reserv_switch").prop("disabled", true);
	}
});