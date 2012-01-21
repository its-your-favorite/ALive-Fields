<?PHP
/**
 * ajax_list_multiple.php
 * Copyright Alex Rohde 2011. Part of ALive Fields project.  https://github.com/anfurny/ALive-Fields
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 *
 * Purpose:  This file houses the ajax handler / controller for AcJoinSelect write operations.
 */

// Note to self: The structure of this file is unreadable. Fix that. 
// 
// Security concerns and how/where they are protected against in this file
// 
// A) Passing an array of made up integers, or even non-integers from client side.
//    *) Handled in verify_control_could_contain_value_set. 
//  Status: Untested
//
// B) SQL Injection
//  *) Escaping
//  Status: Untested
//
// C) Trying to get the control to delete from join table inappropriately
//  *) Filters should take care of this
//    Status: Not explored in depth

/**
 * The normal saving and loading operations have been moved out to this file
 * to enhance readability of AcListJoin
 * 
 * @param AcListJoin $fake_this Context for the controller
 * @param Associative array $request Request from client.
 */
function handle_multiple_field(& $fake_this, $request)
 {        
    
    $fieldUniqueId = $request['request_field']; 
    $this_field = AcField::instance_from_id($fieldUniqueId);
    /*
     *  Load the request information into more-readable variables.
    */
    $requester_page = $request['requesting_page'];
    $sourceUniqueId = $request['source_field'];        
    $this_field_session =& $_SESSION['_AcField'][$requester_page][$fieldUniqueId];    
    $table = $this_field->bound_table;
    $values = $request["fieldInfo"];
    
    //Post Val represents the data from the multi-select as an array of keys e.g. [1,3,5]
    $post_val = NULL; 

    if ($this_field->mode == "limited")
        throw_error("expectedError"); // No updating a limited field.
        

    //////////////////////////////////////////////////////////////////////////////////    
                    
    if (($request['action'] === "save"))
        {        
        if ($this_field->savable < 1) {
            die(throw_error("Field not savable.")); //security violation
        }
        if (count($values) > 1) {
            die(throw_error("Multiple values not implemented" ));
        }
        foreach ($values as $x) //so even though we are looping right here, as written controls can only update 1 field per ajax request. This loop is more for theoretical future use then?
            {        
            $fields_arr[] = $fake_this->adapter->escape_field_name($this_field->bound_field);
            $values_arr[] = array( $fake_this->adapter->escape_field_value($x[1]) ) ;
            }
        $post_val = json_decode($x[1], true);
        // ** I need to analyze this more
        // So we only save to a Select in the event that it has differentiateOptions (right?) 
        // probably eventually change this to an accessor? So it can be overloaded differently by subclasses?
        verify_control_could_contain_value_set($fake_this, $requester_page, $fieldUniqueId, $post_val)
                    or throw_error("expectedError");
        }
    elseif (($request['action'] === "hardcoded_load") || ($request['action'] === "dynamic_load")    )
        {
        die(throw_error("Cannot do load in this. Presumably unnecessary."));
        // Actually, I think we can do this. But I should do it gracefully
            /*
        if (! $this_field->loadable)
            die("Field not loadable"); //security violation
            
        $values_clause = $this_field->bound_field . " as answer ";
    
        if ($dataRequest['action'] === "dynamic_load")    
            {
            $where_clause['key_piece'] = $fake_this->adapter->escape_table_name($this_field->bound_table) . "." . $fake_this->adapter->escape_field_name( $this_field->bound_pkey ) . " = " . $fake_this->adapter->escape_field_value($dataRequest['primaryInfo'][1]);
            if ( count($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters']) )
                {
                $source_field = $_SESSION['_AcField'][$requester_page][$sourceUniqueId];
                            
                if (! in_array($this_field_session["unique_id"], $source_field['dependent_fields']) ) //verify that this control is indeed allowed to update the other control. Do this verification by looking at session records.
                    die("NOT A MATCH");
                    
                $join_clause[] = $source_field['bound_table'] . " ON " . $fake_this->adapter->escape_table_name($this_field->bound_table) . "." . $fake_this->adapter->escape_field_name($this_field->bound_pkey) . " = " . $fake_this->adapter->escape_table_name($source_field['bound_table']) . "." . $fake_this->adapter->escape_field_value($source_field["bound_pkey"]);
                if (count($join_clause))
                    $join_clause = "INNER JOIN " . join($join_clause, " INNER JOIN ");
                $this_field_session['loaded_join_clause'] = $join_clause;            
                $where_clause['join_piece'] = join($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters'], " AND ");
                }
            $this_field_session['loaded_pkey'] = $dataRequest['primaryInfo'][1] ;
            }
        else
            {
            $where_clause[] = $fake_this->adapter->escape_table_name($this_field->bound_table) . "." . $fake_this->adapter->escape_field_name($this_field->bound_pkey) . " = " . $fake_this->adapter->escape_field_value($this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]]);
            $this_field_session['loaded_pkey'] = $this_field_session['hardcoded_loads'][$dataRequest['primaryInfo'][1]];
            }
        $this_field_session['loaded_where_clause'] = $where_clause;
        */}
    else
        {
        trigger_error("Unknown action type requested in ajax_field: " . $request['action'], E_USER_ERROR );
        }
    
    /*if (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load"))
        {
        $sql = "SELECT count(*) as count_rec FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
        //echo $sql;
        $security_check = $fake_this->adapter->call_query_read($sql);
        if (! $security_check[0]['count_rec'])
            json_error("Record not found.");
                
        $sql = "SELECT $values_clause FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
        $result = $fake_this->adapter->call_query_read($sql);
        $result = array("value" => $result[0]['answer']);
        }
    else*/if ($request['action'] === "save")//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        {            
        if (! isset($this_field_session['filter_fields']))
            die(throw_error("This library isn't currently designed to handle a save before a load.")); //library isn't currently designed to securely handle a save before a load. 
                    
        if (! is_array($post_val))
            throw_error("Invalid parameter #17");
        //Two STEP VALIDATION PROCESS. ONE pass the list of fields to be updated (so a validator can verify/change which rows are inserted/deleted)
        //    ??
        if (! $this_field->do_multi_validations($post_val, $this_field_session['loaded_pkey']))
            throw_error("Could not save field: Validation Failed");
    
        $join_table    = $fake_this->adapter->escape_table_name($this_field->join_table);    
        $join_to_left  = $fake_this->adapter->escape_field_name($this_field->bound_pkey );
        $join_to_right = $fake_this->adapter->escape_field_name($this_field->join_to_right_field );
    
        /**
         * Does this need to go through session? ??? 
         */
        $insert_fields = $this_field_session['filter_fields'];
        $insert_values = $this_field_session['filter_values'];
        
        foreach ($post_val as $i=>$pv) {
            //since this comes from the client it MUST be escaped.           
            $post_val[$i] = $fake_this->adapter->escape_field_value($post_val[$i]); 
        }
        
        $where_condition = array();
        
        foreach ($insert_values as $i => $v)  {
            $where_condition[] = ($insert_fields[$i] ) . " = " . ($insert_values[$i]);
        }
        $where_condition = join(" AND ", $where_condition);
        
        // remove all rows that weren't selected            
        $sql = "DELETE FROM $join_table WHERE ($join_table.$join_to_right NOT IN (" . join(",", $post_val) . ") AND $where_condition)";        
        $fake_this->adapter->query_write($sql);

        $insert_fields[] = $join_to_right;
        foreach ($post_val as $this_post_val)    // ensure all selected rows now have a table row
            {
            //Broaden $where_condition because we want to now individually focus on each item in post_val        
            $insert_values[] = $this_post_val; //already escaped            
            $where_condition = array();
            foreach ($insert_values as $i => $v)
                $where_condition[] = ($insert_fields[$i] ) . " = " . ($insert_values[$i]);
            $where_condition = join(" AND ", $where_condition);
    
            $query = "SELECT COUNT(*) as res FROM $join_table WHERE $where_condition";
            $result = $fake_this->adapter->query_read($query);
            
            /* This following line is unreadable... fix it*/
            if ($result[0]['res'] == 0)
                {
                // TWO : pass individual fields so that a validator can set fields for particular rows
                $this_field->do_insert_validations ($tmp = array_combine($insert_fields, $insert_values));
                
                // Code here to handle filters!
                $sql = "INSERT INTO $join_table (" . join(",", $insert_fields) . ") VALUES (" . join(",", $insert_values) . ") ";
                $fake_this->adapter->query_write($sql);
                }
            array_pop($insert_values);
            }
            
        $result['value'] = "success";
        }
    else
        {
        trigger_error("Unknow Action:" . $request['action'], E_USER_ERROR);
        die ();
        }
             
    $result['value'] = true;            
    return $result;
}


?>