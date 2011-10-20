/*!
 * ALive Fields V 1.0
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 * Includes jquery.js, jqueryUI.js
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 *
 * Last Revision: 
 * Date: Oct 11 2011 2:00PM
 */

/**
* @class AcTextbox Very basic control for loading/saving fields that can be represented as simple strings. Is initialized with an &lt;input&gt; (or &lt;textarea&gt;) tag.
* @extends AcField
* @requires AcControls.js and its dependencies
*/
if (typeof(handleError) == "undefined")
	handleError = alert;
	
if (typeof(AcField) == "undefined")
	handleError("Must include AcControls before AcTextbox");

AcTextbox = function (field,table,pkey,loadable,savable,dependentFields)
{
 AcField.call(this, field,table,pkey,loadable,savable,dependentFields); //call parent constructor. Must be done this way-ish
}

AcTextbox.prototype.initialize = function(pkey)
{
 AcField.prototype.initialize.call(this, pkey);	
}

AcTextbox.prototype = new AcField();  // Here's where the inheritance occurs
AcTextbox.prototype.constructor=AcTextbox; // Otherwise instances of AcTextbox would have a constructor of AcField
AcTextbox.prototype.parent = AcField.prototype;

