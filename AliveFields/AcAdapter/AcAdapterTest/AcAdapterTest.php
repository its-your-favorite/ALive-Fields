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

    function query_read($query, $limitRowsReturned = 0) {
        $DEBUG = false;
        $result = NULL;

        // YOU MUST CHANGE THE FOLLOWING LINE IF YOU ARE NOT USING MYSQL ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        //In mysql (default) we limit selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limitRowsReturned > 0)
            $query .= " LIMIT " . (int) $limitRowsReturned;

        $queries[] = $query;
        return array();
    }

    /**
     * Execute an UPDATE, INSERT, or DELETE query
     * allows write access.
     * 
     */
    function query_write($query, $limitRowsAffected = 0) {
// Limit rows affected is a safety precaution which isn't strictly necessary if this tool is used properly, but certainly is recommended. Customize its use to your database (e.g. Set rowcount for mssql)
 
        //In mysql (default) we updates selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limitRowsAffected > 0)
            $query .= " LIMIT " . (int) $limitRowsAffected;

        $queries[] = $query;
        return array();
    }

////////////////////////////////////////////////////////////////

    function escape_field_name($field, $addQuotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_name_mysql($field, $addQuotes);
    }

////////////////////////////////////////////////////////////////

    function escape_table_name($field, $addQuotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_name_mysql($field, $addQuotes); //happens to be the same for mysql
    }

////////////////////////////////////////////////////////////////

    function escape_field_value($field, $addQuotes = true) {
        // YOU MUST CHANGE THE FOLLOWING LINE IF NOT MYSQL  ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** ! ** !
        return $this->escape_field_value_mysql($field, $addQuotes);
    }

////////////////////////////////////////////////////////////////

    private function escape_field_name_mysql($field) {
        return "`" . mysql_real_escape_string($field) . "`";
    }

    private function escape_field_name_mssql($field) {
        return "[" . str_replace(array(" ", "'", "[", "]", "\\", "`", "&"), "", $field) . "]";
    }

////////////////////////////////////////////////////////////////


    function escape_field_value_mysql($val, $addQuotes = true) {
        if ($addQuotes)
            return "'" . mysql_real_escape_string($val) . "'";
        else
            return mysql_real_escape_string($val);
    }

    function escape_field_value_mssql($val, $addQuotes = true) {
        if ($addQuotes)
            return "'" . str_replace("'", "''", $val) . "'";
        else
            return str_replace("'", "''", $val);
    }

}

?>