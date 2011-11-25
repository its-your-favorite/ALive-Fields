<?PHP
/*!
 * ALive Fields V 1.0
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 * Includes jquery.js, jqueryUI.js
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 *
 * Includes json2.js
 * http://www.JSON.org/json2.js
 *
 * Last Revision: 
 * Date: November 24 2011 1:15PM
 */

// To do:
// 	-Make select Multi that only shows actual links (read only) e.g. John's classes -> English, French
//	-Make select Multi that shows all possible links (writable) e.g. John's classes -> English*, French*, Spanish, History
// How to:
//  Override AcList? 
// 

// ERRORS: 
// -- [not reproducible] leaving a page while ajax request is out should not cause an error
// -- [fixed?] weird bug where field loads and closes itself.



//future additions:
// A) server side validations:
// 		// make them return the new value in the event that they change the value, so that the client-side control can immediately know what actually was saved.
//		// make them allow a certain specific error message on a validation fail.

// B) Instead of making this loose script in controllers to handle updates (i.e. ajax_field.php, ajax_script.php) put it inside the classes. This way everything stays object oriented.

// Investigate alternatives to using the session. 
// The complicated result of this though, if we wish to drop the whole SESSION reliance,  is that it will require giant validation queries, with multiple joins. For example if A updates B updates C and we try to set C to 6, we need to check B could contain 6 for a value that A could contain (and so on and so forth). OOOR. Encryption // RSA fingerprint. I can think of a simple way to do this with SHA but is it valid?

// 
// to do: turn off errors on most pages.
// -- discuss limitations  cannot operate on tables that don't have 1 single primary key.
// to do: test lock. Clean up 3 js files.
@session_start();
require_once("Controllers/query_wrapper.php");

global $PAGE_INSTANCE;
$PAGE_INSTANCE = md5(time());

function generate_unique_id()
{
	static $x;
	return ++$x;	
}

class AcField
{
	var $unique_id;
	protected $validators;
	public static $output_mode;
	public static $cached_output;
	public static $basics_included;
	protected static $all_instances;
	public $bound_field, $bound_table, $bound_pkey, $loadable, $savable, $filtered_fields, $filters;
	
	function AcField($field_type, $field, $table, $id, $loadable, $savable)
	{
		$this->include_basics();
		$this->validators = array();
		$data["bound_field"] = $this->bound_field = $field;
		$data["bound_table"] = $this->bound_table = $table;
		$data["bound_pkey"] = $this->bound_pkey = $id;
		$data["loadable"] = $this->loadable = $loadable;
		$data["savable"] = $this->savable = $savable;
		$data["filters"] = $this->filters = array(); //Filters applied TO this AcField
		$this->unique_id = generate_unique_id();
		AcField::$all_instances[$this->unique_id] = &$this;
		$data["unique_id"] = $this->unique_id;
		
		$tmp = &$this->get_session_object();
		$tmp = $data;
		
		$this->add_output( "\n\nvar " . $this->get_js_fieldname() . " = new $field_type($this->unique_id, $loadable, $savable, null); \n " . $this->get_js_fieldname() . ".uniqueId = '" . $this->unique_id . "';"); 	
		//JS needs the unique id so that when saving, it can determine what the originating field is.
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	// This function sets up a dependency for the page exactly once.
	function include_basics()
	{
		global $PAGE_INSTANCE;		
		if (! AcField::$basics_included)
			{
			AcField::$basics_included = true;
			AcField::$output_mode = "postponed";
			AcField::add_output("function AcFieldGetThisPage() { return '" . $_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  . "'; } ");
			}
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// This function registers a validator
	function register_validator($callback)
	{
		$our_version = explode(".",phpversion());
		if (is_array($callback))//using an assoc array
			{
			if (isset($callback["length"]))
				{					
				preg_match_all('/[0-9]?<' . '?' . '>' . '?=?/', $callback["length"], $matches);
				$length_expr = join($matches[0], "");

				if ($our_version[0] >= 5)
					{
					$this->register_validator(function($val) use ( $length_expr )
						{ 
						$x = strlen($val);
						return eval("return $x $length_expr;");
						});
					}
				}
			if (isset($callback['regex']))
				{

				$this->register_validator(function($val) use ($callback)
					{
					try
						{
						$res = preg_match($callback['regex'], $val);
						}
					catch (Exception $e)
						{
						json_error("Error in Regex Validator"); 
						}
					if ($res === false)
						json_error("error in regex validator");
					return $res;
					});
				}
				
			if (isset($callback['uniqueness']) || isset($callback['unique']))
				{
				if (isset($callback['uniqueness']))
					$callback['unique'] = $callback['uniqueness'];				
				$copy = $this;
				$this->register_validator(function($val, $pkey) use ($callback, $copy)
				 	{					
					$query = "SELECT count(*) as res from " . _AcField_escape_table_name($copy->bound_table) . " WHERE  " . _AcField_escape_field_name($copy->bound_field) . " = " . _AcField_escape_field_value($val) . " AND " ._AcField_escape_field_name($copy->bound_pkey) . " != " . _AcField_escape_field_value($pkey);
//					json_error($query);
					$result = _AcField_call_query_read($query) ;
//					json_error((bool)($callback['unique']));					
					return (($result[0]['res'] == 0) == (bool)($callback['unique'])) ;
					});				
				}
			}
		else
			$this->validators[] = $callback;
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// This function returns a particular instance from an ID (useful for passing IDs through session)
	static function instance_from_id($id)
	{
		if (!isset(static::$all_instances[$id]))
			return false;
		$tmp = static::$all_instances[$id];
		return $tmp;
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	// 
	function do_validations(& $prev_value, $key_val)
	{

		foreach ($this->validators as $validator)
			{
				if (!$validator($prev_value, $key_val))
					return false;
			}
		return true;
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	// 	
	function __toString()
	{
		return "This is an AcField";	
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// This function sets the APPLIED FILTERS to this field, that is to say, the filters that AFFECT THIS FIELD [NOT filters from this field]. This is opposite of Set_filtered_fields
	//  Accepts either a single filter [assoc array] or an array of filters [multidimensional array]
	// 	Each filter is an array with 3 elements: [field], [relationship], [value]   e.g. UserId > 6 or Username like 'Jon' or Age = 3
	function add_filters($filt) 
	{
		$tmp = &$this->get_session_object();
		if (is_array($filt))
			{
			if (is_array($filt[reset(array_keys($filt))])) // If the first element (of $filt) is itself an array i.e. this function is being passed an array of filters
				{
				foreach ($filt as $f)
					{			
					if (strpos($f[0], ".") === false)
						$f[0] = _AcField_escape_table_name($this->bound_table) .  "." . _AcField_escape_field_name($f[0]);
					$tmp["filters"][] = join($f, " ");
					$this->filters[] = join($f, " ");
					}					
				}
			else // only being passed one filter
				{
				$tmp["filters"] = $this->filters = array_merge($this->filters,  $filt);				
				}
			}
		else
			handleError("Must pass array to add_filters");
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 
	// This sets the array of filters that STEM FROM this field (not that apply to it). In this respect this fucntion is the opposite of add_filters.
	// Accepts an array of filters (assoc arrays) that have keys  [type] = ("value" / "text") , ['control'] = the actual AcField being updated
	function set_filtered_fields($filt)
	{
		$my_session_object = &$this->get_session_object();
		$my_session_object['filtered_fields'] = $this->filtered_fields = array();

		foreach ($filt as $i)
			{
			if (!isset($i['type']))
				$i['type'] = "value";
				
			$my_session_object['filtered_fields'][] = array($i['control']->unique_id, $i['field'], $i['type']);			
			$this->filtered_fields[] = array($i['control']->unique_id, $i['field'], $i['type']);			
			
			$new_filt[] = $i['control']->get_js_fieldname();	
			}
		$this->add_output($this->get_js_fieldname() . ".filteredFields = [" . join($new_filt, ", ") . "]; ");
		return NULL;	
	}

	//////////////////////////////////////
	function bind($html_element_id)
	{
		//spit out bound javascript	
		$this->add_output( $this->get_js_fieldname() . ".initialize(\"#" . $html_element_id . "\"); ");
	}
		
	function get_js_fieldname()
	{
		return  "AcField" . $this->unique_id;
	}
	
	function set_property($prop, $val)
	{
		$this->add_output($this->get_js_fieldname() . ".$prop = $val;");	
	}
		
	function &get_session_object()
	{
		global $PAGE_INSTANCE;		
 		if (!isset($_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id]))
			$_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id] = array();
			
		return 	$_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id];
	}
	
	function load_unchecked($key)
	{ //Take a key that refers to a primary key value in the table, and store it in the session to prevent client-side manipulation that would allow arbitrary load requests.
		$hardcoded_key_id = generate_unique_id();
		
		$tmp = &$this->get_session_object();
		$tmp['hardcoded_loads'][$hardcoded_key_id] = $key;

		$this->add_output($this->get_js_fieldname() . ".loadField( $hardcoded_key_id, 'static'); ");
	}
	
	function set_dependent_fields($arr)
	{
		$tmp = &$this->get_session_object();

		$this->add_output($this->get_js_fieldname() . ".dependentFields = [];");
		foreach ($arr as $i)
			{
			$tmp['dependent_fields'][] = $i->unique_id;
			$this->add_output($this->get_js_fieldname() . ".dependentFields.push ( " . $i->get_js_fieldname() . ");");
			}
 	}
	
	function differentiate_options($field, $table, $pkey)
	{
		//set the session variable to load options for these fields. 
		//?? Set the session variable to load/save previous field, not load options from them.
		
		//since no columns are used in javascript, javascript doesn't have to be updated?
		// but I do want savable and loadable to apply. Those make no sense before differentiate_options
	}
	
	static function add_output($js_code)
	{
		AcField::$cached_output .= $js_code . "\n";
			
		if (AcField::$output_mode != "postponed")
			AcField::flush_output();			
	}
	
	static function flush_output()
	{
		echo AcField::$cached_output;
		AcField::$cached_output = "";
	}
	
	static function handleRequests()
	{
		if (!isset($_REQUEST['request']))
			return; //No requests.
		else
			{
			$request = json_decode($_REQUEST['request'], true);
			if (!isset($request['AcFieldRequest']))
				return;
			elseif (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
				{
				require_once ("Controllers/ajax_field.php");
				die();
				}
			elseif ($request['AcFieldRequest'] == 'getlist')
				{
				//echo "LIST";
				require_once ("Controllers/ajax_list.php");
				die();
				}
			}		
	}
}

require_once('AcList.php');
?>
