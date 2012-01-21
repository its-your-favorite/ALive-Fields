<?PHP

/**
 * An adapter for unit testing
 *
 *
 * Last Revision: 
 * Date: January 2011 
 * 
 * @author Alex Rohde
 */
// /* Prepend this line with // to alternate blocks
class AcAdapterTest implements AcAdapter_Interface {

    private $queries;

    public function __construct() {
        
    }

    /**
     * Handle Error, 
     * @param $x str, the error message
     * 
     * This function will be called by Alive Fields in the event a library error occurs. 
     * You can adapt it to handle errors in whichever way you want.
     */
    function handleError($x) {
        die($x);
    }

    /*
     *  Execute a SELECT query.
     *  Allows read access only. Useful in minimizing sql injection possibilities.
     */

    function query_read($query, $limit_rows_returned = 0) {
        global $conn_readonly;
        $DEBUG = false;
        $result = NULL;

        // YOU MUST CHANGE THE FOLLOWING LINE IF YOU ARE NOT USING MYSQL ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        //In mysql (default) we limit selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limit_rows_returned > 0)
            $query .= " LIMIT " . (int) $limit_rows_returned;

        $queries[] = $query;
        return array();
    }

    /**
     * Execute an UPDATE, INSERT, or DELETE query
     * allows write access.
     * 
     */
    function query_write($query, $limit_rows_affected = 0) {
// Limit rows affected is a safety precaution which isn't strictly necessary if this tool is used properly, but certainly is recommended. Customize its use to your database (e.g. Set rowcount for mssql)
        global $conn_readwrite;

        //In mysql (default) we updates selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limit_rows_affected > 0)
            $query .= " LIMIT " . (int) $limit_rows_affected;

        $queries[] = $query;
        return array();
    }

////////////////////////////////////////////////////////////////

    function escape_field_name($field, $add_quotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_name_mysql($field, $add_quotes);
    }

////////////////////////////////////////////////////////////////

    function escape_table_name($field, $add_quotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_name_mysql($field, $add_quotes); //happens to be the same for mysql
    }

////////////////////////////////////////////////////////////////

    function escape_field_value($field, $add_quotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_value_mysql($field, $add_quotes);
    }

////////////////////////////////////////////////////////////////

    private function escape_field_name_mysql($field) {
        return "`" . mysql_real_escape_string($field) . "`";
    }

    private function escape_field_name_mssql($field) {
        return "[" . str_replace(array(" ", "'", "[", "]", "\\", "`", "&"), "", $field) . "]";
    }

////////////////////////////////////////////////////////////////


    function escape_field_value_mysql($val, $add_quotes = true) {
        if ($add_quotes)
            return "'" . mysql_real_escape_string($val) . "'";
        else
            return mysql_real_escape_string($val);
    }

    function escape_field_value_mssql($val, $add_quotes = true) {
        if ($add_quotes)
            return "'" . str_replace("'", "''", $val) . "'";
        else
            return str_replace("'", "''", $val);
    }

}

?>