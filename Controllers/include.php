<?PHP
function verify_control_could_contain_value($page, $control_id, $value, $field)// $request['requester'], $request["requester_key"], "field") or die("security issue"); 			
{
  $test_field = $_SESSION['_AcField'][$page][$control_id];
  ///echo $control_id;
  //var_dump(func_get_args());
  //var_dump($test_field);

  if (! isset($test_field["last_used_query"]) )
  		return false;

//die($field);		
  if ($field == "pkey")
  	$fieldname = $test_field["bound_pkey"];
  elseif ($field == "text")
  	$fieldname = $test_field["bound_field"];
  elseif ($field == "optionValue")
   	$fieldname = $test_field['options_pkey'];
  else
  	die("unknown verify type: $field");
  //WHat about stuff? which queries are active which filters I mean? All will be used in its last query... Cool.
  $query = $test_field["last_used_query"] . " AND " . _AcField_escape_field_name($fieldname) . " = " . _AcField_escape_field_value($value);
  
//  echo $query;
  $result = (_AcField_call_query_read($query, 1));
	
//echo "RESULT Is: .";
//var_dump($result);

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
	foreach ($request["filters"] as $filt) // Only apply filters that are by asked for by the client-side request. 
			foreach ($filt as $source_control_id) //for each client-picked filterING control...
				{
			/*	$source_field = $_SESSION['_AcField'][$requester_page][$source_control_id];
				$source_field_filters = $source_field["filtered_fields"];
				var_dump($source_field_filters);*/
				
				$source_field = AcField::instance_from_id($source_control_id) or json_error("Library Error #3"); //In event of a nonexistent source_control_id passed, terminate.
				$source_field_filters = $source_field->filtered_fields;

				foreach ($source_field_filters as $filtering_type)  
					if ($filtering_type[0] == $field_unique_id) // For each alleged filter from the request, ensure that said filter actually is allowed to filter THIS control.
						{
						if ($filtering_type[2] == "value")
							{ //Filtering by Pkey
							verify_control_could_contain_value($request['requesting_page'], $request['requester'], $request["requester_key"], "pkey") or die("security issue 1"); 
							$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_key"]);
							}
						else
							{ //Filtering by Text
							verify_control_could_contain_value($request['requesting_page'], $request['requester'], $request["requester_text"], "text") or die("security issue 2"); 							
							$filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_text"]);					
							}
						}
				}		
	return $filters;
	}
?>