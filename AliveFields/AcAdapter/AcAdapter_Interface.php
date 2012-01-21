<?PHP

/**
 * Defines the necessary functionality for an adapter
 * 
 */
interface AcAdapter_Interface {

    function query_read($query, $limit);

    function query_write($query, $limit);

    function escape_field_name($field, $add_quotes);

    function escape_field_value($field, $add_quotes);

    function escape_table_name($field, $add_quotes);
}
