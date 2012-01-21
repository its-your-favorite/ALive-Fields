<?php
date_default_timezone_set('America/Los_Angeles');
require_once( "UnitTests.php");
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PermissionsTest extends AcResponseTest
{
    
     /**
     * Make sure a request with obselete token cannot be loaded
     * 
     * @expected ErrorException
     */
    public function testInvalidSession()
    {
        //make sure we can't read from a field that doesn't have read permission        
        $request = '{"AcFieldRequest":"loadfield","primaryInfo":[null,"2"],"requesting_page":"FAKE!","request_field":"1","action":"dynamic_load","source_field":"1"}';              
        ////DON'T CORRECT TOKEN
        
        $textbox = new AcTextbox("TestField", "TestTable", "id", AcField::LOAD_YES, AcField::SAVE_YES);

                
        try {
         echo $textbox->request_handler(json_decode($request, true) );
        }
        catch (Exception $e) {
            $this->assertTrue($e instanceOf ErrorException);
            $this->assertTrue($e->getMessage() === AcField::ERROR_INVALID_TOKEN);
        }
    }
    
    /**
     * Make sure a field without load permission cannot be loaded
     * 
     * @expected ErrorException
     */
    public function testDenyRead()
    {
        //make sure we can't read from a field that doesn't have read permission
        $request = '{"AcFieldRequest":"loadfield","primaryInfo":[null,"2"],"requesting_page":"","request_field":"","action":"dynamic_load","source_field":"1"}';
        $request = json_decode($request, true);        
        
        $textbox = new AcTextbox("TestField", "TestTable", "id", AcField::LOAD_NO, AcField::SAVE_NO);

        $request['requesting_page'] = AcField::$unique_session_token;//make token valid
        $request['request_field'] = $textbox->get_unique_id(); //make request match this control
                       
        try {
            $textbox->request_handler($request);
        }
        catch (Exception $e) {
            $this->assertTrue($e instanceOf ErrorException);
            $this->assertTrue($e->getMessage() === AcField::ERROR_LOAD_DISALLOWED);
            return;
        }        
        $this->assertTrue(false);//shouldn't get here
    }
    
    
    /**
     * Make sure a field without write permission cannot save 
     *
     * @expected ErrorException
     */
   public function testDenyWrite()
   {
        $request ='{"AcFieldRequest":"savefield","fieldInfo":[[null,"New Value"]],"action":"save","requesting_page":"","request_field":""}';
        $request = json_decode($request, true);        
        
        $textbox = new AcTextbox("TestField", "TestTable", "id", AcField::LOAD_YES, AcField::SAVE_NO);

        $request['requesting_page'] = AcField::$unique_session_token;//make token valid
        $request['request_field'] = $textbox->get_unique_id(); //make request match this control
             
        try {
            $textbox->request_handler($request);
            }
        catch (Exception $e) {
            $this->assertTrue($e instanceOf ErrorException);
            $this->assertTrue($e->getMessage() === AcField::ERROR_SAVE_DISALLOWED);
            return;
        }        
        $this->assertTrue(false);//shouldn't get here
   }         
   
    /**
     * Make sure a field with load permission can load normally.
     * 
     * @expected ErrorException
     */
    public function testAllowRead()
    {
        //make sure we can't read from a field that doesn't have read permission
        $request = '{"AcFieldRequest":"loadfield","primaryInfo":[null,"2"],"requesting_page":"","request_field":"","action":"dynamic_load","source_field":"1"}';
        $request = json_decode($request, true);        
        
        $textbox = new AcTextbox("TestField", "TestTable", "id", AcField::LOAD_YES, AcField::SAVE_NO);
  
        $request['requesting_page'] = AcField::$unique_session_token;//make token valid
        $request['request_field'] = $textbox->get_unique_id(); //make request match this control
        
        $result = $textbox->request_handler($request);
        $this->assertTrue($result['value'] === "Def");
    }  
           
    /**
     * Makes sure save can work
     */
   public function testAllowWrite()
   {
        $key_to_save = '4';
        $request_load = '{"AcFieldRequest":"loadfield","primaryInfo":[null,"' . $key_to_save . '"],"requesting_page":"","request_field":"","action":"dynamic_load","source_field":"1"}';
        $request_save = '{"AcFieldRequest":"savefield","fieldInfo":[[null,"New Value"]],"action":"save","requesting_page":"","request_field":""}';
        
        $request_load = json_decode($request_load, true);
        $request_save = json_decode($request_save, true);
        
        $textbox = new AcTextbox("TestField", "TestTable", "id", AcField::LOAD_YES, AcField::SAVE_YES);
                
        $request_load['requesting_page'] = AcField::$unique_session_token;//make token valid
        $request_load['request_field'] = $textbox->get_unique_id(); //make request match this control
        
        $request_save['requesting_page'] = AcField::$unique_session_token;//make token valid
        $request_save['request_field'] = $textbox->get_unique_id(); //make request match this control        
        
        $result = $textbox->request_handler($request_load); //right now, saves require a preceding load.
        $result = $textbox->request_handler($request_save);
        $found_val = $this->db_adapter->query_read("SELECT TestField FROM TestTable where ID='4'");
        
        $this->assertTrue($found_val[0]['TestField'] === "New Value");
   }
}
?>
