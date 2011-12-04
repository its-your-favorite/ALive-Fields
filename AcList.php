<?PHP
// Requires AcField. Include AcField directly which will include this.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

abstract class AcList extends AcField
{	
	public $options_field, $options_table, $options_pkey, $options_loadability;
	
	function AcList($field, $table, $id, $loadable, $savable)
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
	//
	function request_handler($request)
	{		
		if (!isset($request['AcFieldRequest']))
				return;
		elseif (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
				{
				require_once ("Controllers/ajax_field.php");
				die();
				}
		elseif ($request['AcFieldRequest'] == 'getlist')
				{
				require_once ("Controllers/ajax_list.php");
				die();
				}
	}
	
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcSelectbox.js");
	}
	
	function get_js_field_type()
	{
		return "AcSelectBox";	
	}
}

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


class AcListSelect extends AcList
{
	function get_field_type_for_javascript()
	{ 
		return "AcSelectbox";
	}
}
//////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// 
/////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// /////// 

class AcListJoin extends AcList // used for lists that represent join tables and thus pull from a join table on the backend but look like plain old (multi-select) lists on the front end
{
	public  $join_table, $join_to_right_field, $join_from_right_field;
	private $multi_validators;
	
	function AcListJoin($field, $table, $id, $join_table, $join_to_right_field, $join_from_right_field, $loadable, $savable)
	{
		parent::__construct($field,$table,$id,(int)$loadable,(int)$savable);
		$this->join_to_right_field = $join_to_right_field;
		$this->join_from_right_field = $join_from_right_field;
		$this->join_table = $join_table;
		$this->type_temp = 1;
		$this->multi_validators = array();
	}	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//	
	function get_field_type_for_javascript()
	{ 
		return "AcJoinSelectbox";
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
		AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcSelectbox.js");
		AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcJoinSelectbox.js");
	}	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	function request_handler($request)
		{
		if (!isset($request['AcFieldRequest']))
				return;
		elseif (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
				{
				require_once ("Controllers/ajax_field_multiple.php");
				handle_multiple_field($request); //sole function in above file.
				die();
				}
		elseif ($request['AcFieldRequest'] == 'getlist')
				{
				//Ajax list has been enhanced to handle JoinTable type requests. It's not as OO as it could be, but the code is 95% the same, so it's the better solution.
				require_once ("Controllers/ajax_list.php");
				die();
				}
		}
		
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// 
	function do_multi_validations(& $prev_value, $key_val)
	{
		//NEED TO WRITE AN INSERT HANDLER FOR THIS.
		foreach ($this->multi_validators as $validator_multi)
			{
				if (!$validator_multi($prev_value, $key_val))
					return false;
			}
		return true;
	}	//////////////////////////////////////////////////////////////////////////////////////////////////
	// 
	function do_insert_validations(& $prev_value_assoc_array)
	{
		//NEED TO WRITE THIS . Maybe move it to AcField
		foreach ($this->multi_validators as $validator_multi)
			{
				if (!$validator_multi($prev_value, $key_val))
					return false;
			}
		return true;
	}
}
?>
