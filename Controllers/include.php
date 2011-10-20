<?PHP
function verify_control_could_contain_value($page, $control_id, $value, $field)// $request['requester'], $request["requester_key"], "field") or die("security issue"); 			
{
  $test_field = $_SESSION['_AcField'][$page][$control_id];
  ///echo $control_id;
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
?>