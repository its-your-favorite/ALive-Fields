<?PHP

// Requires AcField. Include AcField directly which will include this.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 *  AcList represents the backend controller for fields which have multiple simultaneous values (i.e. lists),
 *  including dropdown comboboxes and SELECT boxes.
 * 
 */
abstract class AcList extends AcField
{	
	public $options_field, $options_table, $options_pkey, $options_loadability;
	
	/**
	 * Constructor follows much the same format as AcField
	 *  more comments to come...
	 */
	function __construct($field, $table, $id, $loadable, $savable)
	{
		parent::__construct($field,$table,$id,(int)$loadable,(int)$savable);
		$tmp = &parent::get_session_object();
		$this->options_field = $tmp['options_field'] = $field; //default to same values.
		$this->options_table = $tmp['options_table'] = $table;
		$this->options_pkey = $tmp['options_pkey'] = $id; 				
		$this->type = $tmp['type'] = "multi";
		$this->options_loadability = $tmp['options_loadability'] = $loadable;
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	function differentiate_options($field, $table, $id, $populatability)
	{
		$tmp = &$this->get_session_object();
		$this->options_field = $tmp['options_field'] = $field; //default to same values.
		$this->options_table = $tmp['options_table'] = $table;
		$this->options_pkey = $tmp['options_pkey'] = $id; 		
		$this->options_loadability = $tmp['options_loadability'] = $populatability;
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	function bind($html_element_id, $autoload = true)
	{
		$this->add_output( $this->get_js_fieldname() . ".initialize(\"#" . $html_element_id . "\", null, " . (int)$autoload . "); ");			
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/* This is where the controller actually handles an ajax request. 
	 * 
	 * The method is so complicated that it has been moved to another file (ajax_list.php)
	 */
	function request_handler($request)
	{		
		if (!isset($request['AcFieldRequest']))
				return;
		elseif (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
				{
				require_once (Acfield::$path_to_start_php . "/ajax_field.php");
				acField_Controller($this);
				die();
				}
		elseif ($request['AcFieldRequest'] == 'getlist')
				{
				require_once (Acfield::$path_to_start_php . "/ajax_list.php");
				acList_Controller($this);
				die();
				}
	}
	
	/* This function includes all the necessary javascript files for the javascript widget
	 * in the view that that connects with this controller.
	 */
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcSelectbox.js");
	}
	
	/*
	 * Return the name of the (default) javascript widget (in the view) that sends request to
	 * this object 
	 */
	function get_js_field_type()
	{
		return "AcSelectBox";	
	}
}
