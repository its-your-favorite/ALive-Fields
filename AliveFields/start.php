<?PHP
//* Docblock here
include "query_wrapper.php";
include "general_functions.php";

function internalAutoloader($name)
{
	$dir = explode("/", __DIR__);
	$dir = explode("\\", end($dir));
	$this_dir = end($dir);

	if (substr($name,0,6) == "AcList")
		require_once("/$this_dir/AcList/$name.php");

	elseif (substr($name,0,2) == "Ac")
		require_once("/$this_dir/AcField/$name.php");
//	echo $name;
//	die();
}

spl_autoload_register("internalAutoloader");

