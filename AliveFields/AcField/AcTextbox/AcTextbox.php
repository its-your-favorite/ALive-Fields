<?PHP

/**
 * The subclass of AcField that connects with the AcTextbox javascript class. 
 * It thus can be used with <input type="text"> or <textarea> html elements.
 * 
 */
class AcTextbox extends AcField
{
 function do_js_includes_for_this_control()
    {  //Unique to AcField
        AcField::include_js_file(AcField::PATH_TO_JQUERY);        
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");    
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcTextbox/AcTextbox.js");    //not a typo
    }    
    
 function get_field_type_for_javascript()
    {
    return "AcTextbox"    ;
    }    
}

