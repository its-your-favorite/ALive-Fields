<?PHP
/**
 * The subclass of AcField that connects with the AcDatebox javascript class.
 * It thus can be used with <input type="text"> html elements and can load
 * 		date fields from a database or save them.
 *
 */
class AcDatebox extends AcField
{
function get_field_type_for_javascript()
	{
	return "AcDatebox"	;
	}	
	
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);		
		AcField::include_js_file(AcField::$path_to_jqueryui);		
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcTextbox/AcTextbox.js");	//not a typo
		AcField::include_js_file(Acfield::$path_to_controls . "/AcDatebox/AcDatebox.js");			
	}
}
