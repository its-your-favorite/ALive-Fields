<?PHP

/**
 * Defines the necessary functionality for an adapter
 * 
 */
interface AcAdapter_Interface {

    function query_read($query, $limit);

    function query_write($query, $limit);

    function escape_field_name($field, $addQuotes);

    function escape_field_value($field, $addQuotes);

    function escape_table_name($field, $addQuotes);
}
