<?PHP
/*!
 * ALive Fields V 1.0
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 * 
 * This file needs to do is initialize a database connection and define a function call_query which takes SQL and returns an array (rows) of associative arrays (fieldname => fieldvalue). It also provides information on the database connection and database-specific sql-injection prevention.
 *
 *
 *
 * Last Revision: 
 * Date: Oct 28 2011 1:45PM
 */

// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** 
//connect to DB -- Change this to whatever SQL resource you are using. If you wish to adapt this to not rely on SQL, that can be done by altering ajax_field.php

//     /* Prepend this line with // to alternate blocks
 global $conn_readonly;
$conn_readonly = mysql_connect("localhost:3306", "newuser_readonly", "a") or handleError("could not connect to database. Please check settings in query_wrapper.php ");
mysql_query("USE Test;", $conn_readonly);
/*/ 
$conn_readonly = mysql_connect("db387843467.db.1and1.com", "dbo387843467", "horsebatterymagnet") or handleError("could not connect to database. Please check settings in query_wrapper.php");
mysql_query("USE db387843467", $conn_readonly);
/**/

// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
//     /* Prepend this line with // to alternate blocks
 global $conn_readwrite;
$conn_readwrite = mysql_connect("localhost:3306", "newuser_readwrit", "bligsby cheese") or handleError("could not connect to database. Please check settings in query_wrapper.php ");
mysql_query("USE Test;", $conn_readwrite);
/*/
$conn_readwrite = mysql_connect("db387843467.db.1and1.com", "dbo387843467", "horsebatterymagnet") or handleError("could not connect to database. Please check settings in query_wrapper.php");
mysql_query("USE db387843467", $conn_readwrite);
/**/

////////////////////////////////////////////////////////////////////////
/* 				SETTING UP QUERY WRAPPER 
1. Lines you need to change regardless of your database:  23, 27, 28, 48
2. Lines you need to change if your database is not MySql: 59, 79, 89, 96
3. You can test your output with 
	- var_dump(call_query_read("SHOW TABLES;")); 
	- should look something like: array(1) { [0]=> array(1) { ["Tables_in_test"]=> string(9) "new_table" } } depending on your tables.


Other Notes:
 If you're using the obselete mssql driver for php (< 5.3 I believe) then you may want to: database_query('set textsize 65536');
*/

function _AcField_handleError($x)
{
	die ($x); //You can customize this if you wish to store your errors in a database rather than displaying them, a good security practice for deployment mode.
}

////////////////////////////////////////////////////////////////

function _AcField_call_query_read ($query, $limit_rows_returned = 0) // allows read access only. Useful in minimizing sql injection possibilities.
{
 global $conn_readonly;
 $DEBUG = false;
 $result = NULL;
 
 // ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
 if ($limit_rows_returned > 0) //In mysql (default) we limit updates by appending the string LIMIT X to them. Change this line according to your database
  	$query .= " LIMIT " . (int)$limit_rows_returned;
	
 $rs = mysql_query($query, $conn_readonly) or _AcField_handleError(($DEBUG ? "Failed on query $query": "") . mysql_error());	

 while ($row = mysql_fetch_assoc($rs))
 	$result[] = $row;
	
 return $result;
}

////////////////////////////////////////////////////////////////

function _AcField_call_query_write ($query, $limit_rows_affected = 0) 
// allows write access.
// Limit rows affected is a safety precaution which isn't strictly necessary if this tool is used properly, but certainly is recommended. Customize its use to your database (e.g. Set rowcount for mssql)
{
  global $conn_readwrite;
  
// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !   ** ! ** !
  if ($limit_rows_affected > 0) //In mysql (default) we limit selects by appending the string LIMIT X to them. Change this line according to your database
  	$query .= " LIMIT " . (int)$limit_rows_affected;
	
 	$rs = mysql_query($query, $conn_readwrite) or _AcField_handleError(($DEBUG ? "Failed on query $query" : "") . mysql_error());	
}

////////////////////////////////////////////////////////////////

function _AcField_escape_field_name ($field, $add_quotes = true)
{
	// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
	return _AcField_escape_field_name_mysql($field, $add_quotes);
}

////////////////////////////////////////////////////////////////

function _AcField_escape_table_name ($field, $add_quotes = true)
{
	// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
	return _AcField_escape_field_name_mysql($field, $add_quotes); //happens to be the same for mysql
}

////////////////////////////////////////////////////////////////

function _AcField_escape_field_value ($field,  $add_quotes = true)
{
	// ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
	return _AcField_escape_field_value_mysql($field, $add_quotes);
}

////////////////////////////////////////////////////////////////

function _AcField_escape_field_name_mysql($field)
{
	return "`" . mysql_real_escape_string($field) . "`";
}

function _AcField_escape_field_name_mssql($field)
{
	return "[" . str_replace(array(" ", "'", "[", "]", "\\", "`", "&"), "", $field) . "]";
}

////////////////////////////////////////////////////////////////


function _AcField_escape_field_value_mysql($val, $add_quotes = true)
{
	if ($add_quotes)
		return "'" . mysql_real_escape_string($val) . "'";
	else
		return mysql_real_escape_string($val);
}

function _AcField_escape_field_value_mssql($val, $add_quotes = true)
{
	if ($add_quotes)
		return "'" . str_replace("'", "''", $val) . "'";
	else
		return str_replace("'", "''", $val) ;
}

?>