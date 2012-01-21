<?php
date_default_timezone_set('America/Los_Angeles');
require_once("../AliveFields/start.php");

//require_once dirname(__FILE__) . '/../unit_tests.php';

/**
 * Test class for .
 * Generated by PHPUnit on 2012-01-18 at 15:07:01.
 */
class AcResponseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var textbox
     * @var selectMulti
     */
    protected $db_adapter;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        /**
         * General requirements 
         */
        date_default_timezone_set('America/Los_Angeles');
        
        $ini = parse_ini_file("../.iniUnitTesting");
        $this->db_adapter = new AcAdapterMysql($ini['host'] , $ini['user_read'], $ini['pass_read'],
                                    $ini['db'], $ini['user_write'], $ini['pass_write']);
    
        AcField::set_default_adapter($this->db_adapter);        
        $this->db_adapter->query_write("DROP TABLE IF EXISTS TestTable");
        
        // create a table
        //$db_adapter->query_write("USE AC_UNIT")
        $this->db_adapter->query_write("
             CREATE  TABLE `TestTable` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `TestField` VARCHAR(45) NULL ,
            `TestFieldInt` INT NULL ,
            `TestFieldDate` DATETIME NULL ,
            `TestFieldVarchar` VARCHAR(45) NULL ,
            PRIMARY KEY (`id`) ); "                    ); 
        
       //insert data
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Abc', 1, '2001-01-01 1:00', 'Heyo');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Def', 3, '2002-02-02 2:00', 'Listen');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Ghi', 5, '2003-03-03 3:00', 'What');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Jkl', 7, '2004-04-04 4:00', 'I');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Mno', 9, '2005-05-05 5:00', 'Sayo');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Pqr', 11, '2006-06-06 6:00', 'La');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Stu', 13, '2007-07-07 7:00', 'Da');");
       $this->db_adapter->query_write("INSERT INTO `TestTable` (`TestField`, `TestFieldInt`, `TestFieldDate`, `TestFieldVarchar`) VALUES ('Vsr', 15, '2008-08-08 8:00', 'Dee');");
       AcField::$silence_errors = true;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }


}

?>
