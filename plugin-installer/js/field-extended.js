jQuery( document ).ready(function() {
	 jQuery( "td.type.column-type select" )
   		.change(function() {
   			var name = jQuery(this).attr( "name" );
    		var value = jQuery(this).val();
 			console.log(value);
  			jQuery(".wp-list-select option").each(function() {
	 			if (jQuery(this).parent().attr("name") == name) {
     				console.log(jQuery(this).text());		
     				jQuery(this).removeAttr("selected");	
	 			}
	 		});
	 		jQuery(".wp-list-select option").each(function() {
	 			if (jQuery(this).parent().attr("name") == name) {
	 				if (jQuery(this).val() == value) {
	 					jQuery(this).attr("selected", true);	 				
	 				}
	 			}
	 		});
		});
   	jQuery( ".button-primary.ajax-save" )	
   		.click(function() { 
   			jQuery( ".button-primary.ajax-save" ).parent().find('input').attr("value", "save");
  	});
});