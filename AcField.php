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
 * Date: Oct 28 2011 1:15PM
 */

//future additions:
// to do: server side validations. 
// to do: make select Distinct Work, default, other things? 
// to do: improve code beauty and coder-friendliness
// -- adding rows, deleting rows
// join tables (which can be hidden and function through a select multiple)


//  to do: Check again if all pages can submit to themselves. This would totally avoid the need for a session usage. We would want to keep a unique key generated though, because of multiple instances and for security [just because you submit a request to administrate_users.php doesn't mean it CAME FROM administrate_users.php {which is a page only shown to admins}].  Or the security could be handled as described below: 
// perhaps something like  FIRST LINE: require_once 'validate_security.php'; //some non-AcControls-related security check. SECOND LINE: myAcControls();   THEN below we use: AcControls->dumpAll();
// The complicated result of this though is that it will require giant validation queries, with multiple joins. For example if A updates B updates C and we try to set C to 6, we need to check B could contain 6 for a value that A could contain (and so on and so forth).

// 
// to do: turn off errors on most pages.
// -- discuss limitations  cannot operate on tables that don't have 1 single primary key.

// to do: test lock. Clean up 3 js files.
// to do : differentiate options

session_start();
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
	public static $output_mode;
	public static $cached_output;
	public static $basics_included;
	
	function AcField($field_type, $field, $table, $id, $loadable, $savable)
	{
		$this->include_basics();
		$data["bound_field"] = $field;
		$data["bound_table"] = $table;
		$data["bound_pkey"] = $id;
		$data["loadable"] = $loadable;
		$data["savable"] = $savable;
		$data["filters"] = array();
		$this->unique_id = generate_unique_id();
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
	// See set_filtered_fields for information
	function add_filters($filt)
	{
		$tmp = &$this->get_session_object();
		if (is_array($filt))
			{
			if (is_array($filt[reset(array_keys($filt))]))
				{
				
				foreach ($filt as $f)
					{			
					if (strpos($f[0], ".") === false)
						$f[0] = $tmp["bound_table"] .  "." . $f[0];
					$tmp["filters"][] = join($f, " ");
					}					
				}
			else
				{
				$tmp["filters"] = array_merge($tmp["filters"],  $filt);
				}
			}
		else
			handleError("Must pass array to add_filters");
	}
	
	//*Define stuff here on how this function works*	
	function set_filtered_fields($filt)
	{
		$my_session_object = &$this->get_session_object();
		$my_session_object['filtered_fields'] = array();

		foreach ($filt as $i)
			{
			if (!isset($i['type']))
				$i['type'] = "value";
			$my_session_object['filtered_fields'][] = array($i['control']->unique_id, $i['field'], $i['type']);			
			$new_filt[] = $i['control']->get_js_fieldname();	
			}
		$this->add_output($this->get_js_fieldname() . ".filteredFields = [" . join($new_filt, ", ") . "]; ");
		return NULL;	
	}

	function bind($html_element_id)
	{
		//spit out bound javascript	
		$this->add_output( $this->get_js_fieldname() . ".initialize(\"#" . $html_element_id . "\"); ");
	}
		
	function get_js_fieldname()
	{
		return  "AcField" . $this->unique_id;
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
}

require_once('AcList.php');
?>
