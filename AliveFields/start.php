<?PHP

//* Docblock here
require_once("_internalInclude/general_functions.php");

function internalAutoloader($name) {
    $dir = explode("/", __DIR__);
    $dir = explode("\\", end($dir));
    $this_dir = end($dir);

    if ($name === "AcList")
        require_once("$this_dir/AcList/$name.php");
    elseif (substr($name, 0, 6) === "AcList")
        require_once("$this_dir/AcList/$name/$name.php");
    elseif ($name === "AcField")
        require_once("$this_dir/AcField/$name.php");
    elseif ($name === "AcAdapter_Interface")
        require_once("AcAdapter/AcAdapter_Interface.php");
    elseif (substr($name, 0, 9) === "AcAdapter")
        require_once("AcAdapter/$name/$name.php");
    elseif (substr($name, 0, 2) === "Ac")
        require_once("$this_dir/AcField/$name/$name.php");

}

spl_autoload_register("internalAutoloader");

