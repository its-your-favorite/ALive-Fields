<?PHP
// TO DO:
// make it work: 
// handle filters


/////////////////////////////////////////////////////////////////
// ERROR HANDLER. By default, it passes all errors to client, that by default, displays them in a message box. You may wish to instead log them in production mode.
function json_error($x)
{
	die (json_encode(array("criticalError" => $x)));	
} 

function auto_error($err,$b="",$c="",$d="",$e="")
{
	json_error( " " . implode(",", func_get_args()));
}


function handle_multiple_field($request)
 {
	////////////////////////////////////////////////////////////////
	// Removes Magic Quotes (In the event your webserver has them enabled and doesn't give you the option to change it).
	if (get_magic_quotes_gpc()) 
		{
		function stripslashes_gpc(&$value)
			{
			$value = stripslashes($value);
			}
		array_walk_recursive($_REQUEST, 'stripslashes_gpc');
		}
		
	header('Expires: Fri, 09 Jan 1981 05:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', FALSE);
	header('Content-Type: text/html; charset=iso-8859-1');
	header('Pragma: no-cache');
	
	require_once "query_wrapper.php";
	require_once "include.php";
	
	
	error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
	set_error_handler ("auto_error", E_ERROR | E_PARSE | E_ALL ^ E_NOTICE );
	
	global $sql;
	
	$dataRequest = $request;
	$requester_page = $dataRequest['requesting_page'];
	$fieldUniqueId = $dataRequest['request_field'];
	$sourceUniqueId = $dataRequest['source_field'];
	
	//var_dump($dataRequest); echo "<BR><BR>";
	$limitations = $_SESSION['_AcField'][$requester_page][$dataRequest['primaryInfo'][1]];
	
	$this_field_session =& $_SESSION['_AcField'][$requester_page][$fieldUniqueId];
	$this_field = AcField::instance_from_id($fieldUniqueId);
	
	$table = $this_field->bound_table;
	$values = $dataRequest["fieldInfo"];
	
	/*if (($dataRequest['action'] === "addOrUpdate"))
		{
		 $where_clause_copy =  $where_clause;
		 $where_clause_copy [] = '1=1';	
		 $where_clause_copy  =  implode(" AND ", $where_clause_copy );
		 
		if (database_fetch_field("SELECT COUNT(*) FROM $table WHERE $where_clause_copy "))
			$dataRequest['action'] = "save"; // there is a record to update so update it	
		else
			{
			$dataRequest['action'] = "insert"; // there is not, so insert
			$values = array_merge($values, $limitations); //instead of limiting to primary keys, we need to set primary keys
			$limitations = array();
			unset($where_clause); //important
			}
		}*/
	//////////////////////////////////////////////////////////////////////////////////	
					
	if (($dataRequest['action'] === "save"))
		{	
		$theAcField = AcField::instance_from_id($fieldUniqueId);
		
		if ($this_field->savable < 1)
			die("Field not savable."); //security violation
		 
		foreach ($values as $x) //so even though we are looping right here, as written controls can only update 1 field per ajax request. This loop is more for theoretical future use then?
			{		
			$fields_arr[] = _AcField_escape_field_name($this_field->bound_field);
			$values_arr[] = array( _AcField_escape_field_value($x[1]) ) ;
			}
	
		// ** I need to analyze this more
		// So we only save to a Select in the event that it has differentiateOptions (right?) 
		if ($theAcField->type == 'multi') //probably eventually change this to an accessor? So it can be overloaded differently by subclasses?
			verify_control_could_contain_value($requester_page, $fieldUniqueId, ($x[1]) , "optionValue") or json_error("expectedError");
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
			
		$post_val = json_decode($x[1], true); // $x[1] is just a little too ambiguous.  Post Val represents the data from tho multi-select as an array of keys e.g. [1,3,5]
		if (! is_array($post_val))
			json_error("Invalid parameter #17");
		//Two STEP VALIDATION PROCESS. ONE pass the list of fields to be updated (so a validator can verify/change which rows are inserted/deleted)
		//	??
		if (! $theAcField->do_multi_validations($post_val, $this_field_session['loaded_pkey']))
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
		//die($sql);
		//echo "<BR>" . $sql;
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
			//
			
			//echo "<HR> Trying postval $this_post_val";
			$query = "SELECT COUNT(*) as res FROM $join_table WHERE $where_condition";
			$result = _AcField_call_query_read($query);
			//echo "<BR>$query<br>";
			//var_dump($result);
			if ($result[0]['res'] == 0)
				{
				//echo "not found";
				// TWO : pass individual fields so that a validator can set fields for particular rows
				$theAcField->do_insert_validations (array_combine($insert_fields, $insert_values));
				
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Manual Error  -- Handles errors thrown by programmer.

function manual_error($err, $sql) //specific to this file
{
	$callStack = print_r(debug_backtrace(), 1);	
	$message = $err . " on " . $sql . " and " . $callStack;
	
	json_error($message);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>