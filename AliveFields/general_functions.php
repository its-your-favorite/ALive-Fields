<?PHP
/*
* ERROR HANDLER. By default, it passes all errors to client, that by default, displays them in a message box. You may wish to instead log them in production mode.
*
*/
function json_error($x)
{
	die (json_encode(array("criticalError" => $x)));	
} 

/*
 * Default Error Hanlder
 * // Removes Magic Quotes (In the event your webserver has them enabled and doesn't give you the option to change it).
 * ... like mine... where the example is hosted... 8(
 */
function auto_error($err,$b="",$c="",$d="",$e="")
{
	json_error( " " . implode(",", func_get_args()));
}

/*
 * Manual Error  -- Handles debug errors thrown by programmer.
*/
function manual_error($err, $sql) //specific to this file
{
	$callStack = print_r(debug_backtrace(), 1);
	$message = $err . " on " . $sql . " and " . $callStack;

	json_error($message);
}

/**
 * 
 */
function remove_magic_quotes()
{
	if (get_magic_quotes_gpc())
	{
		function stripslashes_gpc(&$value)
		{
			$value = stripslashes($value);
		}
		array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Verifies control given by fieldUniqueId actually could contain the value passed by post: $value
//   Though this check could instead be done by saving the loaded value for basic controls, for list controls this is a necessary security precaution.
//
function verify_control_could_contain_value($page, $fieldUniqueId, $value, $field)
{
  $test_field = $_SESSION['_AcField'][$page][$fieldUniqueId];
  $this_field = AcField::instance_from_id($fieldUniqueId);

  if (! isset($test_field["last_used_query"]) )
  		return false;

//die($field);		
  if ($field == "pkey")
  	$fieldname = $this_field->bound_pkey;
  elseif ($field == "text")
  	$fieldname = $this_field->bound_field;
  elseif ($field == "optionValue")
   	$fieldname = $this_field->options_pkey;
  else
  	die("unknown verify type: $field");
  //WHat about stuff? which queries are active which filters I mean? All will be used in its last query... Cool.
  $query = $test_field["last_used_query"] . " AND " . _AcField_escape_field_name($fieldname) . " = " . _AcField_escape_field_value($value);
  
  $result = (_AcField_call_query_read($query, 1));

  return $result;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Verifies control given by fieldUniqueId actually could contain the value array passed by post: $value
//   Used for AcJoinSelectboxes
//
//
function verify_control_could_contain_value_set($page, $fieldUniqueId, $value_array /*, $field*/)
{
  $test_field = $_SESSION['_AcField'][$page][$fieldUniqueId];
  $this_field = AcField::instance_from_id($fieldUniqueId);

  if (! isset($test_field["last_used_query"]) )
  		return false;

  $query = $test_field["last_used_query"]; 
  $result = (_AcField_call_query_read($query, 1));
  
  foreach ($result as $this_row)
  	$valid_values[] = $this_row['value'];

  //var_dump($value_array);
  foreach ($value_array as $value)
  	{
	//echo "<BR> Testing $value against " . join(",", $valid_values);
	if (! array_search($value, $valid_values) === false)
		return false;		
	}
  return true;

  return $result;
}

///////////////////////////////////////////////////////////////////////////////////////////////////

function remove_magic_quotes_if_present()
	{
	if (get_magic_quotes_gpc()) 
		{
		function stripslashes_gpc(&$value)
			{
			$value = stripslashes($value);
			}
		array_walk_recursive($_REQUEST, 'stripslashes_gpc');
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

function apply_list_filters(& $request, $table, $field_unique_id)
	{
	$filters = array();
	$fieldnames = array();
	$fieldvals = array();
	
	foreach ($request["filters"] as $filt) // Only apply filters that are by asked for by the client-side request. 
			foreach ($filt as $source_fieldUniqueId) //for each client-picked filterING control...
				{				
				$source_field = AcField::instance_from_id($source_fieldUniqueId) or json_error("Library Error #3"); 
				//In event of a nonexistent source_fieldUniqueId passed, terminate.
				//Remember $source_field->filtered_fields  represents all of the OTHER fields that this given AcList FILTERS.

				foreach ($source_field->filtered_fields as $filtering_type)  
					if ($filtering_type[0] == $field_unique_id) // For each alleged filter from the request, ensure that said filter actually is allowed to filter THIS control.
						{
						if ($filtering_type[2] == "value")
							{ //Filtering by Pkey
							verify_control_could_contain_value($request['requesting_page'], $request['requester'], $request["requester_key"], "pkey") or die("security issue 1"); 
							$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_key"]);
							$fieldnames[] =  _AcField_escape_field_name($filtering_type[1]);
							$fieldvals[] =  _AcField_escape_field_value($request["requester_key"]);							
							}
						else
							{ //Filtering by Text
							verify_control_could_contain_value($request['requesting_page'], $request['requester'], $request["requester_text"], "text") or die("security issue 2"); 							
							$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_text"]);					
							$fieldnames[] =  _AcField_escape_field_name($filtering_type[1]);
							$fieldvals[] =  _AcField_escape_field_value($request["requester_text"]);
							}
						}
				}		
	return array($filters, $fieldnames, $fieldvals);
	}
?>