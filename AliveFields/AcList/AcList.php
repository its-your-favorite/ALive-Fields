<?PHP

/**
 *  AcList represents the backend controller for fields which have multiple
 *  simultaneous values (i.e. lists), including dropdown comboboxes and 
 *  SELECT boxes.  
 * 
 * @abstract
 */
abstract class AcList extends AcField {

    public $optionsField, $optionsTable, $optionsPkey, $optionsLoadability;

    /**
     * Constructor follows much the same format as AcField
     *  more comments to come...
     */
    function __construct($field, $table, $id, $loadable, $savable) {
        parent::__construct($field, $table, $id, (int) $loadable, (int) $savable);
        $tmp = &parent::get_session_object();
        $this->optionsField = $tmp['options_field'] = $field; //default to same values.
        $this->optionsTable = $tmp['options_table'] = $table;
        $this->optionsPkey = $tmp['options_pkey'] = $id;
        $this->optionsLoadability = $tmp['options_loadability'] = $loadable;
    }

    /**
     *
     * @param type $field
     * @param type $table
     * @param type $id
     * @param type $populatability 
     */
    function differentiate_options($field, $table, $id, $populatability) {
        $tmp = &$this->get_session_object();
        $this->optionsField = $tmp['options_field'] = $field; //default to same values.
        $this->optionsTable = $tmp['options_table'] = $table;
        $this->optionsPkey = $tmp['options_pkey'] = $id;
        $this->optionsLoadability = $tmp['options_loadability'] = $populatability;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    function bind($htmlElementId, $autoload = true) {
        $this->add_output($this->get_js_fieldname() . ".initialize(\"#" . $htmlElementId . "\", null, " . (int) $autoload . "); ");
    }

    /** This function includes all the necessary javascript files for the javascript widget
     * in the view that that connects with this controller.
     */
    function do_js_includes_for_this_control() {  //Unique to AcField
        AcField::include_js_file(AcField::PATH_TO_JQUERY);
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcSelectbox/AcSelectbox.js");
    }

    /**
     * Return the name of the (default) javascript widget (in the view) that sends request to
     * this object 
     */
    function get_js_field_type() {
        return "AcSelectBox";
    }

    /** This is where the controller actually handles an ajax request. 
     * 
     * The method is so complicated that it has been moved to another file (ajax_list.php)
     */
    function request_handler($request) {        
       if (($request['AcFieldRequest'] == 'loadfield') || ($request['AcFieldRequest'] == 'savefield')) {
            require_once (__DIR__ . "/../_internalInclude/ajax_field.php");
            return acField_Controller($this, $request);            
        } elseif ($request['AcFieldRequest'] == 'getlist') {
            require_once (__DIR__ . "/../_internalInclude/ajax_list.php");
            return acList_Controller($this, $request);            
        } else {
            return throw_error("Nonexistant Request");
        }
        
    }

}
