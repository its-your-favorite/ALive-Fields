<?php
/**
 * This file contains the ajax handler to retrieve list contents for List controls
 * 
 *  However, it does not handle saving of elements. This file belongs in the 
 *  AcList request_handler, but has been extracted to its own file for size
 *  reasons.
 * 
 *
 *  @author Alex Rohde
 */
 
function acList_Controller(& $fake_this)
    {    
    
    $request = json_decode($_REQUEST['request'], 1);
    
    error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
    set_error_handler ("auto_error", E_ERROR | E_PARSE | E_USER_ERROR );
    
    session_start();
    
    /////////////////////////////
    // Shorthand variables 
    $requester_page =& $request['requesting_page'];
    $field_unique_id = $request['request_field'];
    $term = strtoupper(_AcField_escape_field_value($request['term'], false));
    $this_field_session =& $_SESSION['_AcField'][$requester_page][$field_unique_id];
    $this_field = AcField::instance_from_id($field_unique_id);
    $this_field->generate_controller_header();    
    
    $field1 = _AcField_escape_field_name($this_field_session['options_pkey']);
    $field2 = _AcField_escape_field_name($this_field_session['options_field']);
    $table = _AcField_escape_field_name($this_field_session['options_table']);
    
    $filtering = false;
    
    $filter_fields = array();
    $filter_vals = array();
    //
    /////////////////////////////
    
    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Check Security    
    if (($this_field->options_loadability == 1) && strlen($request["requester"]))
        {        
        $found = false;
        foreach ( AcField::instance_from_id($request['requester'])->filtered_fields as $filter_set)
            {
            if (($filter_set[0] == $field_unique_id) )
                $filtering = true;
            }
    
        foreach ($request["filters"] as $filt) // make sure at least one filter is active. deny loading the whole table.
            {
            if (in_array($request["requester"], $filt ))
                $found = true;
            }
            
        if ((! $filtering) || (!$found))
            die("[]");//return empty result set. Proceeding would be a security issue. Don't generate an error
        }//Okay, we're loading through a filtered field
    elseif ($this_field->options_loadability == 2)
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
            {
            list($filters, $this_field_session['filter_fields'], $this_field_session['filter_values'] ) =
                     apply_list_filters( /*byref*/ $request, _AcField_escape_table_name($this_field->join_table), $field_unique_id);
            }    
        else        
            
            {
            $filters = apply_list_filters(/* byref */ $request, $table, $field_unique_id);        
            $filters = $filters[0];
            }    
        }
    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Add in the requirement that the search term be present (ignoring case, partial match)
    $conditions = $this_field->filters;
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
            return json_error("Fields pkey and value must be the same in a distinct request");
        else
            $distinct = "distinct";
        }
    // 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////    
    // Produce Result. 
    //      All query parameter variables should be escaped at this point.
    
    if ($this_field->type_temp == 0) 
        {
        // In the event of no filtering, use a simple query
        $query = "SELECT $distinct $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
        }
    elseif ($this_field->type_temp == 1)
        {
        // In the event of no filtering, use a complicated join
        
        $join_table = _AcField_escape_table_name($this_field->join_table);
        $join_to_right_field = _AcField_escape_field_name($this_field->join_to_right_field);
        $join_from_right_field = _AcField_escape_field_name($this_field->join_from_right_field);    
         
        
        if ( $this_field->mode == 'limited' ) 
            {//In the event that we wish the table to show only records in the right-table that have a join-table record.
            
             $query = "SELECT  $join_table.$join_to_right_field as nada, $table.$field2 as label, $table.$field1 as value from $join_table " . 
             "inner" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field " ." WHERE $conditions ";         
            }     
        else 
            {
             // Show all the records in the right table, regardless of whether they have a join table 
             // record, to let the user select from the full list.
             $query = "SELECT  $join_table.$join_to_right_field as isset, $table.$field2 as label, $table.$field1 as value from $join_table " . 
             "RIGHT" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field AND $conditions " ;
    
             // Right join cannot handle a WHERE the way we want. This solution won't function properly if we have a right
             // join (i.e. show EVERY record in the right table) that is trying to use filtering terms
            }
        
        }
    else
        return json_error("Unrecognized field type requesting");
          
    if (isset($DEBUG) && $DEBUG)
        echo $query;
        
    $this_field_session["last_used_query"] = $query;
    $query .= "  ORDER BY $field2 ";
    $result = _AcField_call_query_read($query, (int)$request['max_rows']);
    
    if (is_null($result ) )
        echo "[]";
    else
        echo json_encode($result);    
    }
?>