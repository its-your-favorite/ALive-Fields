<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AcListCombo extends AcList
{
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(AcField::$path_to_jqueryui);
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcSelectbox.js");
		AcField::include_js_file(Acfield::$path_to_controls . "/AcCombobox/AcCombobox.js");
	}	
	
	function get_field_type_for_javascript()
	{
		return "AcCombobox";	
	}
}
