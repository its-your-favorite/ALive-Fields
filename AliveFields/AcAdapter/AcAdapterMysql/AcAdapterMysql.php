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

    private $connReadonly;
    private $connReadwrite;

    public function __construct($host, $userReadonly, $passReadonly, $db, $userReadwrite = null, $passReadwrite = null) {
        if ($userReadwrite === null)
            $userReadwrite = $passReadonly;
        if ($passReadwrite === null)
            $passReadwrite = $passReadwrite;

        $this->connReadonly = mysql_connect($host, $userReadonly, $passReadonly)
                or handleError("Could not connect to Mysql database for read.");
        mysql_query("USE $db;", $this->connReadonly);

        $this->connReadwrite = mysql_connect($host, $userReadwrite, $passReadwrite)
                or handleError("could not connect to Mysql database for write.");
        mysql_query("USE $db;", $this->connReadwrite);
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

        //In mysql (default) we limit selects by appending the string LIMIT X to them. Change this line according to your database
        if ($limitRowsReturned > 0)
            $query .= " LIMIT " . (int) $limitRowsReturned;

        $rs = mysql_query($query, $this->connReadonly);
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
     * @param type $limitRowsAffected a safety precaution which isn't strictly necessary if this tool is used properly, but certainly is recommended.
     */
    function query_write($query, $limitRowsAffected = 0) {

        if ($limitRowsAffected > 0)
            $query .= " LIMIT " . (int) $limitRowsAffected;

        $rs = mysql_query($query, $this->connReadwrite);
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
    function escape_field_name($field, $addQuotes = true) {

        return $this->escape_field_name_mysql($field, $addQuotes);
    }

    /**
     * Escape a table name in a way to prevent SQL injection
     *
     * @param type The table to escape
     * @param type Whether or not to add quotes
     * @return Escaped table name
     */
    function escape_table_name($field, $addQuotes = true) {

        return $this->escape_field_name_mysql($field, $addQuotes); //happens to be the same for mysql
    }

    /**
     * Escape a field value in a way to prevent SQL injection
     *
     * @param type The value to escape
     * @param type Whether or not to add quotes
     * @return Escaped field value
     */
    function escape_field_value($field, $addQuotes = true) {

        return $this->escape_field_value_mysql($field, $addQuotes);
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
    function escape_field_value_mysql($val, $addQuotes = true) {
        if ($addQuotes)
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
     * function escape_field_value_mssql($val, $addQuotes = true) {
      if ($addQuotes)
      return "'" . str_replace("'", "''", $val) . "'";
      else
      return str_replace("'", "''", $val);
      }
     */
}

?>