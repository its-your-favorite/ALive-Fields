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
 * Date: Nov 24 2011 2:00PM
 */
@session_start();

function json_error($x)
{
	die(json_encode(array("criticalError" => $x)));	
} 

function auto_error($err,$b="",$c="",$d="",$e="")
{
	json_error( " " . implode(",", func_get_args()));
}


//session_start();
//var_dump($_SESSION);
require_once ("query_wrapper.php");
require_once ("include.php");

remove_magic_quotes_if_present();

$request = json_decode($_REQUEST['request'], 1);

error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
set_error_handler ("auto_error", E_ERROR | E_PARSE | E_USER_ERROR );

/////////////////////////////
// Shorthand variables 
$requester_page =& $request['requesting_page'];
$field_unique_id = $request['request_field'];
$term = strtoupper(_AcField_escape_field_value($request['term'], false));
$this_field =& $_SESSION['_AcField'][$requester_page][$field_unique_id];

$field1 = _AcField_escape_field_name($this_field['options_pkey']);
$field2 = _AcField_escape_field_name($this_field['options_field']);
$table = _AcField_escape_field_name($this_field['options_table']);

$filtering = false;
//
/////////////////////////////

/*
var_dump($_SESSION);
echo "<BR><HR>$requester_page - $field_unique_id -<BR><HR>";
var_dump($request);
//var_dump($this_field);
echo "<hr>";
var_dump($_GET);*/

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check Security
if (($this_field["options_loadability"] == 1) && strlen($request["requester"]))
	{
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
	json_error("Field ($field_unique_id) not loadable.. ");
	}
// </ Check Security >
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Apply Filtering
if ($filtering)
	$filters = apply_list_filters(& $request, $table, $field_unique_id);	

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add in the requirement that the search term be present (ignoring case, partial match)
$conditions = $this_field['filters'];
$conditions[] = " UCASE($field2) like '%$term%'";
if ($filtering)
	$conditions = array_merge($conditions, $filters);
$conditions = join($conditions, " AND ");
$distinct = "";

// Handle request distincts
if ($request['distinct'] )
	{
	if ($field1 != $field2)
		return json_error("Fields (pkey, value) must be the same in a distinct request");
	else
		$distinct = "distinct";
	}
// 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
// Produce Result
$query = "SELECT $distinct $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
$this_field["last_used_query"] = $query;
$query .= "  ORDER BY $field2 ";
  
$DEBUG = false;
if ($DEBUG)
	echo $query;
	
$result = _AcField_call_query_read($query, (int)$request['max_rows']);

if (is_null($result ) )
	echo "[]";
else
	echo json_encode($result);



?>