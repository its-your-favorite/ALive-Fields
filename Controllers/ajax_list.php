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
$this_field_session =& $_SESSION['_AcField'][$requester_page][$field_unique_id];
$this_field = AcField::instance_from_id($field_unique_id);

$field1 = _AcField_escape_field_name($this_field_session['options_pkey']);
$field2 = _AcField_escape_field_name($this_field_session['options_field']);
$table = _AcField_escape_field_name($this_field_session['options_table']);

$filtering = false;

$filter_fields = array();
$filter_vals = array();
//
/////////////////////////////

/*
var_dump($_SESSION);
echo "<BR><HR>$requester_page - $field_unique_id -<BR><HR>";
var_dump($request);
//var_dump($this_field_session);
echo "<hr>";
var_dump($_GET);*/

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check Security
if (($this_field_session["options_loadability"] == 1) && strlen($request["requester"]))
	{
	$found = false;
	foreach ($_SESSION['_AcField'][$requester_page][$request['requester']]["filtered_fields"] as $filter_set)
		if (($filter_set[0] == $this_field_session["unique_id"]) )
			$filtering = true;

	foreach ($request["filters"] as $filt) // make sure at least one filter is active. deny loading the whole table.
		if (in_array($request["requester"], $filt ))
			$found = true;
		
	if ((! $filtering) || (!$found))
		die("[]");//return empty result set. Proceeding would be a security issue. Don't generate an error
	}//Okay, we're loading through a filtered field
elseif ($this_field_session["options_loadability"] == 2)
	;// okay, we can load without filters
else
	{
	//var_dump($this_field_session);
	json_error("Field ($field_unique_id) not loadable.. ");
	}
// </ Check Security >
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Apply Filtering
if ($filtering)
	{if ($this_field->type_temp) //If we have a join table, then the filters apply to that table.
		list($filters, $this_field_session['filter_fields'], $this_field_session['filter_values'] ) = apply_list_filters(& $request, _AcField_escape_table_name($this_field->join_table), $field_unique_id);	
	else
		$filters = reset( apply_list_filters(& $request, $table, $field_unique_id) );	
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add in the requirement that the search term be present (ignoring case, partial match)
$conditions = $this_field_session['filters'];
if ($filtering)
	$conditions = array_merge($conditions, $filters);

if ($this_field->type_temp == 0) //not appropriate for select-joins
	$conditions[] = " UCASE($field2) like '%$term%'";
	
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
if ($this_field->type_temp == 0)
	{
	$query = "SELECT $distinct $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
	}
elseif ($this_field->type_temp == 1)
	{
	// unnecessary. delete this line. $this_field_session["loaded_pkey"] = 
	//$query = "SELECT $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
	$join_table = _AcField_escape_table_name($this_field->join_table);
	$join_to_right_field = _AcField_escape_field_name($this_field->join_to_right_field);
	$join_from_right_field = _AcField_escape_field_name($this_field->join_from_right_field);
	//Set the session thingy
	
	//. (LEFT or INNER) . 
	//We require as a means to tell which rows have an associated record in the left table [rather than just showing up from the RIGHT join]. 
	 $query = "SELECT  $join_table.$join_to_right_field as nada, $table.$field2 as label, $table.$field1 as value from $join_table " . "inner" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field " ." WHERE $conditions "; 
	
	 $query = "SELECT  $join_table.$join_to_right_field as isset, $table.$field2 as label, $table.$field1 as value from $join_table " . "RIGHT" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field AND $conditions " ; 
	 // Right join cannot handle a WHERE the way we want. This solution won't function properly if we have a right join (i.e. show EVERY record in the right table) that is trying to use filtering terms
	
	//echo $query;
	//die($query);
	}
$this_field_session["last_used_query"] = $query;
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