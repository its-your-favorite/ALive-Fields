<?PHP

/**
 * Serves as the one necessary include that handles all other includes.
 *
 *
 */
require_once("_internalInclude/general_functions.php");

function internalAutoloader($name) {
    $dir = explode("/", __DIR__);
    $dir = explode("\\", end($dir));
    $thisDir = end($dir);

    if ($name === "AcList")
        require_once("AcList/$name.php");
    elseif (substr($name, 0, 6) === "AcList")
        require_once("AcList/$name/$name.php");
    elseif ($name === "AcField")
        require_once("AcField/$name.php");
    elseif ($name === "AcAdapter_Interface")
        require_once("AcAdapter/AcAdapter_Interface.php");
    elseif (substr($name, 0, 9) === "AcAdapter")
        require_once("AcAdapter/$name/$name.php");
    elseif (substr($name, 0, 2) === "Ac")
        require_once("AcField/$name/$name.php");
}

spl_autoload_register("internalAutoloader");
