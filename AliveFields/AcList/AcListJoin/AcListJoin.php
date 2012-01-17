<?PHP
/**
 *  Used for lists that represent join tables and thus pull from a join table on
 *  the backend but look like plain old (multi-select) lists on the front end
 * 
 * This is used more many-to-many relationships and allows both read and write
 * (inserting and deleting records from the join table). 
 * 
 * @author Alex Rohde
 * 
 */
class AcListJoin extends AcList 
{
    public  $join_table, $join_to_right_field, $join_from_right_field;
    private $multi_validators;
    public $mode; //"default" or "limited"
    
    function __construct($field, $table, $id, $join_table, $join_to_right_field, 
                                    $join_from_right_field, $loadable, $savable)
    {
        parent::__construct($field,$table,$id,(int)$loadable,(int)$savable);
        $this->join_to_right_field = $join_to_right_field;
        $this->join_from_right_field = $join_from_right_field;
        $this->join_table = $join_table;
        $this->multi_validators = array();
        $this->mode = "default";
    }    

    //////////////////////////////////////////////////////////////////////////////////////////////////
    // 
    function do_insert_validations( $prev_value_assoc_array)
    {
        //NEED TO WRITE THIS . Maybe move it to AcField
        foreach ($this->multi_validators as $validator_multi)
            {
                if (!$validator_multi($prev_value, $key_val))
                    return false;
            }
        return true;
    }        
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    function do_js_includes_for_this_control()
    {  //Unique to AcField
        AcField::include_js_file(AcField::$path_to_jquery);            
        AcField::include_js_file(Acfield::$path_to_controls . "/AcControls.js");    
        AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcSelectbox.js");
        AcField::include_js_file(Acfield::$path_to_controls . "/AcSelectbox/AcJoinSelectbox.js");
    }    
    
    
    //////////////////////////////////////////////////////////////////////////////////////////////////
    // 
    function do_multi_validations(& $prev_value, $key_val)
    {
        //NEED TO WRITE AN INSERT HANDLER FOR THIS.
        foreach ($this->multi_validators as $validator_multi)
            {
                if (!$validator_multi($prev_value, $key_val))
                    return false;
            }
        return true;
    }
 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //    
    function get_field_type_for_javascript()
    { 
        return "AcJoinSelectbox";
    }
        
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    function request_handler($request)
     {
        if (($request['AcFieldRequest'] == 'getfield') || ($request['AcFieldRequest'] == 'savefield'))
                {
                require_once (Acfield::$path_to_start_php . "/_internalInclude/ajax_field_multiple.php");
                handle_multiple_field($request); //sole function in above file.
                die();
                }
    
        parent::request_handler($request);
    }
}