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
     /**     
     * @var string "default" or "limited" Determines if control shows all possible values
     */
    public $mode; 
    public  $joinTable, $joinToRightField, $joinFromRightField;
    private $multiValidators;

    /**
     * Constructor
     *  
     * @param string $field Same as in AcField
     * @param string $table Same as in AcField
     * @param string $id Same as in AcField
     * @param string $joinTable Table name 
     * @param string $joinToRightField Field name
     * @param string $joinFromRightField Field name
     * @param CONST $loadable  Same as in AcField
     * @param CONST $savable  Same as in AcField
     */
    function __construct($field, $table, $id, $joinTable, $joinToRightField, 
                                    $joinFromRightField, $loadable, $savable)
    {
        parent::__construct($field,$table,$id,(int)$loadable,(int)$savable);
        $this->joinToRightField = $joinToRightField;
        $this->joinFromRightField = $joinFromRightField;
        $this->joinTable = $joinTable;
        $this->multiValidators = array();
        $this->mode = "default";
    }    

   /**
    * Serves to let the programmer easily restrict which values can be added to
    * a AcListJoin (because it's a many to many, values are INSERTS)
    * 
    * @INCOMPLETE
    * @param array $prev_value_assoc_array
    * @return boolean 
    */
    
    function do_insert_validations( $prev_value_assoc_array)
    {
        return true;
        //NEED TO WRITE THIS . Maybe move it to AcField
        foreach ($this->multiValidators as $validatorMulti)
            {
                if (!$validatorMulti($prev_value, $key_val))
                    return false;
            }
        return true;
    }        
    
   /**
    *  { @inheritdoc}
    */
    function do_js_includes_for_this_control()
    {  //Unique to AcField
        AcField::include_js_file(AcField::PATH_TO_JQUERY);            
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");    
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcSelectbox/AcSelectbox.js");
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcSelectbox/AcJoinSelectbox.js");
    }    
    
    
   /**
    * Validations
    * 
    * @INCOPMLETE
    * @param type $prevValue
    * @param type $keyVal
    * @return boolean 
    */
    function do_multi_validations(& $prevValue, $keyVal)
    {
        //NEED TO WRITE AN INSERT HANDLER FOR THIS.
        foreach ($this->multiValidators as $validatorMulti)
            {
                if (!$validatorMulti($prevValue, $keyVal))
                    return false;
            }
        return true;
    }
 
    // See parent docblock
    function get_field_type_for_javascript()
    { 
        return "AcJoinSelectbox";
    }
        
    
    //see parent docblock
    function request_handler($request)
     {
        if (($request['AcFieldRequest'] == 'loadfield') || ($request['AcFieldRequest'] == 'savefield'))
                {
                require_once (__DIR__ . "/../../_internalInclude/ajax_field_multiple.php");
                return handle_multiple_field($this, $request); //sole function in above file.
                die();
                }
    
        return parent::request_handler($request);
    }
}