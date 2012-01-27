<?PHP
/**
 * General Functions includes a bunch of commonly used functions.
 *  
 * @author Alex Rohde
 */


/**
 * This function ensures that we we only have queries apply to the correct fields.
 * 
 * @param $_REQUEST $request
 * @param string $table
 * @param string $field_unique_id
 * @return array of "Filters" (restrictions) to be applied to the query 
 */
function apply_list_filters($fakeThis, & $request, $table, $field_unique_id)
    {
    $filters = array();
    $fieldnames = array();
    $fieldvals = array();
    
    foreach ($request["filters"] as $filt) {// Only apply filters that are by asked for by the client-side request. 
        foreach ($filt as $source_fieldUniqueId) //for each client-picked filterING control...
           {     
                $source_field = AcField::instance_from_id($source_fieldUniqueId) or throw_error("Library Error #3"); 
                //In event of a nonexistent source_fieldUniqueId passed, terminate.
                //Remember $source_field->filtered_fields  represents all of the OTHER fields that this given AcList FILTERS.

                foreach ($source_field->filtered_fields as $filtering_type)  
                    if ($filtering_type[0] == $field_unique_id) // For each alleged filter from the request, ensure that said filter actually is allowed to filter THIS control.
                        {
                        
                        if ($filtering_type[2] == "value")
                            { //Filtering by Pkey
                              
                            verify_control_could_contain_value($source_field, $request['requesting_page'], $request["requester_key"], "pkey") or die("security issue 1"); 
                       
                            $filters[] = ($table) . "." . $fakeThis->adapter->escape_field_name($filtering_type[1]) . " = " 
                                                  . $fakeThis->adapter->escape_field_value($request["requester_key"]);
                            $fieldnames[] =  $fakeThis->adapter->escape_field_name($filtering_type[1]);
                            $fieldvals[]  =  $fakeThis->adapter->escape_field_value($request["requester_key"]);                            
                            }
                        else
                            { //Filtering by Text
                            verify_control_could_contain_value($source_field, $request['requesting_page'], $request["requester_text"], "text") or die("security issue 2");                             
                            $filters[] = ($table) . "." . _AcField_escape_field_name($filtering_type[1])  . " = " . _AcField_escape_field_value($request["requester_text"]);                    
                            $fieldnames[] =  _AcField_escape_field_name($filtering_type[1]);
                            $fieldvals[] =  _AcField_escape_field_value($request["requester_text"]);
                            }
                        }
            }     
    }
    return array($filters, $fieldnames, $fieldvals);
    }
    
/*
 * Default Error Hanlder
 * 
 */
function auto_error($err,$b="",$c="",$d="",$e="")
{
    throw_error( " " . implode(",", func_get_args()));
}

/*
* ERROR HANDLER. By default, it passes all errors to client, that by default, 
 * displays them in a message box. You may wish to instead log them in production mode.
*
*/
function throw_error($x)
{
    throw new ErrorException ($x);    
} 
/*
 * Manual Error  -- Handles debug errors thrown by programmer.
*/
function manual_error($err, $sql) //specific to this file
{
    $callStack = print_r(debug_backtrace(), 1);
    $message = $err . " on " . $sql . " and " . $callStack;

    throw_error($message );
}

/**
 * Removes Magic Quotes (In the event your webserver has them enabled and 
 * doesn't give you the option to change it).
 * like mine... where the example is hosted... 8(
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

/**
 *  Verifies control given by fieldUniqueId actually could contain the pkey passed by post: $value
 *  This is necessary because the key to load is passed via the client side so it needs to be server-validated.
 * 
 * 
 * Though this check could instead be done by saving the loaded value for basic controls,
 *  for list controls this is a necessary security precaution.
 *
 *
 * @param AcField $fakeThis - The given control that we are ensuring contains $value
 * @param AcField $page - A unique token to this page request
 * @param type $value - The value we want to be sure that $fakeThis contains
 * @param type $field - Where $fakeThis should contain the value 
 * @return boolean 
 */
function verify_control_could_contain_value($fakeThis, $page, $value, $field)
{
 /* echo "<BR><BR>";
  var_dump($fakeThis);
  echo "<BR><BR>";
  echo $fieldUniqueId;*/
  
  $this_sess = $_SESSION['_AcField'][$page][$fakeThis->get_unique_id()];

  if (! isset($this_sess["last_used_query"]) ) {
      return false;
  }
  if ($field == "pkey")
      $fieldname = $fakeThis->bound_pkey;
  elseif ($field == "text")
      $fieldname = $fakeThis->bound_field;
  elseif ($field == "optionValue")
       $fieldname = $fakeThis->options_pkey;
  else
      die("unknown verify type: $field");
  //WHat about stuff? which queries are active which filters I mean? All will be used in its last query... Cool.
  $query = $this_sess["last_used_query"] . " AND " . $fakeThis->adapter->escape_field_name($fieldname) . " = " . $fakeThis->adapter->escape_field_value($value);    
  
  $result = ($fakeThis->adapter->query_read($query, 1));
  
  if (!$result)
  {      
     throw_error("No result found");
  }
  return $result;
}

/**
 *  Verifies control given by fieldUniqueId actually could contain the value array passed by post: $value
 * 
 * 
 *  Used for AcJoinSelectboxes
 */
function verify_control_could_contain_value_set($fakeThis, $page, $fieldUniqueId, $value_array /*, $field*/)
{
  $test_field = $_SESSION['_AcField'][$page][$fieldUniqueId];
  $this_field = AcField::instance_from_id($fieldUniqueId);

  if (! isset($test_field["last_used_query"]) )
          return false;

  $query = $test_field["last_used_query"]; 
  $result = ($fakeThis->adapter->query_read($query, 1));
  
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

?>