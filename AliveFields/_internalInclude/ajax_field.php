<?PHP

/**
 * The normal save and load operations have been moved out to this file
 * to enhance readability of AcField
 *
 * @param AcField $fakeThis The context of the handling AcField
 * @param string $request Whether to load or save
 * @return associative_array Response information
 */
function acField_Controller(& $fakeThis, $request) {

    /*  Load the request information into more-readable variables.
     */
    $requestingPage = $request['requesting_page'];
    $fieldUniqueId = $request['request_field'];
    $this_field = AcField::instance_from_id($fieldUniqueId);
    $table = $this_field->bound_table;
    $thisFieldSession = & $_SESSION['_AcField'][$requestingPage][$fieldUniqueId];

    if (empty($thisFieldSession)) {
        throw_error(AcField::ERROR_INVALID_TOKEN);
    }

    $this_field = AcField::instance_from_id($fieldUniqueId);
    $table = $this_field->bound_table;
    $join_clause = '';


    if ($request['action'] === 'save') {
        return save_action($fakeThis, $request);
    } elseif (($request['action'] === 'hardcoded_load') || ($request['action'] === 'dynamic_load')) {
        $tmp = load_action($fakeThis, $request);
        return $tmp;
        /* elseif (($dataRequest['action'] === "insert"))
          {
          return insert_action();
          } */
    } else {
        trigger_error("Unknow Action:" . $request['action'], E_USER_ERROR);
    }
}

function save_action(& $fakeThis, $request) {

    $requestingPage = $request['requesting_page'];
    $fieldUniqueId = $request['request_field'];
    $thisFieldSession = & $_SESSION['_AcField'][$requestingPage][$fieldUniqueId];

// reconstruct theAcField based on the Id (no need to pass the whole field through session)
    $this_field = AcField::instance_from_id($fieldUniqueId);
    $table = $this_field->bound_table;


    ////////////////////////////////////////////////////////////////////////////////
    // Validity Checks

    if ($this_field->savable != AcField::SAVE_YES)
        throw_error(AcField::ERROR_SAVE_DISALLOWED);

    //library isn't currently designed to securely handle a save before a load.
    if (!isset($thisFieldSession['loaded_where_clause'])) {
        throw_error('Not Supported');
    }

    // Validity Checks
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    // Set the values clause

    /**
     * Values is a multidimensional array of the form:
     * array
     *  array [ UNUSED , 'new value' ] 
     * 
     * The only case the outer array will have more than 1 element is if insert 
     *  is ever implemented in this library.
     */
    $all_values = $request['fieldInfo'];
    if (count($all_values) != 1)
        throw_error("Invalid Save Request");
    $value = $all_values[0][1];

    $values_clause = $fakeThis->adapter->escape_field_name($this_field->bound_field)
            . " = " . $fakeThis->adapter->escape_field_value($value) . " ";

    /**
     *  Ensure that every value we're saving is plausible. This only applies to
     *  lists where differentiate options has been used and each field represents
     *   on of a set bunch of options from a join table.
     */
    if ($fakeThis instanceof AcList) {

        $res = verify_control_could_contain_value($fakeThis, $requestingPage, $fieldUniqueId, ($value), 'optionValue');
        if (! $res) 
            {
            throw_error('expectedError');
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////

    /**
     * For security we apply all the limiting filters that restrict what
     * values can be *loaded* into a control to the process of SAVING a control.
     *
     * This is thus stored in the session.
     */
    $sql = "SELECT COUNT(*) as count_rec from $table " . $thisFieldSession['loaded_join_clause']
            . " WHERE " . join($thisFieldSession['loaded_where_clause'], " AND ");

    $security_check = $fakeThis->adapter->query_read($sql);
    if (!$security_check[0]['count_rec']) {
        throw_error('Security issue');
    } else if ($security_check[0]['count_rec'] > 1) {
// You probably don't want to do something that affects multiple rows since you are usually operating on primary key.
//However if you know what you are doing, you can disable this restriction by commenting the following line
        throw_error('Cancelled. Affects multiple rows.');
    }

    /**
     * Apply save validations
     */
    foreach ($all_values as $x)
        if (!$this_field->do_validations($x[1], $thisFieldSession['loaded_pkey']))
            throw_error('Could not save field: Validation Failed');

    /**
     * Perform the actual save
     */
    $sql = "UPDATE $table SET  $values_clause  WHERE  " . join($thisFieldSession['loaded_where_clause'], " AND ");
    $fakeThis->adapter->query_write($sql, 1);
    $result['value'] = "success";
    return $result;
}

function load_action(& $fakeThis, $request) {    
    $requestingPage = $request['requesting_page'];
    $fieldUniqueId = $request['request_field'];
    $thisFieldSession = & $_SESSION['_AcField'][$requestingPage][$fieldUniqueId];
    $thisFieldSession['loaded_join_clause'] = '';
    /**
     * SourceUniqueID is the AcField's unique ID of the field that *allegedly*
     * told this control to load (i.e. the client claims this field is a dependent
     * field of SourceUniqueID) 
     */
    $sourceUniqueId = $request['source_field'];
    $this_field = AcField::instance_from_id($fieldUniqueId);
    $table = $this_field->bound_table;
    $join_clause = '';

    if (!$this_field->loadable)
        throw_error(AcField::ERROR_LOAD_DISALLOWED); //security violation

    $values_clause = $this_field->bound_field . ' as answer ';

    if ($request['action'] === 'dynamic_load') {
        $where_clause['key_piece'] = $fakeThis->adapter->escape_table_name($this_field->bound_table)
                . "." . $fakeThis->adapter->escape_field_name($this_field->bound_pkey) . " = "
                . $fakeThis->adapter->escape_field_value($request['primaryInfo'][1]);


        if (isset($_SESSION['_AcField'][$requestingPage][$sourceUniqueId]['filters'])) {
            if (count($_SESSION['_AcField'][$requestingPage][$sourceUniqueId]['filters'])) {
                
                // Source_field = Field that told *this* to load
                $source_field = $_SESSION['_AcField'][$requestingPage][$sourceUniqueId];

                /**
                 * verify that this control is indeed allowed to update the other control. 
                 * Do this verification by looking at session records.
                 */
                if (!in_array($thisFieldSession["unique_id"], $source_field['dependent_fields'])) {
                    throw_error(AcField::ERROR_LOAD_DISALLOWED); //This indicates attempted hacking.
                }

                
                $join_clause[] = $source_field['bound_table'] . ' ON ' .
                        $fakeThis->adapter->escape_table_name($this_field->bound_table)
                        . "." . $fakeThis->adapter->escape_field_name($this_field->bound_pkey)
                        . " = " . $fakeThis->adapter->escape_table_name($source_field['bound_table'])
                        . "." . $fakeThis->adapter->escape_field_value($source_field["bound_pkey"]);

                //All join clauses need to have INNER JOINs between them
                if (count($join_clause) > 0) {
                    $join_clause = 'INNER JOIN ' . join($join_clause, ' INNER JOIN ');
                }            
                
            $thisFieldSession['loaded_join_clause'] = $join_clause;
            $where_clause['join_piece'] = join($_SESSION['_AcField'][$requestingPage][$sourceUniqueId]['filters'], ' AND ');
            }
        }//</if filtered>
        
        $thisFieldSession['loaded_pkey'] = $request['primaryInfo'][1];
    } else {
        //Hardcoded Load
        $where_clause[] = $fakeThis->adapter->escape_table_name($this_field->bound_table)
                . '.' . $fakeThis->adapter->escape_field_name($this_field->bound_pkey)
                . ' = ' . $fakeThis->adapter->escape_field_value($thisFieldSession['hardcoded_loads'][$request['primaryInfo'][1]]);

        $thisFieldSession['loaded_pkey'] = $thisFieldSession['hardcoded_loads'][$request['primaryInfo'][1]];
    }
    $thisFieldSession['loaded_where_clause'] = $where_clause;

    
    /*
     * Generate the actual query
     */
    $sql = "SELECT $values_clause FROM $table $join_clause WHERE " . join(' AND ', $where_clause);
    $result = $fakeThis->adapter->query_read($sql);

    /**
     * Since this is limited by a primary key, it should return exactly one row
     */
    if (count($result) !== 1)
        throw_error("Incorrect number of rows returned from read");

    $result = array('value' => $result[0]['answer']);

    return $result;
}

/**
 * Not implemented. Will allow client side code to request record insertion, in
 * a verifiable accurate way. 
 */
function insert_action() {
// I haven't yet ported this to the new format for this library and thus it is non-functional
    /* {
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
      } */
}

?>