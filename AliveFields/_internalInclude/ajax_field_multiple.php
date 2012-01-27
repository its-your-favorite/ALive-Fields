<?PHP

/**
 * This file houses the ajax handler / controller for AcJoinSelect write operations.
 *
 *
 *
 * @author Alex Rohde
 */
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
 * @param AcListJoin $fakeThis Context for the controller
 * @param Associative array $request Request from client.
 */
function handle_multiple_field(& $fakeThis, $request) {
    if (($request['action'] === "save")) {
        $result = AcListJoin_controller_save($fakeThis, $request);
    } elseif (($request['action'] === "hardcoded_load") || ($request['action'] === "dynamic_load")) {
        throw_error("Cannot do load in this. Presumably unnecessary.");
    } else {
//Fatal Error
        trigger_error("Unknown action type requested in ajax_field: " . $request['action'], E_USER_ERROR);
    }

    return $result;
}

/**
 * 
 */
//SAVE:
function AcListJoin_controller_save(& $fakeThis, $request) {

    /*
     *  Load the request information into more-readable variables.
     */
    $fieldUniqueId = $request['request_field'];
    $requesterPage = $request['requesting_page'];
    $sourceUniqueId = $request['source_field'];
    $thisFieldSession = & $_SESSION['_AcField'][$requesterPage][$fieldUniqueId];
    $table = $fakeThis->bound_table;
    $values = $request["fieldInfo"];

    if ($fakeThis->mode == "limited")
        throw_error("expectedError"); // No updating a limited field.

    if ($fakeThis->savable == AcField::SAVE_NO) {
        throw_error("Field not savable."); //security violation
    }
    if (count($values) > 1) {
        throw_error("Multiple values not implemented");
    }

    $unescapedValuesArr = json_decode($values[0][1]);
    $valuesArr = array_map($fakeThis->adapter->escape_field_value, $unescapedValuesArr); //($oneValue[1]);                 
// ** I need to analyze this more
// So we only save to a Select in the event that it has differentiateOptions (right?) 
// probably eventually change this to an accessor? So it can be overloaded differently by subclasses?
    verify_control_could_contain_value_set($fakeThis, $requesterPage, $fieldUniqueId, $unescapedValuesArr)
            or throw_error("expectedError");

    if (!isset($thisFieldSession['filter_fields'])) {
        throw_error("This library isn't currently designed to handle a save before a load.");
    }

    if (!is_array($unescapedValuesArr)) {
        throw_error("Invalid parameter.");
    }

    /**
     * Two STEP VALIDATION PROCESS:
     *  ONE pass the list of fields to be updated (so a validator can verify/change which rows are inserted/deleted)
     */
    if (!$fakeThis->do_multi_validations($postVal, $thisFieldSession['loaded_pkey']))
        throw_error("Could not save field: Validation Failed");

    $join_table = $fakeThis->adapter->escape_table_name($fakeThis->join_table);
    $join_to_left = $fakeThis->adapter->escape_field_name($fakeThis->bound_pkey);
    $join_to_right = $fakeThis->adapter->escape_field_name($fakeThis->join_to_right_field);

    /**
     * Does this need to go through session? ??? 
     */
    $insertFieldNames = $thisFieldSession['filter_fields'];
    $commonInsertValues = $thisFieldSession['filter_values'];

    $whereCondition = array(" TRUE "); //this array must have one element

    foreach ($commonInsertValues as $i => $v) {
        $whereCondition[] = ($insertFieldNames[$i] ) . " = " . ($commonInsertValues[$i]);
    }
    $whereCondition = join(" AND ", $whereCondition);

// remove all rows that weren't selected            
    $sql = "DELETE FROM $join_table WHERE ($join_table.$join_to_right NOT IN (" . join(",", $valuesArr) . ") AND $whereCondition)";
    $fakeThis->adapter->query_write($sql);

    /*
     *  Lastly, the primary key
     */
    $insertFieldNames[] = $join_to_right;

    // ensure all client-selected values now have a join-table row
    // Broaden $where_condition because we want to now individually focus on each item in post_val        

    /**
     *  get the list of already present appropriate keys
     */   
    $query = "SELECT $join_to_right as id FROM $join_table WHERE $whereCondition"; 
    $result = $fakeThis->adapter->query_read($query);
    if (!$result)
       $result = array();

    $existingIds = array_map(function($row){ return $row['id']; }, $result);
    
    $neededIds = array_diff($valuesArr, $existingIds);
    
    /**
     * insertValues - Represent the 2-d array of values that are all of the necessary
     *          inserts to the join table.
     */
    $insertValues = array_map(function($add) use ($commonInsertValues, $fakeThis)
                                                { 
                                                $cp = $commonInsertValues; 
                                                $cp[] = $fakeThis->adapter->escape_field_value( $add);
                                                return $cp;
                                                } 
                                 , $neededIds);
                                 
    if (count($insertValues)) {
        // TWO : pass individual fields so that a validator can set fields for particular rows
        foreach ($insertValues as $key => $valuesRow) {            
            $fakeThis->do_insert_validations($tmp = array_combine($insertFieldNames, $valuesRow));
            $insertValues[$key] = "(" . join(",", $valuesRow) . ")";
            }
        $sql = "INSERT INTO $join_table (" . join(",", $insertFieldNames) . ") VALUES " . join(",", $insertValues) . " ";        
        $fakeThis->adapter->query_write($sql);
    }

    $result['value'] = true;
    return $result;
}

?>