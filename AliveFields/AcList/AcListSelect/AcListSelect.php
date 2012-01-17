<?PHP
/**
 *  This control interacts with the standard SELECT html element on the frontside. 
 */

class AcListSelect extends AcList
{
    function get_field_type_for_javascript()
    { 
        return "AcSelectbox";
    }
}
