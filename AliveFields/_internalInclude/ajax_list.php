<?php

/**
 * This file contains the ajax handler to retrieve list contents for List controls
 *
 *  However, it does not handle saving of elements. This file belongs in the
 *  AcList request_handler, but has been extracted to its own file for size
 *  reasons.
 */

/**
 * This function is the controller for the ajax request to retrieve a list
 *  (used by AcList controls). It has been moved from AcList for readability
 *
 * @param AcList $fakeThis The context for the controller
 * @param Associative Array $request The specific load request
 * @return Associative Array
 */
function acList_Controller(& $fakeThis, $request) {

    /////////////////////////////
    // Shorthand variables
    if (!isset($request['term']))
        $request['term'] = '';

    $requesterPage = & $request['requesting_page'];
    $field_unique_id = $request['request_field'];    
    $thisFieldSession = & $_SESSION['_AcField'][$requesterPage][$field_unique_id];

    $term = strtoupper($fakeThis->adapter->escape_field_value($request['term'], false));
    $this_field = AcField::instance_from_id($field_unique_id);

    $field1 = $fakeThis->adapter->escape_field_name($thisFieldSession['options_pkey']);
    $field2 = $fakeThis->adapter->escape_field_name($thisFieldSession['options_field']);
    $table = $fakeThis->adapter->escape_field_name($thisFieldSession['options_table']);

    $filtering = false;
    $filter_fields = array();
    $filter_vals = array();

    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Check Security
    if (($this_field->options_loadability == AcField::LOAD_WHEN_FILTERED) && strlen($request["requester"])) {
        //Okay, we're loading through a filtered field
        $found = false;
        foreach (AcField::instance_from_id($request['requester'])->filtered_fields as $filter_set) {
            if (($filter_set[0] == $field_unique_id))
                $filtering = true;
        }

        foreach ($request["filters"] as $filt) { // make sure at least one filter is active. deny loading the whole table.
            if (in_array($request["requester"], $filt))
                $found = true;
        }

        if ((!$filtering) || (!$found))
            return array(); //return empty result set. Proceeding would be a security issue. Don't generate an error
    }
    elseif ($this_field->options_loadability == AcField::LOAD_YES)
        ; // okay, we can load without filters
    else {        
        throw_error(AcField::ERROR_LOAD_DISALLOWED);
    }

    // </ Check Security >
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Apply Filtering
    if ($filtering) {        
        if ($this_field instanceof AcListJoin) { //If we have a join table, then the filters apply to that table.
            list($filters, $thisFieldSession['filter_fields'], $thisFieldSession['filter_values'] ) =
                    apply_list_filters($fakeThis, /* byref */ $request, $fakeThis->adapter->escape_table_name($this_field->join_table), $field_unique_id);
        } else {
            $filters = apply_list_filters($fakeThis, /* byref */ $request, $table, $field_unique_id);
            $filters = $filters[0]; //this needs an explaining comment
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Add in the requirement that the search term be present (ignoring case, partial match)
    $conditions = $this_field->filters;

    if ($filtering)
        $conditions = array_merge($conditions, $filters);

    if (($this_field instanceof AcListJoin) === false) //not appropriate for select-joins
        $conditions[] = " UCASE($field2) like '%$term%'";

    $conditions = join($conditions, " AND ");
    $distinct = "";

    // Handle request distincts
    if ($request['distinct']) {
        if ($field1 != $field2)
            throw_error("Fields pkey and value must be the same in a distinct request");
        else
            $distinct = "distinct";
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Produce Result.
    //      All query parameter variables should be escaped at this point.

    if (($this_field instanceOf AcList) && (false === ($this_field instanceOf AcListJoin))) {
        // In the event of no filtering, use a simple query

        $query = "SELECT $distinct $field1 as id, $field2 as label, $field2 as value FROM $table WHERE $conditions ";
    } elseif ($this_field instanceOf AcListJoin) {
        // In the event of no filtering, use a complicated join

        $join_table = $fakeThis->adapter->escape_table_name($this_field->join_table);
        $join_to_right_field = $fakeThis->adapter->escape_field_name($this_field->join_to_right_field);
        $join_from_right_field = $fakeThis->adapter->escape_field_name($this_field->join_from_right_field);


        if ($this_field->mode == 'limited') {//In the event that we wish the table to show only records in the right-table that have a join-table record.
            $query = "SELECT  $join_table.$join_to_right_field as nada, $table.$field2 as label, $table.$field1 as value from $join_table " .
                    "inner" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field " . " WHERE $conditions ";
        } else {
            // Show all the records in the right table, regardless of whether they have a join table
            // record, to let the user select from the full list.
            $query = "SELECT  $join_table.$join_to_right_field as isset, $table.$field2 as label, $table.$field1 as value from $join_table " .
                    "RIGHT" . " JOIN $table ON $join_table.$join_to_right_field = $table.$join_from_right_field AND $conditions ";

            // Right join cannot handle a WHERE the way we want. This solution won't function properly if we have a right
            // join (i.e. show EVERY record in the right table) that is trying to use filtering terms
        }
    }
    else
        throw_error("Unrecognized field type requesting");

    //echo "Setting field session for $field_unique_id to $query";
    $thisFieldSession["last_used_query"] = $query;
    $query .= "  ORDER BY $field2 ";
    if (!isset($request['max_rows']))
        $request['max_rows'] = null;
    
    $result = $fakeThis->adapter->query_read($query, (int) $request['max_rows']);

    if (is_null($result))
        return array();
    else
        return $result;
}

?>