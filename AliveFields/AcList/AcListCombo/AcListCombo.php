<?PHP
/**
 *  A list combo-box. This uses jQuery UIs autocomplete combo-box on the front-end
 * 
 * A combo box is both a SELECT dropdown and a TEXT box at the same time. 
 * 
 * It applies to input type = text which is then enhanced by jqueryUI.
 * 
 * @requires jQueryUI
 */

class AcListCombo extends AcList
{
    function do_js_includes_for_this_control()
    {  //Unique to AcField
        AcField::include_js_file(AcField::PATH_TO_JQUERY);            
        AcField::include_js_file(AcField::PATH_TO_JQUERYUI);
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");    
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcSelectbox/AcSelectbox.js");
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcCombobox/AcCombobox.js");
    }    
    
    function get_field_type_for_javascript()
    {
        return "AcCombobox";    
    }
}
