jQuery.noConflict();
jQuery(function(){
	var $ = jQuery;
	
	$("table.status select").change(function(){
		if($(this).val() != '0')
		{
			$("tr.valid").show();
		} else {
			$("tr.valid").hide();
		}
	}).change();
	
	$("table.status").removeClass("selectable");
});