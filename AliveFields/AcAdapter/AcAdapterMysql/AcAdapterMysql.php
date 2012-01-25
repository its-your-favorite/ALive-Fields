<?PHP

/**
 * An adapter for a Mysql database
 *
 *  *
 * This file needs to do is initialize a database connection and define a function
 *  call_query which takes SQL and returns an array (rows) of associative arrays
 *  (fieldname => fieldvalue). It also provides information on the database
 *  connection and database-specific sql-injection prevention.
 *
 * Last Revision:
 * Date: January 2011
 *
 * @author Alex Rohde
 */
class AcAdapterMysql implements AcAdapter_Interface {

    private $conn_readonly;
    private $conn_readwrite;

    public function __construct($host, $user_readonly, $pass_readonly, $db, $user_readwrite = null, $pass_readwrite = null) {
        if ($user_readwrite === null)
            $user_readwrite = $pass_readonly;
        if ($pass_readwrite === null)
            $pass_readwrite = $pass_readwrite;

        $this->conn_readonly = mysql_connect($host, $user_readonly, $pass_readonly)
                or handleError("Could not connect to Mysql database for read.");
        mysql_query("USE $db;", $this->conn_readonly);

        $this->conn_readwrite = mysql_connect($host, $user_readwrite, $pass_readwrite)
                or handleError("could not connect to Mysql database for write.");
        mysql_query("USE $db;", $this->conn_readwrite);
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
        $DEBUG = false;
        $result = NULL;

        //In mysql (default) we limit selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limit_rows_returned > 0)
            $query .= " LIMIT " . (int) $limit_rows_returned;

        $rs = mysql_query($query, $this->conn_readonly);
        if (!$rs) {
            throw new ErrorException("Failed on query $query. = " . mysql_error());
        }

        while ($row = mysql_fetch_assoc($rs))
            $result[] = $row;

        return $result;
    }

    /**
     * Execute an UPDATE, INSERT, or DELETE query
     * allows write access.
     *
     * @param string $query The SQL query that communicates how we want this adapter to change its data.
     * @param type $limit_rows_affected a safety precaution which isn't strictly necessary if this tool is used properly, but certainly is recommended.
     */
    function query_write($query, $limit_rows_affected = 0) {

        if ($limit_rows_affected > 0)
            $query .= " LIMIT " . (int) $limit_rows_affected;

        $rs = mysql_query($query, $this->conn_readwrite);
        if (!$rs) {
            throw new ErrorException("Failed on query $query " . mysql_error());
        }
    }

    /**
     * Escape a field name in a way to prevent SQL injection
     *
     * @param type The field to escape
     * @param type Whether or not to add quotes
     * @return Escaped field name
     */
    function escape_field_name($field, $add_quotes = true) {

        return $this->escape_field_name_mysql($field, $add_quotes);
    }

    /**
     * Escape a table name in a way to prevent SQL injection
     *
     * @param type The table to escape
     * @param type Whether or not to add quotes
     * @return Escaped table name
     */
    function escape_table_name($field, $add_quotes = true) {

        return $this->escape_field_name_mysql($field, $add_quotes); //happens to be the same for mysql
    }

    /**
     * Escape a field value in a way to prevent SQL injection
     *
     * @param type The value to escape
     * @param type Whether or not to add quotes
     * @return Escaped field value
     */
    function escape_field_value($field, $add_quotes = true) {

        return $this->escape_field_value_mysql($field, $add_quotes);
    }

    /**
     * Escape a fieldname in a way appropriate for a mysql database
     * @param type $field
     * @return type
     */
    private function escape_field_name_mysql($field) {
        return "`" . mysql_real_escape_string($field) . "`";
    }

    /**
     * Escape a field value in a way appropriate for a mysql database
     * @param type $field
     * @return type
     */
    function escape_field_value_mysql($val, $add_quotes = true) {
        if ($add_quotes)
            return "'" . mysql_real_escape_string($val) . "'";
        else
            return mysql_real_escape_string($val);
    }

    /** Kept in case I add a sql server adapter
     * Escape a fieldname in a way appropriate for a sql server database
     * @param type $field
     * @return type

      private function escape_field_name_mssql($field) {
      return "[" . str_replace(array(" ", "'", "[", "]", "\\", "`", "&"), "", $field) . "]";
     * }
     *
     * function escape_field_value_mssql($val, $add_quotes = true) {
      if ($add_quotes)
      return "'" . str_replace("'", "''", $val) . "'";
      else
      return str_replace("'", "''", $val);
      }
     */
}

?>