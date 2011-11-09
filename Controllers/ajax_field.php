<?PHP

function json_error($x)
{
	die (json_encode(array("criticalError" => $x)));	
} 

function auto_error($err,$b="",$c="",$d="",$e="")
{
	json_error( " " . implode(",", func_get_args()));
}
if (get_magic_quotes_gpc()) 
	{
    function stripslashes_gpc(&$value)
    	{
        $value = stripslashes($value);
    	}
	array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	}
header('Expires: Fri, 09 Jan 1981 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Content-Type: text/html; charset=iso-8859-1');
header('Pragma: no-cache');

require_once "query_wrapper.php";
require_once "include.php";


error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
set_error_handler ("auto_error", E_ERROR | E_PARSE | E_USER_ERROR );

global $sql;
$var = json_decode($_REQUEST['request']);

$dataRequest = json_decode($_REQUEST['request'], 1);
$requester_page = $dataRequest['requesting_page'];
$fieldUniqueId = $dataRequest['request_field'];
$sourceUniqueId = $dataRequest['source_field'];

//var_dump($dataRequest); echo "<BR><BR>";
$limitations = $_SESSION['_AcField'][$requester_page][$dataRequest['primaryInfo'][1]];

$this_field =& $_SESSION['_AcField'][$requester_page][$fieldUniqueId];
$table = $this_field['bound_table'];
$values = $dataRequest["fieldInfo"];

/*if (($dataRequest['action'] === "addOrUpdate"))
	{
	 $where_clause_copy =  $where_clause;
	 $where_clause_copy [] = '1=1';	
	 $where_clause_copy  =  implode(" AND ", $where_clause_copy );
	 
	if (database_fetch_field("SELECT COUNT(*) FROM $table WHERE $where_clause_copy "))
		$dataRequest['action'] = "save"; // there is a record to update so update it	
	else
		{
		$dataRequest['action'] = "insert"; // there is not, so insert
		$values = array_merge($values, $limitations); //instead of limiting to primary keys, we need to set primary keys
		$limitations = array();
		unset($where_clause); //important
		}
	}*/
//////////////////////////////////////////////////////////////////////////////////	
				
if (($dataRequest['action'] === "save"))
	{
	if (! $this_field['savable'])
		die("Field not savable."); //security violation
	 
	foreach ($values as $x)
		$values_clause[] = _AcField_escape_field_name($this_field['bound_field'])  . " =  " . _AcField_escape_field_value($x[1]) . " ";

	$values_clause = implode(" , ", $values_clause);
	if ($this_field['type'] == 'multi')
		verify_control_could_contain_value($requester_page, $fieldUniqueId, ($x[1]) , "optionValue") or json_error("expectedError");
	}
/*elseif (($dataRequest['action'] === "insert"))
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
	}
/* Disabled. This method of doing this is just begging for errors.
*/
/*elseif (($dataRequest['action'] == "append"))
	foreach ($values as $x)
		{
		$values_clause[] = "[" . cleanFieldName($x[0]) . "]" . " =  " . "[" . cleanFieldName($x[0]) . "] + " . cleanFieldValue($x[1], 1) . " ";
		if (strpos($SECURITY_PERMISSIONS["normal"][$table], "R") === false)
			trigger_error("Insufficient Permissions to read from table $table");
		}		*/
elseif (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load")	)
	{
	if (! $this_field['loadable'])
		die("Field not loadable"); //security violation
		
	$values_clause = $this_field['bound_field'] . " as answer ";

	if ($dataRequest['action'] === "dynamic_load")	
		{
		$where_clause['key_piece'] = $this_field["bound_table"] . "." . $this_field['bound_pkey'] . " = " . _AcField_escape_field_value( $dataRequest['primaryInfo'][1] ) ;
		if ( count($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters']) )
			{
			$source_field = $_SESSION['_AcField'][$requester_page][$sourceUniqueId];
						
			if (! in_array($this_field["unique_id"], $source_field['dependent_fields']) ) //verify that this control is indeed allowed to update the other control. Do this verification by looking at session records.
				die("NOT A MATCH");
				
			$join_clause[] = $source_field['bound_table'] . " ON " . $this_field['bound_table'] . "." . $this_field["bound_pkey"] . " = " . $source_field['bound_table'] . "." . $source_field["bound_pkey"];
			if (count($join_clause))
				$join_clause = "INNER JOIN " . join($join_clause, " INNER JOIN ");
			$this_field['loaded_join_clause'] = $join_clause;
			$where_clause['join_piece'] = join($_SESSION['_AcField'][$requester_page][$sourceUniqueId]['filters'], " AND ");
			}
		}
	else
		$where_clause[] = $this_field['bound_table'] . "." . $this_field['bound_pkey'] . " = " . $this_field['hardcoded_loads'][$dataRequest['primaryInfo'][1]];
	$this_field['loaded_where_clause'] = $where_clause;
	}
else
	{
	trigger_error("Unknown action type requested in ajax_field: " . $dataRequest['action'], E_USER_ERROR );
	}

if (($dataRequest['action'] === "hardcoded_load") || ($dataRequest['action'] === "dynamic_load"))
	{
	$sql = "SELECT count(*) as count_rec FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
	//echo $sql;
	$security_check = _AcField_call_query_read($sql);
	if (! $security_check[0]['count_rec'])
		json_error("Record not found.");
			
	$sql = "SELECT $values_clause FROM $table $join_clause WHERE " . join(" AND ", $where_clause );
	$result = _AcField_call_query_read($sql);
	$result = array("value" => $result[0]['answer']);
	}
elseif ($dataRequest['action'] === "save")
	{
	if (! isset($this_field['loaded_where_clause']))
		die(); //library isn't currently designed to securely handle a save before a load. 
		
	$sql = "SELECT COUNT(*) as count_rec from $table " .  $this_field['loaded_join_clause'] . " WHERE " . join($this_field['loaded_where_clause'], " AND ");
	
	$security_check = _AcField_call_query_read($sql); // By seeing how many rows our current where clause selects we can add an additional level of security.
	if (! $security_check[0]['count_rec']) // This is the critical line that ensures that the filters that limit which values are loaded (as stored in the loaded_where_clause) also 
		die("security violation");			// apply to the value which will be saved here. That is, if this field can only load values WHERE X < 3 then we can apply the same WHERE X < 3 check on our save.
	else if ($security_check[0]['count_rec'] > 1) // You probably don't want to do something that affects multiple rows since you are usually operating on primary key. 
		json_error("Cancelled. Affects multiple rows."); //However if you know what you are doing, you can disable this restriction by commenting these two lines.
	
	$sql = "UPDATE $table SET  $values_clause  WHERE  " . join($this_field['loaded_where_clause'], " AND ");
	_AcField_call_query_write($sql, 1);
	$result['value'] = "success";
	}

else
	{
	trigger_error("Unknow Action:" . $dataRequest['action'], E_USER_ERROR);
	die ();
	}

$debug = false;
if ($debug)
	var_dump($dataRequest);
if ($debug)
	 echo $sql;
	 
//database_query('set textsize 65536');	 Good idea for MsSql
//$rs = database_query($sql, 0);

if ($dataRequest['action'] === 'load')
	{
	$result['value'] = $row[0];//Our result should be the first field in the first row.
	}
echo json_encode($result);


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function manual_error($err, $sql) //specific to this file
{
	$callStack = print_r(debug_backtrace(), 1);	
	$message = $err . " on " . $sql . " and " . $callStack;
	
	json_error($message);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>