<?php
/*!
 * ALive Fields V 1.0
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 *
 *
 * Last Revision: 
 * Date: Oct 11 2011 2:00PM
 */

function json_error($x)
{
	die(json_encode(array("criticalError" => $x)));	
} 

function auto_error($err,$b="",$c="",$d="",$e="")
{
	json_error( " " . implode(",", func_get_args()));
}

if (get_magic_quotes_gpc()) 
	{
    function stripslashes_gpc(&$value)
    	{
        $value = stripslashes($value);
    	}
	array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	}
	

//session_start();
//var_dump($_SESSION);
require_once ("query_wrapper.php");
require_once ("include.php");
_AcField_call_query_read("SELECT 1 ");

$request = json_decode($_REQUEST['request'], 1);

if (isset($request['term'])) 
	$term = $request['term'];
else
	$term = "";

error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
set_error_handler ("auto_error", E_ERROR | E_PARSE | E_USER_ERROR );

$requester_page = $request['requesting_page'];
$fieldUniqueId = $request['request_field'];
$maxRows = (int)$request['max_rows'];
$term = strtoupper(_AcField_escape_field_value($term, false));
$this_field =& $_SESSION['_AcField'][$requester_page][$fieldUniqueId];

$filtering = false;

/*
var_dump($_SESSION);
echo "<BR><HR>$requester_page - $fieldUniqueId -<BR><HR>";
var_dump($request);
//var_dump($this_field);
echo "<hr>";
var_dump($_GET);*/

if (($this_field["options_loadability"] == 1) && strlen($request["requester"]))
	{
//		blah broken.
	$found = false;
	foreach ($_SESSION['_AcField'][$requester_page][$request['requester']]["filtered_fields"] as $filter_set)
		if (($filter_set[0] == $this_field["unique_id"]) )
			$filtering = true;

	foreach ($request["filters"] as $filt) // make sure at least one filter is active. deny loading the whole table.
		if (in_array($request["requester"], $filt ))
			$found = true;
		
	if ((! $filtering) || (!$found))
		die("[]");//return empty result set. Proceeding would be a security issue. Don't generate an error
	}//Okay, we're loading through a filtered field
elseif ($this_field["options_loadability"] == 2)
	;// okay, we can load without filters
else
	{
	//var_dump($this_field);
	json_error("Field ($fieldUniqueId) not loadable.. ");
	}

$field1 = _AcField_escape_field_name($this_field['options_pkey']);
$field2 = _AcField_escape_field_name($this_field['options_field']);
$table = _AcField_escape_field_name($this_field['options_table']);

if ($filtering)
	{

	foreach ($request["filters"] as $filt)
		foreach ($filt as $source_control_id) //pick all enabled filtering controls
			{
			$source_field = $_SESSION['_AcField'][$requester_page][$source_control_id];
			$source_field_filters = $source_field["filtered_fields"];
			foreach ($source_field_filters as $filtering_type) //welcome to variable name hell.
				if ($filtering_type[0] == $fieldUniqueId)
					{
//	var_dump($request);
//  if (verify_key($request["requester_key"]))
// 		var_dump($filtering_type);
//							;						
 					if ($filtering_type[2] == "value")
						{
						verify_control_could_contain_value($requester_page, $request['requester'], $request["requester_key"], "pkey") or die("security issue 1"); 
						$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_key"]);
						}
					else
						{
						verify_control_could_contain_value($requester_page, $request['requester'], $request["requester_text"], "text") or die("security issue 2"); 							
						$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_text"]);					
						}
					}
			//look to see how those controls filter this field
			$filtering_info = $source_field;
			}		
	}

$conditions = $this_field['filters'];
$conditions[] = " UCASE($field2) like '%$term%'";
if ($filtering)
	$conditions = array_merge($conditions, $filters);
$conditions = join($conditions, " AND ");
$distinct = "";

if ($request['distinct'] )
	{
	if ($field1 != $field2)
		return json_error("Fields (pkey, value) must be the same in a distinct request");
	else
		$distinct = "distinct";
	}
$query = "SELECT $distinct $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
$this_field["last_used_query"] = $query;
$query .= "  ORDER BY $field2 ";
  
$DEBUG = false;
if ($DEBUG)
	echo $query;
	
$result = _AcField_call_query_read($query, (int)$maxRows);

if (is_null($result ) )
	echo "[]";
else
	echo json_encode($result);



?>