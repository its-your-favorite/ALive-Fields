<?PHP
/*!
 * Main Class file, containing the base class for all PHP controls.
 * 
 * This abstract base class performs all the vital functions to the page including:
 * 	- creating and outputting necessary javascript to power client-side interactivity
 *  - outputting necessary includes dynamically for relevant javascript files
 *  - storing necessary security information in the session
 *  - handling and directing client side requests to the appropriate controllers
 *  - passing client side requests to validators.
 *  - tl;dr : everything except the html view.
 * 
 * @todo actually put different classes in different directories. Not just the two.
 * @todo remove version numbers from all other files
 * @todo put php docs in a lot of places (files, classes, functions) 
 * 		-Done on AcField.php
 * 		THen auto-generate some documentation
 * @todo destroy all references to temp variable
 * @todo move generate_unique_id out of global scope
 * @todo cleanup: Alphabetize function names. 
 * @todo cleanup: make a couple unit tests.
 * @todo Make sure validations work of both types on the new whatcha-ma-call-it list
 * @todo to do: turn off errors on most pages. 
 * @todo to do: differentiate options does nothing?
 * @todo to do: try two fields filtering one, to be sure it works
 * @todo add validations for join table multi
 * @todo clean up javascript by using parent instead of AcWHATEVER (if possible)
 *  	and use the type of call that automatically passes params as an array.
 * @todo To do: ReMake a date-time control.
 * @todo Testing -- locking on all types of fields
 * 
 * @todo make sure my jsDoc is up to date
 * @todo C) A way to instead of making things auto-save, allow them to be 
 *	 controlled by two buttons (auto-inserted) that read "Save" or
 * 	 "Restore Value". Key is easy extensibility!
 * @todo See if I can trim down my controllers enough that it's not
 *  necessary to keep them in separate files
 * 
 * @todo A) server side validations: make them return the new value in the 
 * event that they change the value, so that the client-side control can 
 *	 immediately know what actually was saved.
 * @todo B) Validations: make them allow a certain specific error message on a
 * 	 validation fail. 
 *
 * @todo test out this pretty jqUI stuff here: 
 * 		http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/
 * @todo to do: Consider connecting my app with ZF
 * 
 * @todo Investigate alternatives to using the session.  The complicated result
 *  of this though, if we wish to drop the whole SESSION reliance,  is that it
 *   will require giant validation queries, with multiple joins. For example if
 *    A updates B updates C and we try to set C to 6, we need to check B could
 *     contain 6 for a value that A could contain (and so on and so forth). 
 *     OOOR. Encryption // RSA fingerprint. I can think of a simple way to do 
 *     this with SHA but is it valid?
 *     
 * @version 1.01
 * 
 * @author Alex Rohde http://alexrohde.com/
 * @copyright Alex Rohde 2011
 * @license GPL Version 2 license.
 *
 * Includes jquery.js, jqueryUI.js
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 *
 * Includes json2.js
 * http://www.JSON.org/json2.js
 *
 * Last Revision: 
 * Date: January 14 2012 
 */
 
@session_start();

global $PAGE_INSTANCE;
$PAGE_INSTANCE = md5(time());

function generate_unique_id()
{
	static $x;
	return ++$x;	
}


/**	AcField is the PHP heart of this project. 
 * 
 * It is the base class from which all other
 * components derive (much in the way that AcControl is in the javascript portion). 
 * 	All of these classes act as controllers, handling various ajax requests and connecting themselves to your view (which is pure HTML).
 * 
 *  @abstract
 *  
 */

abstract class AcField 
{
	var $unique_id;
	protected $validators;
	
	public static $included_js_files; // to prevent double-inclusion of controls
	
	public static $output_mode = "postponed"; 
	public static $cached_output;
	public static $cached_head_output; 
	public static $basics_included;
	
	public static $include_directory_js = "/js";
	public static $path_to_start_php = "/AliveFields";
	public static $path_to_jquery = "/jquery.js";
	public static $path_to_jqueryui = "/jquery-ui.js";
	public static $path_to_controls = "/controls";
	public static $include_js_manually = false; //by default, handle including JS files for the user
	private static $declaration_progress = 0; //Help new users of the library avoid basic errors.
	
	protected static $all_instances;

	/* Enable soon: 
	 * const NoRead = 0;
	 * const ReadWhenFiltered = 1;
	 * const YesRead = 1;
	 * 
	 * const NoChangeNoSave = ?
	 * const NoSave = 0;
	 * const YesSave = ?; 
	 */
	//Definitions to explain the following fields:

	public $bound_field, $bound_table, $bound_pkey, $filters;
	public $loadable, $savable; //whether this control can LOAD and or SAVE to the database
	public $type_temp, $type;
	public $filtered_fields; //keep public!
		
	/**
	 * 
	 * A control's internal value will be: SELECT $bound_pkey FROM $bound_table 
	 * A control's displayed value will be: SELECT $bound_field FROM $bound_table WHERE $bound_pkey = (loaded_value) [AND $filters clauses here]
	 * 
	 */
	function __construct($field, $table, $id, $loadable, $savable)
	{
		$type_temp = 0;
		$this->include_basics();
		$this->validators = array();
		$this->filtered_fields = array();
		$this->type = "single"; //necessary? If not remove from here and AcList.php

		$this->bound_field = $field;
		$this->bound_table = $table;
		$this->bound_pkey = $id;
		
		$data["loadable"] = $this->loadable = $loadable;
		$data["savable"] = $this->savable = $savable;
		$data["filters"] = $this->filters = array(); //Filters applied TO this AcField
		$this->unique_id = generate_unique_id();
		AcField::$all_instances[$this->unique_id] = &$this;
		$data["unique_id"] = $this->unique_id;
		
		if (AcField::$declaration_progress > .25)
			AcField::register_error("Please declare all AcFields before proceeding to call handle_all_requests");
		AcField::$declaration_progress = .25;
		
		$tmp = &$this->get_session_object();
		$tmp = $data;
		
		$this->add_output( "\n\nvar " . $this->get_js_fieldname() . " = "  
						 . " new " . $this->get_field_type_for_javascript() 
						 . "($this->unique_id, $loadable, $savable, null); \n "
						 . $this->get_js_fieldname() . ".uniqueId = '" . $this->unique_id . "';"); 	
	
		if (! AcField::$include_js_manually)
			$this->do_js_includes_for_this_control();
		//JS needs the unique id so that when saving, it can determine what the originating field is.
	}

	/**
	 * Destructor. Provide useful error message in event of newbie mistake.
	 */
	function __destruct()
	{		
		if (AcField::$declaration_progress < 2)
			AcField::register_error("Flush output should be called somewhere in the document.");
	}

	/**
	 * @todo enhance
	 */
	function __toString()
	{
		return "This is an AcField";	
	}
	
	
	/**
	 * This function sets the filters applied *TO* this field. That is to say, 
	 * the filters that *affect this field* [NOT filters from this field]. 
	 * This is opposite of Set_filtered_fields
	 * 
	 * @param [multiple] $filt Accepts either a single filter [assoc array] or an array of filters [multidimensional array]
	 * Each filter is an array with 3 elements: [field], [relationship], [value]   e.g. UserId > 6 or Username like 'Jon' or Age = 3
	 */	
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
	
	/**
	 * Add output that should be displayed in the head (namely javascript includes)
	 */
	static function add_head_output($html_code)
	{
		AcField::$cached_head_output .= $html_code . "\n";
	}
	
	/**
	 * Displays provided javascript appropriately.
	 */
	static function add_output($js_code)
	{
		AcField::$cached_output .= $js_code . "\n";
			
		if (AcField::$output_mode != "postponed")
			AcField::flush_output();			
	}
	
	
	/**
	 * Inform the javascript class which HTML element id it should connect with.
	 */
	function bind($html_element_id)
	{	//spit out bound javascript	
		$this->add_output( $this->get_js_fieldname() . ".initialize(\"#" . $html_element_id . "\"); ");
	}
		
	/**
	 * Determine if this field meets all validator criteria
	 * @return boolean 
	 */
	function do_validations(& $prev_value, $key_val)
	{
		foreach ($this->validators as $validator)
			{
				if (!$validator($prev_value, $key_val))
					return false;
			}
		return true;
	}

	/** 
	 * 
	 */
	function differentiate_options($field, $table, $pkey)
	{
		throw Exception("Not implemented");
		// Notes to self:
		//set the session variable to load options for these fields. 
		//?? Set the session variable to load/save previous field, not load options from them.
		
		//since no columns are used in javascript, javascript doesn't have to be updated?
		// but I do want savable and loadable to apply. Those make no sense before differentiate_options
	}
	
	/**
	 * Include the relevant javascript files necessary to power the view.
	 */
	function do_js_includes_for_this_control()
	{  //Unique to AcField
		AcField::include_js_file(AcField::$path_to_jquery);			
		AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");	
	}
	

	/**
	 * Dumps includes that need to be in the HEAD of the html document
	 */
	static function flush_head_output() 
	{
		echo AcField::$cached_head_output; 
		AcField::$cached_head_output = "";
		
		//Generate useful error messages in the event that a new user declares things out of order.
		if (AcField::$declaration_progress < .5)				
			AcField::register_error("Please call handle-all-requests before any "
									. " output and call flush_head_output in the " 
									. " head of the document. Thus flush_head_output" 
									. " should not be called first. ");
		elseif (AcField::$declaration_progress != .5)
			AcField::register_error("Flush head output should be called one time, before flush " 
									. "output, after handle_all_requests, and in the HEAD section"
									. " of the HTML document");
		AcField::$declaration_progress=1;
	}
	

	/**
	 * Outputs the entire output buffer. 
	 * 
	 * This is necessary because controls are declared before the document start, where obviously
	 * javascript cannot be displayed yet.  
	 */
	static function flush_output()
	{
		//Generate useful error messages in the event that a new user declares things out of order.		
		if (!AcField::$declaration_progress && !AcField::$include_js_manually)
			AcField::register_error("Be sure to flush head output before flushing output.");
			
		AcField::$declaration_progress=2;			
		echo AcField::$cached_output;
		AcField::$cached_output = "";
	}
	
	/**
	*	Outputs the decided headers for a controller's ajax response to the view.
	*/
	public function generate_controller_header() //ideally make private at some point
	{
		header('Expires: Fri, 09 Jan 1981 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', FALSE);
		header('Content-Type: text/html; charset=iso-8859-1'); //not application/json for many good complicated reasons.
		header('Pragma: no-cache');	
	}

	/**	 
	 * @return string  
	 */
	abstract function get_field_type_for_javascript();	
	
	
	/**
	 *	This function returns a particular instance from an ID (useful for passing IDs through session)
	 *
	 * Every instance of AcField is generated a unique id (integer). This allows us to have one unique 
	 * 	number that acts as an identifier *across requests* to show us where to direct controller requests to.
	 * 
	 * This function convers such a unique id back into the relevant instance of AcField
	 * 
	 * @return AcField
	 */
	
	static function instance_from_id($id)
	{
		if (!isset(static::$all_instances[$id]))
			return false;
		$tmp = static::$all_instances[$id];
		return $tmp;
	}
	
	/**
	 * Provides the instance name of the javascript object that this class communicates with.
	 * 
	 * @return string
	 */		
	function get_js_fieldname()
	{
		return  "AcField" . $this->unique_id;
	}
	
	/**
	 * 
	 */		
	function &get_session_object()
	{
		global $PAGE_INSTANCE;		
 		if (!isset($_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id]))
			$_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id] = array();
			
		return 	$_SESSION['_AcField'][$_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE  ][$this->unique_id];
	}
	
	/**
	*	Dispatch the request to the appropriate Acfield's request_handler, so it can act as the controller.
	* 
	*/
	static function handle_all_requests()
	{   // In release mode, I recommend changing these $_REQUEST to post for a minor reduction in xsrf risk, and just for consistency with the meaning of GET and POST.

		if (AcField::$declaration_progress < .25)				
			AcField::register_error("Please declare your AcFields before calling handle_all_requests");
		AcField::$declaration_progress = .5;

		if (!isset($_REQUEST['request']))
			return; //No ajax requests. I.E. We're just loading the page normally.
		else
			{			
			AcField::$declaration_progress = 100; //Don't monitor declaration progress in ajax 
									// request mode, we obviously don't want javascript declared.
			$request = json_decode($_REQUEST['request'], true);		
			AcField::instance_from_id($request['request_field'])->request_handler($request);
			}		
	}
	
	/**
	 * This function sets up a dependency for the page exactly once.
	 */
	function include_basics()
	{
		global $PAGE_INSTANCE;		
		if (! AcField::$basics_included)
			{
			AcField::$basics_included = true;
			AcField::$output_mode = "postponed";
			AcField::add_output("function AcFieldGetThisPage() { " 
								. " return '" . $_SERVER['PHP_SELF'] . " " . $PAGE_INSTANCE . "'; }");
			}
	}
	
	/**
	 * Includes a given javascript file
	 * 
	 * @param string $file a javascript filename
	 */
	static function include_js_file($file)
	{
		// These 6 lines ensure that includes don't accidentally double or omit the / for paths.
		if (strlen(AcField::$include_directory_js) && (substr(AcField::$include_directory_js, 0, 1) == '/') )
			AcField::$include_directory_js = ltrim(AcField::$include_directory_js, "/");
		if (strlen(AcField::$include_directory_js) && (substr(AcField::$include_directory_js, -1) == '/') )
			AcField::$include_directory_js = rtrim(AcField::$include_directory_js, "/");
		if (strlen($file) && (substr($file,0,1) != '/'))
			$file = '/' . $file; 
		if (isset(AcField::$included_js_files[$file])) //prevent double-inclusion of files
			return;

		AcField::$included_js_files[$file] = true;
		AcField::add_head_output("<script language='javascript' src='" . AcField::$include_directory_js . "$file'></script>");	
	}
	
	/**
	 *	Load a value of a field based on a specified key. 
	 */
	function load_unchecked($key)
	{ //Take a key that refers to a primary key value in the table, and store it in the session to 
		// prevent client-side manipulation that would allow arbitrary load requests.
		$hardcoded_key_id = generate_unique_id();
		
		$tmp = &$this->get_session_object();
		$tmp['hardcoded_loads'][$hardcoded_key_id] = $key;

		$this->add_output($this->get_js_fieldname() . ".loadField( $hardcoded_key_id, 'static'); ");
	}

	/**
	 * outputs library-generated errors.
	 * 
	 * Alter this function as appropriate for your level of experience, error reporting system, and development/release systems.
	 */
	static function register_error($string)
	{	// 
		trigger_error("Error: $string. <br>\n You may find the wiki useful: https://github.com/anfurny/ALive-Fields/wiki/Using-the-Library");
		die();
	}	
	
	/**
	 * This function registers a validator
	 */
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
					$query = "SELECT count(*) as res from " . _AcField_escape_table_name($copy->bound_table) . " WHERE  " 
							. _AcField_escape_field_name($copy->bound_field) . " = " . _AcField_escape_field_value($val) 
							. " AND " ._AcField_escape_field_name($copy->bound_pkey) . " != " . _AcField_escape_field_value($pkey);
					$result = _AcField_call_query_read($query) ;
					return (($result[0]['res'] == 0) == (bool)($callback['unique'])) ;
					});				
				}
			}
		else
			$this->validators[] = $callback;
	}
		
	/**
	 * This function determine loads and executes the relevant controller for this request.
	 * 
	 *  This handles the back-end power ajax requests submitted by the views, for this particular field.
	 *  This acts as the controller to the view request.
	 */
	function request_handler($request)
	{			
		if (!isset($request['AcFieldRequest']))
				return;
		elseif (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
				{
				require_once (Acfield::$path_to_start_php . "/ajax_field.php");
				acField_Controller($this);
				die(); //important, otherwise the view would be included in the controller response.			
				}
		else
			; //Probably called by a subclass, continue execution normally			
	}
	
	
	/**
	 * Set the list of fields that this field will call load upon when this field changes value.
	 *
	 * @see documentation
	 * @param array of AcField $arr the fields to update
	 */
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
	
	/**
	* This sets the array of filters that STEM FROM this field (not that apply to it). In this respect this function is the opposite of add_filters.
	* Accepts an array of filters (assoc arrays) that have keys  [type] = ("value" / "text") , ['control'] = the actual AcField being updated
	*/
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
	
	/**
	 * Sets an attribute of the corresponding javascript class instance.
	 */	
	function set_property($prop, $val)
	{
		$this->add_output($this->get_js_fieldname() . ".$prop = $val;");	
	}
	
}

?>
