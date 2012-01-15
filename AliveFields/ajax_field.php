<?PHP

function acField_Controller(& $fake_this)
	{			
	$fake_this->generate_controller_header();
	
	error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
	set_error_handler ("auto_error", E_ERROR | E_PARSE | E_ALL ^ E_NOTICE );		
	session_start();
	
	remove_magic_quotes();
	
	global $sql;
	
	/*  Load the request information into more-readable variables.
	*/
	$dataRequest = json_decode($_REQUEST['request'], 1);
	$requestingPage = $dataRequest['requesting_page'];
	$fieldUniqueId = $dataRequest['request_field'];
	$sourceUniqueId = $dataRequest['source_field'];
	
	
	$this_field_session =& $_SESSION['_AcField'][$requestingPage][$fieldUniqueId];
	$this_field = AcField::instance_from_id($fieldUniqueId);
	
	$table = $this_field->bound_table;
	$values = $dataRequest["fieldInfo"];
	
	if (($dataRequest['action'] === "save"))
		{	
		// reconstruct theAcField based on the Id (no need to pass the whole field through session)
		$theAcField = AcField::instance_from_id($fieldUniqueId);
		
		if ( $this_field->savable < 1)
			json_error("Field not savable."); //security violation
		 
		foreach ($values as $x) //so even though we are looping right here, as written controls can only update 1 field per ajax request. This loop is more for theoretical future use then?
			{		
			$values_clause[] = _AcField_escape_field_name($this_field->bound_field)  . " =  " . _AcField_escape_field_value($x[1]) . " ";
			}
		$values_clause = implode(" , ", $values_clause);
		if ($this_field_session['type'] == 'multi')
			verify_control_could_contain_value($requestingPage, $fieldUniqueId, ($x[1]) , "optionValue") or json_error("expectedError");
		}
	/*elseif (($dataRequest['action'] === "insert")) // I haven't yet ported this to the new format for this library and thus it is non-functional
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
		}*/
	elseif (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load")	)
		{
		if (! $this_field->loadable)
			json_error("Field not loadable"); //security violation
					
		$values_clause = $this_field->bound_field . " as answer ";
	
		if ($dataRequest['action'] === "dynamic_load")	
			{
			$where_clause['key_piece'] = _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name( $this_field->bound_pkey ) . " = " . _AcField_escape_field_value($dataRequest['primaryInfo'][1]);
			if ( count($_SESSION['_AcField'][$requestingPage][$sourceUniqueId]['filters']) )
				{
				$source_field = $_SESSION['_AcField'][$requestingPage][$sourceUniqueId];
							
				if (! in_array($this_field_session["unique_id"], $source_field['dependent_fields']) ) //verify that this control is indeed allowed to update the other control. Do this verification by looking at session records.
					json_error("Field not loadable"); //This indicates attempted hacking.
					
				$join_clause[] = $source_field['bound_table'] . " ON " . _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name($this_field->bound_pkey) . " = " . _AcField_escape_table_name($source_field['bound_table']) . "." . _AcField_escape_field_value($source_field["bound_pkey"]);
				if (count($join_clause))
					$join_clause = "INNER JOIN " . join($join_clause, " INNER JOIN ");
				$this_field_session['loaded_join_clause'] = $join_clause;			
				$where_clause['join_piece'] = join($_SESSION['_AcField'][$requestingPage][$sourceUniqueId]['filters'], " AND ");
				}
			$this_field_session['loaded_pkey'] = $dataRequest['primaryInfo'][1] ;
			}
		else
			{
			$where_clause[] = _AcField_escape_table_name($this_field->bound_table) . "." . _AcField_escape_field_name($this_field->bound_pkey) . " = " . _AcField_escape_field_value($this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]]);
			$this_field_session['loaded_pkey'] = $this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]];
			}
		$this_field_session['loaded_where_clause'] = $where_clause;
		}
	else
		{
		//The reason we use trigger-error is to allow this informative message to show up in DEV but
		//	stifle it to a generic one in production.
		trigger_error("Unknown action type requested in ajax_field: " . $dataRequest['action'], E_USER_ERROR );
		}
	
		
		
	/*
	 * This is where load requests are handled.
	 *  To Do: Move it all in in a more object oriented fashion.
	 */	
		
	if (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load"))
		{	
		/* Security check is a necessary layer of security to the application 
		 * 
		 * 
		 */
		
		// down to the nitty-gritty that powers the app
		$sql = "SELECT count(*) as count_rec FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
					
		$security_check = _AcField_call_query_read($sql);
		if (! $security_check[0]['count_rec'])
			json_error("Record not found.");
				
		$sql = "SELECT $values_clause FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
		$result = _AcField_call_query_read($sql);
		$result = array("value" => $result[0]['answer']);
		}
		
	elseif ($dataRequest['action'] === "save")//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		{
		if (! isset($this_field_session['loaded_where_clause']))
			json_error("Not Supported"); //library isn't currently designed to securely handle a save before a load. 
			
		$sql = "SELECT COUNT(*) as count_rec from $table " .  $this_field_session['loaded_join_clause'] . " WHERE " . join($this_field_session['loaded_where_clause'], " AND ");
		
		$security_check = _AcField_call_query_read($sql); // By seeing how many rows our current where clause selects we can add an additional level of security.
		if (! $security_check[0]['count_rec']) // This is the critical line that ensures that the filters that limit which values are loaded (as stored in the loaded_where_clause) also 
			json_error("Security issue");			// apply to the value which will be saved here. That is, if this field can only load values WHERE X < 3 then we can apply the same WHERE X < 3 check on our save.
												// technically this is redundant with the inclusion of "loaded_where_clause" in the actual UPDATE statement. 
		else if ($security_check[0]['count_rec'] > 1) // You probably don't want to do something that affects multiple rows since you are usually operating on primary key. 
			json_error("Cancelled. Affects multiple rows."); //However if you know what you are doing, you can disable this restriction by commenting these two lines.
			
		foreach ($values as $x)
			if (! $theAcField->do_validations($x[1], $this_field_session['loaded_pkey']))
				json_error("Could not save field: Validation Failed");
	
		$sql = "UPDATE $table SET  $values_clause  WHERE  " . join($this_field_session['loaded_where_clause'], " AND ");
		_AcField_call_query_write($sql, 1);	
		
		$result['value'] = "success";
		}
	else
		{
		trigger_error("Unknow Action:" . $dataRequest['action'], E_USER_ERROR);	
		}
	
	$debug = false;
	if ($debug)
		var_dump($dataRequest);
	if ($debug)
		 echo $sql;
		 
	
	
	if ($dataRequest['action'] === 'load')
		{
		$result['value'] = $row[0];
		//Our result should be the first field in the first row.
		}
			
	echo json_encode($result);
	}

//
?>