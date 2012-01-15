<?PHP
/**
 * The subclass of AcField that connects with the AcCheckbox javascript class.
 * It thus can be used with <input type="checkbox"> html element.
 *
 */
class AcCheckbox extends AcField 
{
function get_field_type_for_javascript()
	{
	return "AcCheckbox"	;
	}	
	
function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcCheckbox/AcCheckbox.js");			
	}
}
