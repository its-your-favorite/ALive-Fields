<?PHP

	session_start();
	function ensure_logged_in() {/* hypothetical. In your app you would check the session to do this AT THIS POINT;*/}
	ensure_logged_in(); //It is important to validate security at this point, before AcFields.

	require_once( "AcField.php");

	///////////////////// Main File //////////////////////////////////////////////////////////////////////////////	
	
	$user_enabled = new AcCheckbox("enabled", "users", "userID", 1, 1);
	$user_enabled->bind("user_enabled");
	
	$join_date = new AcDatebox("join_date", "users", "userID", 1, 1);
	$join_date->bind("user_join_date");

	$article_content = new AcTextbox("content", "articles", "articleID", 1, 1);
	$article_content->bind("content");
	
	// This is also valid.
	//$article_content->register_validator( array("unique" => true, "length" => ">0", "regex" => '/^[0-9]+ace/') );
		$article_content->register_validator(	function ($new_value) 
				{					
					return (strpos($new_value, "fail") === false);
				}); 
	
	$users_articles = new AcListSelect("title", "articles", "articleID", 1, 0);
	$users_articles->bind("articles");
	$users_articles->set_dependent_fields(array($article_content));

	$users_departments_join = new AcListJoin("department_name", "departments", "departmentID", "join_users_departments", "department_id", "departmentID",  1, 1);
	$users_departments_join->bind("departments_select");
	$users_departments_join->mode = "limited";
	
	$all_the_users = new AcListCombo("username", "users", "userID", 2, 0);
	$all_the_users->bind("all_users");
	$all_the_users->set_dependent_fields(array($user_enabled, $join_date));
	$all_the_users->set_filtered_fields(array(array("control" => $users_articles, "field" => "author"), array("control" => $users_departments_join, "field" => "user_id" )) );


	AcField::handle_all_requests(); //In the event that this page is being called as an ajax call to load/save data (or return a list etc) handle appropriately.
	
	///// End Of PHP /////////////////////////////////////////////////////
	// This is all the code a user of this library would need to write to make the page that you see. 
	// EXCEPT the one call to AcField::flush_output(), which should come AFTER all the referenced html elements are defined.
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?PHP
AcField::flush_head_output();

/*  If you were to set include to manual, you would need to include these javascript files.
<script src="js/jquery.js" ></script>
 <script src="js/controls/AcControls.js"></script>
 <script src="js/jquery-ui.js"></script>
<script src="js/controls/AcSelectbox/AcSelectbox.js"></script>
<script src="js/controls/AcCombobox/AcCombobox.js"></script>
<script src="js/controls/AcCheckbox/AcCheckbox.js"></script> 
<script src="js/controls/AcDatebox/AcDatebox.js"></script>
<script src="js/controls/AcTextbox/AcTextbox.js"></script>
<script src="js/controls/AcSelectbox/AcJoinSelectbox.js"></script>
*/
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link link rel="stylesheet" type="text/css" href="css/custom-theme/jquery-ui-1.8.16.custom.css"  />
<title>ALive Fields Demo App</title>
<style type="text/css">
<!--
body {
	font: 100%/1.4 Verdana, Arial, Helvetica, sans-serif;
	background: #4E5869;
	margin: 0;
	padding: 0;
	color: #000;
}

/* ~~ Element/tag selectors ~~ */
ul, ol, dl { /* Due to variations between browsers, it's best practices to zero padding and margin on lists. For consistency, you can either specify the amounts you want here, or on the list items (LI, DT, DD) they contain. Remember that what you do here will cascade to the .nav list unless you write a more specific selector. */
	padding: 0;
	margin: 0;
}
h1, h2, h3, h4, h5, h6, p {
	margin-top: 0;	 /* removing the top margin gets around an issue where margins can escape from their containing div. The remaining bottom margin will hold it away from any elements that follow. */
	padding-right: 15px;
	padding-left: 15px; /* adding the padding to the sides of the elements within the divs, instead of the divs themselves, gets rid of any box model math. A nested div with side padding can also be used as an alternate method. */
}
a img { /* this selector removes the default blue border displayed in some browsers around an image when it is surrounded by a link */
	border: none;
}

/* ~~ Styling for your site's links must remain in this order - including the group of selectors that create the hover effect. ~~ */
a:link {
	color:#414958;
	text-decoration: underline; /* unless you style your links to look extremely unique, it's best to provide underlines for quick visual identification */
}
a:visited {
	color: #4E5869;
	text-decoration: underline;
}
a:hover, a:active, a:focus { /* this group of selectors will give a keyboard navigator the same hover experience as the person using a mouse. */
	text-decoration: none;
}

/* ~~ this container surrounds all other divs giving them their percentage-based width ~~ */
.container {
	width: 80%;
	max-width: 1260px;/* a max-width may be desirable to keep this layout from getting too wide on a large monitor. This keeps line length more readable. IE6 does not respect this declaration. */
	min-width: 780px;/* a min-width may be desirable to keep this layout from getting too narrow. This keeps line length more readable in the side columns. IE6 does not respect this declaration. */
	background: #FFF;
	margin: 0 auto; /* the auto value on the sides, coupled with the width, centers the layout. It is not needed if you set the .container's width to 100%. */
}

/* ~~ the header is not given a width. It will extend the full width of your layout. It contains an image placeholder that should be replaced with your own linked logo ~~ */
.header {
	background: #6F7D94;
}

/* ~~ These are the columns for the layout. ~~ 

1) Padding is only placed on the top and/or bottom of the divs. The elements within these divs have padding on their sides. This saves you from any "box model math". Keep in mind, if you add any side padding or border to the div itself, it will be added to the width you define to create the *total* width. You may also choose to remove the padding on the element in the div and place a second div within it with no width and the padding necessary for your design.

2) No margin has been given to the columns since they are all floated. If you must add margin, avoid placing it on the side you're floating toward (for example: a right margin on a div set to float right). Many times, padding can be used instead. For divs where this rule must be broken, you should add a "display:inline" declaration to the div's rule to tame a bug where some versions of Internet Explorer double the margin.

3) Since classes can be used multiple times in a document (and an element can also have multiple classes applied), the columns have been assigned class names instead of IDs. For example, two sidebar divs could be stacked if necessary. These can very easily be changed to IDs if that's your preference, as long as you'll only be using them once per document.

4) If you prefer your nav on the left instead of the right, simply float these columns the opposite direction (all left instead of all right) and they'll render in reverse order. There's no need to move the divs around in the HTML source.

*/
.sidebar1 {
	float: right;
	width: 20%;
	background: #93A5C4;
	padding-bottom: 10px;
}
.content {
	padding: 10px 0;
	width: 80%;
		float: right;
}

/* ~~ This grouped selector gives the lists in the .content area space ~~ */
.content ul, .content ol { 
	padding: 0 15px 15px 40px; /* this padding mirrors the right padding in the headings and paragraph rule above. Padding was placed on the bottom for space between other elements on the lists and on the left to create the indention. These may be adjusted as you wish. */
}

/* ~~ The navigation list styles (can be removed if you choose to use a premade flyout menu like Spry) ~~ */
ul.nav {
	list-style: none; /* this removes the list marker */
	border-top: 1px solid #666; /* this creates the top border for the links - all others are placed using a bottom border on the LI */
	margin-bottom: 15px; /* this creates the space between the navigation on the content below */
}
ul.nav li {
	border-bottom: 1px solid #666; /* this creates the button separation */
}
ul.nav a, ul.nav a:visited { /* grouping these selectors makes sure that your links retain their button look even after being visited */
	padding: 5px 5px 5px 15px;
	display: block; /* this gives the link block properties causing it to fill the whole LI containing it. This causes the entire area to react to a mouse click. */
	text-decoration: none;
	background: #8090AB;
	color: #000;
}
ul.nav a:hover, ul.nav a:active, ul.nav a:focus { /* this changes the background and text color for both mouse and keyboard navigators */
	background: #6F7D94;
	color: #FFF;
}

/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background: #6F7D94;
	position: relative;/* this gives IE6 hasLayout to properly clear */
	clear: both; /* this clear property forces the .container to understand where the columns end and contain them */
}

/* ~~ miscellaneous float/clear classes ~~ */
.fltrt {  /* this class can be used to float an element right in your page. The floated element must precede the element it should be next to on the page. */
	float: right;
	margin-left: 8px;
}
.fltlft { /* this class can be used to float an element left in your page. The floated element must precede the element it should be next to on the page. */
	float: left;
	margin-right: 8px;
}
.clearfloat { /* this class can be placed on a <br /> or empty div as the final element following the last floated div (within the #container) if the #footer is removed or taken out of the #container */
	clear:both;
	height:0;
	font-size: 1px;
	line-height: 0px;
}
-->
</style><!--[if lte IE 7]>
<style>
.content { margin-right: -1px; } /* this 1px negative margin can be placed on any of the columns in this layout with the same corrective effect. */
ul.nav a { zoom: 1; }  /* the zoom property gives IE the hasLayout trigger it needs to correct extra whiltespace between the links */
</style>
<![endif]--></head>

<body>

<div class="container">
  <div class="header"><a href="#"><img src="alexrohde.jpg" alt="Insert Logo Here" name="Insert_logo" width="50%" height="100" id="Insert_logo" style="background: #8090AB; display:block;" border="1"/></a> 
    <!-- end .header --></div>
  <div class="sidebar1">
    <ul class="nav">
      <li><a href="#">Administrate</a></li>
      <li><a href="#">View Settings</a></li>
      <li><a href="#">Blahblah</a></li>
      <li><a href="#">Logout</a></li>
    </ul>
    <p>Blah Blah this is just a right container that could contain anything and is pretty much irrelevant to this Alive Fields demonstration.</p>
    <p>Blah Blah this is just a right container that could contain anything and is pretty much irrelevant to this Alive Fields demonstration.<br />
      <br />
      <br />
      <br />
      <br />
      <br />
    </p>
    <p></p>
    <!-- end .sidebar1 --></div>
  <div class="content">
    <h1><a href="#">Basic Dreamweaver Layout</a></h1>
    <p>This is a basic dreamweaver layout. My point in using it here is to illustrate that Alive Fields are independent of any HTML.</p>
    <h2>Administrate Users:</h2>
    <table width="100%" border="1">
      <tr>
        <td>Users<br />
		<input name="all_users" id="all_users" style="width:150px">
		</td>
        
        <td>
        User's Articles<br />
		<select name="articles" size="10" id="articles" style="width:150px"></select>
		</td>
		
        <td>
		User Belongs to these Departments (select multiple)<br />        
        <select multiple="multiple" name="departments_select" size="10" id="departments_select" style="width:150px"></select>
        </td>
        
      </tr>
      <tr>
        <td>User is enabled:
          <input type="checkbox" id="user_enabled" />
          <br />
          Join Date
<input type="text" id="user_join_date" />
        <br /></td>
        <td colspan="1">Article's content          
          <br />
        <textarea id="content" rows="5" cols="53" /></textarea></td>
        <td>
        You could put department-joined fields here...
        </td>
      </tr>
    </table>
    <p>&nbsp;</p>
    <br />

  <!-- end .content --></div>
  <div class="footer">
    <p>This is the footer.</p>
    <!-- end .footer --></div>
  <!-- end .container --></div>
  <script language="javascript">
  <?PHP	
	AcField::flush_output();
  ?>
   </script>

</body>
</html>