<?PHP
// Requires AcField. Include AcField directly which will include this.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AcList extends AcField
{	
	function AcList($field_type, $field, $table, $id, $loadable, $savable)
	{
		parent::__construct($field_type,$field,$table,$id,(int)$loadable,(int)$savable);
		$tmp = &parent::get_session_object();
		$tmp['options_field'] = $field; //default to same values.
		$tmp['options_table'] = $table;
		$tmp['options_pkey'] = $id; 				
		$tmp['type'] = "multi";
		$tmp['options_loadability'] = $loadable;
	//	echo "<HR>";
	//	var_dump($tmp);		
	}
	
	function differentiate_options($field, $table, $id, $populatability)
	{
		$tmp = &$this->get_session_object();
		$tmp['options_field'] = $field; //default to same values.
		$tmp['options_table'] = $table;
		$tmp['options_pkey'] = $id; 		
		$tmp['options_loadability'] = $populatability;
	//	echo "Val: " . $populatability;
	//	echo "<HR>";
	//	var_dump($tmp);
	//	die($populatability);
	}
	
	function bind($html_element_id, $autoload = true)
	{
		$this->add_output( $this->get_js_fieldname() . ".initialize(\"#" . $html_element_id . "\", null, " . (int)$autoload . "); ");			
	}
}

//$x = new AcList(0,0,0,0,0,0);
?>
