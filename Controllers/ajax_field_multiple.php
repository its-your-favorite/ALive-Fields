<?PHP
/*!
 * ajax_list_multiple.php
 * Copyright Alex Rohde 2011. Part of ALive Fields project.  https://github.com/anfurny/ALive-Fields
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 * Last Revision: 
 * Date: Dec 5 2011 7:45PM
 *
 * Purpose:  This file houses the ajax handler / controller for AcJoinSelect write operations.
 */


// Security concerns and how/where they are protected against in this file
// 
// A) Passing an array of made up integers, or even non-integers from client side.
//	*) Handled in verify_control_could_contain_value_set. 
//  Status: Untested
//
// B) SQL Injection
//  *) Escaping
//  Status: Untested
//
// C) Trying to get the control to delete from join table inappropriately
//  *) Filters should take care of this
//	Status: Not explored in depth

function handle_multiple_field($request)
 {	
	header('Expires: Fri, 09 Jan 1981 05:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', FALSE);
	header('Content-Type: text/html; charset=iso-8859-1');
	header('Pragma: no-cache');
	
	require_once "query_wrapper.php";
	require_once "include.php";
	remove_magic_quotes(); // In the event your webserver has them enabled and doesn't give you the option to change it
	
	error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
	set_error_handler ("auto_error", E_ERROR | E_PARSE | E_ALL ^ E_NOTICE );
	
	global $sql; // There's a very good reason for this... I just don't remember
				// it at the second.
	
	/*
	 *  Load the request information into more-readable variables.
	*/
	$dataRequest = $request;
	$requester_page = $dataRequest['requesting_page'];
	$fieldUniqueId = $dataRequest['request_field'];
	$sourceUniqueId = $dataRequest['source_field'];	
	
	$this_field_session =& $_SESSION['_AcField'][$requester_page][$fieldUniqueId];
	$this_field = AcField::instance_from_id($fieldUniqueId);
	
	$table = $this_field->bound_table;
	$values = $dataRequest["fieldInfo"];
	
	//Post Val represents the data from tho multi-select as an array of keys e.g. [1,3,5]
	$post_val = NULL; 

	if ($this_field->mode == "limited")
		json_error("expectedError"); // No updating a limited field.
		

	//////////////////////////////////////////////////////////////////////////////////	
					
	if (($dataRequest['action'] === "save"))
		{		
		if ($this_field->savable < 1)
			die(json_error("Field not savable.")); //security violation
		if (count($values) > 1)
			die(json_error("Multiple values not implement" ));
		foreach ($values as $x) //so even though we are looping right here, as written controls can only update 1 field per ajax request. This loop is more for theoretical future use then?
			{		
			$fields_arr[] = _AcField_escape_field_name($this_field->bound_field);
			$values_arr[] = array( _AcField_escape_field_value($x[1]) ) ;
			}
		$post_val = json_decode($x[1], true);
		// ** I need to analyze this more
		// So we only save to a Select in the event that it has differentiateOptions (right?) 
		// probably eventually change this to an accessor? So it can be overloaded differently by subclasses?
		verify_control_could_contain_value_set($requester_page, $fieldUniqueId, $post_val /*don't escape. Checked outside of a query*/ /*, "optionValue"*/) or json_error("expectedError");
		}
	/*elseif (($dataRequest['action'] === "insert"))
		{
		foreach ($values as $x)
			{
			$values_clause1[] = cleanFieldName($x[0]);
			$values_clause2[] = cleanFieldValue($x[1]);
			$values_clause = " (" . join(",", $values_clause1) . ") VALUES (" . join(",", $values_clause2) . ") " ;
			
			if (isset($where_clause))
				trigger_error("Cannot use limiting conditions in an insert", E_USER_ERROR );
				
			if (strpos($SECURITY_PERMISSIONS["normal"][$table], "W") === false)
				trigger_error("Insufficient Permissions to write to table -$table-", E_USER_ERROR );
			}		
		}
	/* Disabled. This method of doing this is just begging for errors.
	*/
	/*elseif (($dataRequest['action'] == "append"))
		foreach ($values as $x)
			{
			$values_clause[] = "[" . cleanFieldName($x[0]) . "]" . " =  " . "[" . cleanFieldName($x[0]) . "] + " . cleanFieldValue($x[1], 1) . " ";
			if (strpos($SECURITY_PERMISSIONS["normal"][$table], "R") === false)
				trigger_error("Insufficient Permissions to read from table $table");
			}		*/
	elseif (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load")	)
		{
		die(json_error("Cannot do load in this. Presumably unnecessary."));
		// think about this more later.
			/*
		if (! $this_field->loadable)
			die("Field not loadable"); //security violation
			
		$values_clause = $this_field->bound_field . " as answer ";
	
		if ($dataRequest['action'] === "dynamic_load")	
			{
			$where_clause['key_piece'] = _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name( $this_field->bound_pkey ) . " = " . _AcField_escape_field_value($dataRequest['primaryInfo'][1]);
			if ( count($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters']) )
				{
				$source_field = $_SESSION['_AcField'][$requester_page][$sourceUniqueId];
							
				if (! in_array($this_field_session["unique_id"], $source_field['dependent_fields']) ) //verify that this control is indeed allowed to update the other control. Do this verification by looking at session records.
					die("NOT A MATCH");
					
				$join_clause[] = $source_field['bound_table'] . " ON " . _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name($this_field->bound_pkey) . " = " . _AcField_escape_table_name($source_field['bound_table']) . "." . _AcField_escape_field_value($source_field["bound_pkey"]);
				if (count($join_clause))
					$join_clause = "INNER JOIN " . join($join_clause, " INNER JOIN ");
				$this_field_session['loaded_join_clause'] = $join_clause;			
				$where_clause['join_piece'] = join($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters'], " AND ");
				}
			$this_field_session['loaded_pkey'] = $dataRequest['primaryInfo'][1] ;
			}
		else
			{
			$where_clause[] = _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name($this_field->bound_pkey) . " = " . _AcField_escape_field_value($this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]]);
			$this_field_session['loaded_pkey'] = $this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]];
			}
		$this_field_session['loaded_where_clause'] = $where_clause;
		*/}
	else
		{
		trigger_error("Unknown action type requested in ajax_field: " . $dataRequest['action'], E_USER_ERROR );
		}
	
	/*if (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load"))
		{
		$sql = "SELECT count(*) as count_rec FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
		//echo $sql;
		$security_check = _AcField_call_query_read($sql);
		if (! $security_check[0]['count_rec'])
			json_error("Record not found.");
				
		$sql = "SELECT $values_clause FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
		$result = _AcField_call_query_read($sql);
		$result = array("value" => $result[0]['answer']);
		}
	else*/if ($dataRequest['action'] === "save")//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		{			
		if (! isset($this_field_session['filter_fields']))
			die(json_error("This library isn't currently designed to handle a save before a load.")); //library isn't currently designed to securely handle a save before a load. 
					
		if (! is_array($post_val))
			json_error("Invalid parameter #17");
		//Two STEP VALIDATION PROCESS. ONE pass the list of fields to be updated (so a validator can verify/change which rows are inserted/deleted)
		//	??
		if (! $this_field->do_multi_validations($post_val, $this_field_session['loaded_pkey']))
			json_error("Could not save field: Validation Failed");
	
		$join_table = _AcField_escape_table_name($this_field->join_table);	
		$join_to_left = _AcField_escape_field_name($this_field->bound_pkey );
		$join_to_right = _AcField_escape_field_name($this_field->join_to_right_field );
	
		foreach ($post_val as $i=>$pv)
			$post_val[$i] = _AcField_escape_field_value($post_val[$i]); //since this comes from the client it MUST be escaped.
			

		$insert_fields = $this_field_session['filter_fields'];
		$insert_values = $this_field_session['filter_values'];

		$where_condition = array();
		foreach ($insert_values as $i => $v)
			$where_condition[] = ($insert_fields[$i] ) . " = " . ($insert_values[$i]);
		$where_condition = join(" AND ", $where_condition);
		
		// remove all rows that weren't selected			
		$sql = "DELETE FROM $join_table WHERE ($join_table.$join_to_right NOT IN (" . join(",", $post_val) . ") AND $where_condition)";		
		_AcField_call_query_write($sql);

		$insert_fields[] = $join_to_right;
		foreach ($post_val as $this_post_val)	// ensure all selected rows now have a table row
			{
			//Broaden $where_condition because we want to now individually focus on each item in post_val		
			$insert_values[] = $this_post_val; //already escaped			
			$where_condition = array();
			foreach ($insert_values as $i => $v)
				$where_condition[] = ($insert_fields[$i] ) . " = " . ($insert_values[$i]);
			$where_condition = join(" AND ", $where_condition);
	
			$query = "SELECT COUNT(*) as res FROM $join_table WHERE $where_condition";
			$result = _AcField_call_query_read($query);
			
			if ($result[0]['res'] == 0)
				{				//
				// TWO : pass individual fields so that a validator can set fields for particular rows
				$this_field->do_insert_validations ($tmp = array_combine($insert_fields, $insert_values));
				
				// Code here to handle filters!
				$sql = "INSERT INTO $join_table (" . join(",", $insert_fields) . ") VALUES (" . join(",", $insert_values) . ") ";
				_AcField_call_query_write($sql);
				}
			array_pop($insert_values);
			}
			
		$result['value'] = "success";
		}
	else
		{
		trigger_error("Unknow Action:" . $dataRequest['action'], E_USER_ERROR);
		die ();
		}
			 
	$result['value'] = true;
			
	echo json_encode($result);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>