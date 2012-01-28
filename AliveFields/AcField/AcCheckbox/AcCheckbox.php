<?PHP
/**
 * The subclass of AcField that connects with the AcCheckbox javascript class.
 * It thus can be used with <input type="checkbox"> html element.
 *
 */
class AcCheckbox extends AcField 
{
    /*
     * Find comments in parent class
     */
    function do_js_includes_for_this_control()
        {  //Unique to AcField
            AcField::include_js_file(AcField::PATH_TO_JQUERY);            
            AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");    
            AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcCheckbox/AcCheckbox.js");            
        }
        
    function get_field_type_for_javascript()
        {
        return "AcCheckbox"    ;
        }    
}
