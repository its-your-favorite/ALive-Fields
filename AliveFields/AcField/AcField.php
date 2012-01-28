<?PHP

/* !
 * Main Class file, containing the base class for all PHP controls.
 * 
 * This abstract base class performs all the vital functions to the page including:
 *     - creating and outputting necessary javascript to power client-side interactivity
 *  - outputting necessary includes dynamically for relevant javascript files
 *  - storing necessary security information in the session
 *  - handling and directing client side requests to the appropriate controllers
 *  - passing client side requests to validators.
 *  - tl;dr : this class does everything except the html view, which you do by hand.
 * 
 * 
 * @todo Make variables camelCase to be consistent with the zend coding standard.
 * @todo consolidate error reporting, turn off deprecated on release mode. (alexrohde.com server)
 * @todo unit test for acField loads that operate through a join for test-criteria
 * 
 * @todo make unit tests for multi fields.
 * @todo refactor Check the other controllers too.
 * @todo move to mysqli 
 * @todo Make sure validations work of both types on the new whatcha-ma-call-it list
 * @todo to do: make new superior error handler thingies.
 * @todo to do: differentiate options does nothing?
 * @todo to do: try two fields filtering one, to be sure it works
 * @todo security tests listed in ajax_field_multiple
 * @todo add validations for join table multi (right now I think they don't filter, do validate)
 * @todo clean up javascript by using parent instead of AcWHATEVER (if possible)
 *      and use the type of call that automatically passes params as an array.
 * @todo To do: ReMake a date-time control.
 * @todo Testing -- locking on all types of fields
 * @todo make unit testing on every subclass of AcField.
 * 
 * @todo make sure my jsDoc is up to date
 * @todo C) A way to instead of making things auto-save, allow them to be 
 *     controlled by two buttons (auto-inserted) that read "Save" or
 *      "Restore Value". Key is easy extensibility!
 * @todo See if I can trim down my controllers enough that it's not
 *  necessary to keep them in separate files
 * 
 * @todo A) server side validations: make them return the new value in the 
 * event that they change the value, so that the client-side control can 
 *     immediately know what actually was saved.
 * @todo B) Validations: make them allow a certain specific error message on a
 *      validation fail. 
 *
 * @todo test out this pretty jqUI stuff here: 
 *         http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/
 * @todo to do: Consider connecting my app with ZF
 * 
 * @todo Investigate alternatives to using the session.  The complicated result
 *  of this though, if we wish to drop the whole SESSION reliance,  is that it
 *   will require giant validation queries, with multiple joins. For example if
 *    A updates B updates C and we try to set C to 6, we need to check B could
 *     contain 6 for a value that A could contain (and so on and so forth). 
 *     OOOR. Encryption // RSA fingerprint. I can think of a simple way to do 
 *     this with SHA but is it valid?
 *     
 * @version 1.01
 * 
 * @author Alex Rohde http://alexrohde.com/
 * @copyright Alex Rohde 2011
 * @license GPL Version 2 license.
 *
 * Includes jquery.js, jqueryUI.js
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 *
 * Includes json2.js
 * http://www.JSON.org/json2.js
 *
 * Last Revision: 
 * Date: January 14 2012 
 */

@session_start();
AcField::$uniqueSessionToken = md5($_SERVER['PHP_SELF'] . microtime());

/**    AcField is the PHP heart of this project. 
 * 
 * It is the base class from which all other
 * components derive (much in the way that AcControl is in the javascript portion). 
 *     All of these classes act as controllers, handling various ajax requests 
 *  and connecting themselves to your view (which is pure HTML).
 * 
 *  @abstract
 *  
 */
abstract class AcField {
    /**
     * Consts are written this way because it'll be easier when using autocomplete.
     * You only need to recall the first word (Load) and then the remaining suffixes
     * will all appear
     */

    //
    const LOAD_NO = 0;
    const LOAD_WHEN_FILTERED = 1;
    const LOAD_YES = 2;

    /**
     * SAVE_NO_CHANGE_NO -
     * Additionally tells the client-side control to act as read-only / locked. 
     */
    const SAVE_NO_CHANGE_NO = -1;    
    const SAVE_NO = 0;
    const SAVE_YES = 1;

    /**
     * The coder has stipulated that this field should not be loadable likely by using READ_NO. 
     */
    const ERROR_LOAD_DISALLOWED = "Field not loadable";

    /**
     * The coder has stipulated that this field should not be savable likely by using SAVE_NO.
     */
    const ERROR_SAVE_DISALLOWED = "Field not savable";
    const ERROR_INVALID_TOKEN = "Invalid Token In Session";

     /**
     *  These constants should reflect your directory structure. Adjust as
     *  necessary.
      * 
      * THESE SHOULD START with a forward slash and not end with one
     */
    const INCLUDE_DIRECTORY_JS = "js";    
    const PATH_TO_JQUERY = "/jquery.js";
    const PATH_TO_JQUERYUI  = "/jquery-ui.js";
    const PATH_TO_CONTROLS = "/controls";
    
    const PATH_TO_START_PHP = "AliveFields";
    
    public static $includedJsFiles; // to prevent double-inclusion of controls
    public static $outputMode = "postponed";
    public static $cachedOutput;
    public static $cachedHeadOutput;
    public static $basicsIncluded;
    
    public static $includeJsManually = false; //by default, handle including JS files for the user
    public static $silenceErrors = false;
    public static $uniqueSessionToken; //unique to each request
    private static $declarationProgress = 0; //Help new users of the library avoid basic errors.
    private static $defaultAdapter;
    protected static $allInstances;
    //Definitions to explain the following fields:
    public $adapter;
    public $boundField, $boundTable, $boundPkey, $filters;
    public $loadable, $savable; //whether this control can LOAD and or SAVE to the database
    public $type;
    public $filteredFields; //keep public!
    public $hardcodedLoads;
    private $uniqueId;
    protected $validators;

    /**
     * 
     * A control's internal value will be: SELECT $id FROM $table 
     * A control's displayed value will be: SELECT $field FROM $table
     *       WHERE $id = (loaded_value)      
     *
     * @param string $field The fieldname of the field which this control should represent
     * @param string $table The table in which $field is stored
     * @param string $id Fieldname that acts as the primary key in $table.
     * @param CONST $loadable Expects Ac_Field::LOAD_NO, ::LOAD_YES, or ::LOAD_WHEN_FILTERED
     * @param CONST $savable Expects Ac_Field::SAVE_NO, ::SAVE_YES, or ::SAVE_NO_CHANGE_NO
     */
    function __construct($field, $table, $id, $loadable, $savable) {
        $this->include_basics();
        $this->validators = array();
        $this->filteredFields = array();
        $this->boundField = $field;
        $this->boundTable = $table;
        $this->boundPkey = $id;

        $this->loadable = $loadable;
        $this->savable = $savable;
        $this->filters = array(); //Filters applied TO this AcField
        $this->uniqueId = $this->generate_unique_id();
        AcField::$allInstances[$this->uniqueId] = &$this;

        if (AcField::$declarationProgress > .25)
            AcField::register_error("Please declare all AcFields before proceeding to call handle_all_requests");
        AcField::$declarationProgress = .25;

        $this->add_output("\n\nvar " . $this->get_js_fieldname() . " = "
                . " new " . $this->get_field_type_for_javascript()
                . "($this->uniqueId, $loadable, $savable, null); \n "
                . $this->get_js_fieldname() . ".uniqueId = '" . $this->uniqueId . "';");

        if (!AcField::$includeJsManually)
            $this->do_js_includes_for_this_control();

        if (AcField::$defaultAdapter !== null)
            $this->adapter = AcField::$defaultAdapter;

        /*
         * This serves to let the controller action verify at least that
         * the request has visited the client side and gotten the token
         * PAGE_INSTANCE. 
         */
        $sess = & $this->get_session_object();
        $sess['enabled'] = true;
    }

    /**
     * Destructor. Provide useful error message in event of newbie mistake.
     */
    function __destruct() {
        if ($this->adapter === null)
            AcField::register_error("Please make sure all fields have an adapter.");
        if (AcField::$declarationProgress < 2)
            AcField::register_error("Flush output should be called somewhere in the document.");
    }

    /**
     * Useful for var_dumps
     */
    function __toString() {
        return "An AcField (" . get_class($this) . ") ID #" . $this->uniqueId;
    }

    /**
     * This function sets the filters applied *TO* this field. That is to say, 
     * the filters that *affect this field* [NOT filters from this field]. 
     * This is opposite of Set_filtered_fields
     * 
     * @param [multiple] $filt Accepts either a single filter [assoc array] or 
     * an array of filters [multidimensional array]
     * Each filter is an array with 3 elements: [field], [relationship], [value]
     * e.g. UserId > 6 or Username like 'Jon' or Age = 3
     */
    function add_filters($filt) {
        if (is_array($filt)) {
            // If the first element (of $filt) is itself an array i.e. this function is being passed an array of filters
            if (is_array($filt[reset(array_keys($filt))])) {
                foreach ($filt as $f) {
                    if (strpos($f[0], ".") === false)
                        $f[0] = _AcField_escape_table_name($this->boundTable) . "." . _AcField_escape_field_name($f[0]);
                    $this->filters[] = join($f, " ");
                }
            }
            else { // only being passed one filter
                $this->filters = array_merge($this->filters, $filt);
            }
        }
        else
            handleError("Must pass array to add_filters");
    }

    /**
     * Add output that should be displayed in the head (namely javascript includes)
     */
    static function add_head_output($htmlCode) {
        AcField::$cachedHeadOutput .= $htmlCode . "\n";
    }

    /**
     * Displays provided javascript appropriately.
     */
    static function add_output($jsCode) {
        AcField::$cachedOutput .= $jsCode . "\n";

        if (AcField::$outputMode != "postponed")
            AcField::flush_output();
    }

    /**
     * Inform the javascript class which HTML element id it should connect with.
     *
     * @param string $htmlElementId The id of the html element to connect this control to.
     */
    function bind($htmlElementId) {    //spit out bound javascript    
        $this->add_output($this->get_js_fieldname() .
                ".initialize(\"#" . $htmlElementId . "\"); ");
    }

    /**
     * Determine if this field meets all validator criteria
     *      
     * @param variant $prevValue Unvalidate value
     * @param variant $keyVal ID of unvalidate value's row (primary key)
     * @return boolean 
     */
    function do_validations(& $prevValue, $keyVal) {
        foreach ($this->validators as $validator) {
            if (!$validator($prevValue, $keyVal))
                return false;
        }
        return true;
    }

    /**
     * Include the relevant javascript files necessary to power the view.
     */
    function do_js_includes_for_this_control() {  //Unique to AcField
        AcField::include_js_file(AcField::PATH_TO_JQUERY);
        AcField::include_js_file(Acfield::PATH_TO_CONTROLS . "/AcControls.js");
    }

    /**
     * Dumps includes that need to be in the HEAD of the html document
     */
    static function flush_head_output() {
        echo AcField::$cachedHeadOutput;
        AcField::$cachedHeadOutput = "";

        //Generate useful error messages in the event that a new user declares things out of order.
        if (AcField::$declarationProgress < .5)
            AcField::register_error("Please call handle-all-requests before any "
                    . " output and call flush_head_output in the "
                    . " head of the document. Thus flush_head_output"
                    . " should not be called first. ");
        elseif (AcField::$declarationProgress != .5)
            AcField::register_error("Flush head output should be called one time, before flush "
                    . "output, after handle_all_requests, and in the HEAD section"
                    . " of the HTML document");
        AcField::$declarationProgress = 1;
    }

    /**
     * Outputs the entire output buffer. 
     * 
     * This is necessary because controls are declared before the document start, where obviously
     * javascript cannot be displayed yet.  
     */
    static function flush_output() {
        //Generate useful error messages in the event that a new user declares things out of order.        
        if (!AcField::$declarationProgress && !AcField::$includeJsManually)
            AcField::register_error("Be sure to flush head output before flushing output.");

        AcField::$declarationProgress = 2;
        echo AcField::$cachedOutput;
        AcField::$cachedOutput = "";
    }

    /**
     *    Outputs the decided headers for a controller's ajax response to the view.
     */
    public function generate_controller_header() { //ideally make private at some point        
        header('Expires: Fri, 09 Jan 1981 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Content-Type: text/html; charset=iso-8859-1');
        ////not application/json for good complicated reasons.
        header('Pragma: no-cache');

        remove_magic_quotes(); // In the event your webserver has them enabled and doesn't give you the option to change it    
        error_reporting(E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
        set_error_handler("auto_error", E_ERROR | E_PARSE | E_ALL ^ E_NOTICE);
    }

    /**
     * @return string  
     */
    abstract function get_field_type_for_javascript();

    /**
     * Create a unique id for each AcField control
     * 
     * @staticvar int $x The total number of controls instantiated
     * @return int 
     */
    private static function generate_unique_id() {
        static $x;
        return++$x;
    }

    /**
     * Provides the instance name of the javascript object that 
     * this class communicates with.
     * 
     * @return string
     */
    function get_js_fieldname() {
        return "AcField" . $this->uniqueId;
    }

    /**
     * Return a unique session spot for this control to store relevant security
     * information that can't be trusted to the client.
     */
    function &get_session_object() {
        global $PAGE_INSTANCE;
        $uniqueKey = AcField::$uniqueSessionToken;

        if (!isset($_SESSION['_AcField'][$uniqueKey][$this->uniqueId]))
            $_SESSION['_AcField'][$uniqueKey][$this->uniqueId] = array();

        return $_SESSION['_AcField'][$uniqueKey][$this->uniqueId];
    }

    /**
     * accessor 
     */
    public function get_unique_id() {
        return $this->uniqueId;
    }

    /**
     *    Dispatch the request to the appropriate Acfield's request_handler, so it can act as the controller.
     * 
     */
    static function handle_all_requests() {   // In release mode, I recommend changing these $_REQUEST to post for a minor reduction in xsrf risk, and just for consistency with the meaning of GET and POST.
        if (AcField::$declarationProgress < .25)
            AcField::register_error("Please declare your AcFields before calling handle_all_requests");
        AcField::$declarationProgress = .5;

        if (!isset($_REQUEST['request']))
            return; //No ajax requests. I.E. We're just loading the page normally.
        else {
            AcField::$declarationProgress = 100; //Don't monitor declaration progress in ajax 
            // request mode, we obviously don't want javascript declared.
            $request = json_decode($_REQUEST['request'], true);

            if (isset($request['AcFieldRequest'])) {
                try {
                    AcField::instance_from_id($request['request_field'])->generate_controller_header();
                    echo json_encode(AcField::instance_from_id($request['request_field'])->request_handler($request));
                    die();
                } catch (Exception $e) {
                    echo json_encode(array("criticalError" => $e->getMessage()));
                    die();
                }
            }
        }
    }

    /**
     * This function sets up a dependency for the page exactly once.
     */
    function include_basics() {
        global $PAGE_INSTANCE;
        if (!AcField::$basicsIncluded) {
            AcField::$basicsIncluded = true;
            AcField::$outputMode = "postponed";
            AcField::add_output("function AcFieldGetThisPage() { "
                    . " return '" . AcField::$uniqueSessionToken . "'; }");
        }
    }

    /**
     * Includes a given javascript file
     * 
     * @param string $file a javascript filename
     */
    static function include_js_file($file) {
       /*
        if (strlen(AcField::INCLUDE_DIRECTORY_JS) && (substr(AcField::INCLUDE_DIRECTORY_JS, 0, 1) == '/'))
            AcField::INCLUDE_DIRECTORY_JS = ltrim(AcField::INCLUDE_DIRECTORY_JS, "/");
        if (strlen(AcField::INCLUDE_DIRECTORY_JS) && (substr(AcField::INCLUDE_DIRECTORY_JS, -1) == '/'))
            AcField::INCLUDE_DIRECTORY_JS = rtrim(AcField::INCLUDE_DIRECTORY_JS, "/");
        if (strlen($file) && (substr($file, 0, 1) != '/'))
            $file = '/' . $file;*/
        if (isset(AcField::$includedJsFiles[$file])) //prevent double-inclusion of files
            return;

        AcField::$includedJsFiles[$file] = true;
        AcField::add_head_output("<script language='javascript' src='" . AcField::INCLUDE_DIRECTORY_JS . "$file'></script>");
    }

     /**
     * This function returns a particular instance from an ID (useful for passing IDs through session)
     *
     * Every instance of AcField is generated a unique id (integer). This allows us to have one unique 
     *     number that acts as an identifier *across requests* to show us where to direct controller requests to.
     * 
     * This function convers such a unique id back into the relevant instance of AcField
     * @param int $id The id for which you seek the corresponding AcField.
     * @return AcField The correspondin AcField
     */
    static function instance_from_id($id) {
        if (!isset(static::$allInstances[$id]))
            return false;
        $tmp = static::$allInstances[$id];
        return $tmp;
    }
    
    /**
     *    Load a value of a field based on a specified key. 
     */
    function load_unchecked($key) { //Take a key that refers to a primary key value in the table, and store it in the session to 
        // prevent client-side manipulation that would allow arbitrary load requests.
        $hardcodedKeyId = generate_unique_id();

        $this->hardcodedLoads[$hardcodedKeyId] = $key;

        $this->add_output($this->get_js_fieldname() . ".loadField( $hardcodedKeyId, 'static'); ");
    }

    /**
     * outputs library-generated errors.
     * 
     * Alter this function as appropriate for your level of experience, error reporting system, and development/release systems.
     */
    static function register_error($string) {
        if (!AcField::$silenceErrors) {
            trigger_error("Error: $string. <br>\n You may find the wiki useful: https://github.com/anfurny/ALive-Fields/wiki/Using-the-Library");
            die();
        }
    }

    /**
     * This function registers a validator
     */
    function register_validator($callback) {
        $ourVersion = explode(".", phpversion());
        if (is_array($callback)) {//using an assoc array
            if (isset($callback["length"])) {
                preg_match_all('/[0-9]?<' . '?' . '>' . '?=?/', $callback["length"], $matches);
                $lengthExpr = join($matches[0], "");

                if ($ourVersion[0] >= 5) {
                    $this->register_validator(function($val) use ( $lengthExpr ) {
                                $x = strlen($val);
                                return eval("return $x $lengthExpr;");
                            });
                }
            }
            if (isset($callback['regex'])) {
                $this->register_validator(function($val) use ($callback) {
                            try {
                                $res = preg_match($callback['regex'], $val);
                            } catch (Exception $e) {
                                throw_error("Error in Regex Validator");
                            }
                            if ($res === false)
                                throw_error("error in regex validator");
                            return $res;
                        });
            }

            if (isset($callback['uniqueness']) || isset($callback['unique'])) {
                if (isset($callback['uniqueness'])) {
                    $callback['unique'] = $callback['uniqueness'];
                }
                $copy = $this;
                $this->register_validator(function($val, $pkey) use ($callback, $copy) {
                            $query = "SELECT count(*) as res from " . $this->adapter->escape_table_name($copy->boundTable) . " WHERE  "
                                    . $this->adapter->escape_field_name($copy->boundField) . " = " . $this->adapter->escape_field_value($val)
                                    . " AND " . $this->adapter->escape_field_name($copy->boundPkey) . " != " . $this->adapter->escape_field_value($pkey);
                            $result = $this->adapter->query_read($query);
                            return (($result[0]['res'] == 0) == (bool) ($callback['unique']));
                        });
            }
        }
        else
            $this->validators[] = $callback;
    }

     /**
     * Sets the backend adapter for this Ajax control Field
     *
     * @param (implements AcAadpter_Interface) $adapter The backed adapter to 
     *      retrieve/retain AcField values.
     */
    function set_adapter($adapter) {
        $this->adapter = $adapter;
    }
    
    
    /**
     * This function determine loads and executes the relevant controller for this request.
     * 
     *  This handles the back-end power ajax requests submitted by the views, for this particular field.
     *  This acts as the controller to the view request.
     */
    function request_handler($request) {
        if (($request['AcFieldRequest'] == 'loadfield') || ($request['AcFieldRequest'] == 'savefield')) {
            require_once (__DIR__ . "/../_internalInclude/ajax_field.php");
            return acField_Controller($this, $request);
        } else {
            return throw_error("Nonexistant Request");
        }
    }

    static public function set_default_adapter($adapter) {
        AcField::$defaultAdapter = $adapter;
    }

    /**
     * Set the list of fields that this field will call load upon when this field changes value.
     *
     * @see documentation
     * @param array of AcField $arr the fields to update
     */
    function set_dependent_fields($arr) {
        $tmp = &$this->get_session_object();

        $this->add_output($this->get_js_fieldname() . ".dependentFields = [];");
        foreach ($arr as $i) {
            $tmp['dependent_fields'][] = $i->uniqueId;
            $this->add_output($this->get_js_fieldname() . ".dependentFields.push ( " . $i->get_js_fieldname() . ");");
        }
    }

    /**
     * This sets the array of filters that STEM FROM this field (not that apply to it).
     *  In this respect this function is the opposite of add_filters.
     * Accepts an array of filters (assoc arrays) that have keys  
     * [type] = ("value" / "text") , ['control'] = the actual AcField being updated
     */
    function set_filtered_fields($filt) {
        $mySessionObject = &$this->get_session_object();
        $mySessionObject['filtered_fields'] = $this->filteredFields = array();

        foreach ($filt as $i) {
            if (!isset($i['type']))
                $i['type'] = "value";

            $mySessionObject['filtered_fields'][] = array($i['control']->uniqueId, $i['field'], $i['type']);
            $this->filteredFields[] = array($i['control']->uniqueId, $i['field'], $i['type']);

            $newFilter[] = $i['control']->get_js_fieldname();
        }
        $this->add_output($this->get_js_fieldname() . ".filteredFields = [" . join($newFilter, ", ") . "]; ");
        return NULL;
    }

    /**
     * Sets an attribute of the corresponding javascript class instance.
     */
    function set_property($prop, $val) {
        $this->add_output($this->get_js_fieldname() . ".$prop = $val;");
    }


}

?>
