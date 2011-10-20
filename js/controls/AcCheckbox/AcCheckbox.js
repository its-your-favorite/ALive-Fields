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
* @class  AcCheckbox Very basic control for loading/saving fields that can be represented as booleans. It's initialized with an &lt;input type="checkbox"&gt; tag. 
* @extends AcField
* @requires AcControls.js and its dependencies.
*/
if (typeof(handleError) == "undefined")
	handleError = alert;
	
if (typeof(AcField) == "undefined")
	handleError("Must include AcControls before AcCheckbox.");

AcCheckbox =  function (field,table,pkey,loadable,savable,dependentFields)
{  // the reason we cannot use "this.prototype" here, is because extending classes will call this 
//function with the parent object, who's constructor is this function, causing an infinite loop.
  AcField.call(this, field,table,pkey,loadable,savable,dependentFields); //call parent constructor			
}
AcCheckbox.prototype = new AcField();  // Here's where the inheritance occurs
AcCheckbox.prototype.constructor=AcCheckbox; // Otherwise this would have a constructor of AcField
AcCheckbox.prototype.parent = AcField.prototype;

/////////////////////////////////////////////////////////////////////////////////

AcCheckbox.prototype.initialize=function(jqElementStr)
{
	jqElement = $(jqElementStr);
	AcField.prototype.initialize.call(this, jqElement);
	
  	this.correspondingElement.bind("click", this, function(myEvent)
	 	{
			if (myEvent.data.oldValue != myEvent.data.getValue()) 
	   			myEvent.data.handleChange();
		    myEvent.data.oldValue = myEvent.data.getValue();
		} );		
	
}

/////////////////////////////////////////////////////////////////////////////////

AcCheckbox.prototype.getValue=function()
{
return this.correspondingElement[0].checked;
}
/////////////////////////////////////////////////////////////////////////////////

AcCheckbox.prototype.getValueForSave=function()
{
return this.correspondingElement[0].checked * 1; //convert to numeric
}

//////////////////////////////////////////////////////////////////////////////////
AcCheckbox.prototype.setValue=function(param)
{
 if (param && (param != "0")) 
	this.correspondingElement[0].checked = true;
 else
 	this.correspondingElement[0].checked = false;
}
