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

/* 
* Javascript set of classes to extend standard HTML controls to make them ajax, powered
* and connect them to a database in a way that mirrors access.
* Requires jquery, Requires json2 (https://github.com/douglascrockford/JSON-js/blob/master/json2.js)
*/

/**
* @class AcSelectbox Acts as a combination dropdown-textbox with autocomplete functionality. Utilizes the JqueryUI dropdown control. Powerful control great for populating other controls. Also, it is filterable.
* @extends AcField
* @requires AcControls.js and its dependencies
* @requires Jquery UI
* @param ajaxMode This is the only parameter not in the standard AcField constructor. If this is set to true [default], then this control uses live value as most AcControls do. If set to false, the control must be populated manually by custom javascript.
*/
if (typeof(handleError) == "undefined")
	handleError = alert;
	
if (typeof(AcField) == "undefined")
	handleError("Must include AcControls before AcSelectbox");
		
AcSelectbox = function (a,b,c,d,e,dependentFields, ajaxMode)
{
 if (ajaxMode == undefined)
	 this.ajaxMode = true; // default to true
 else
 	this.ajaxMode = ajaxMode;

 AcField.call(this, a,b,c,d,e,dependentFields); //call parent constructor. Must be done this way-ish
 this.controlType = "AcSelect";
 this.loadedKey = null;
 this.readonly = false;
 this.loadingOptions = false;
 this.filters = {}; //to allow it to be an assoc-array and therefore json-able
 this.filteredFields = [];
 this.requestDistinct = false;
 this.overrideURL = null;
 this.dependentFields = [];
 
 this.defaultValue = null;
 this.defaultType = null;
 
 this.statusLoading = 0;
 
	this.optionsTable = this.correspondingTable; 
	this.optionsKeys = this.pkeyField; ///represents the values that are stored in each dropdown choice
	this.optionsTexts = this.correspondingField;//these values cannot be overriden in the constructor / initializer.
 }


AcSelectbox.prototype = new AcField();  // Here's where the inheritance occurs
AcSelectbox.prototype.constructor=AcSelectbox; 
AcSelectbox.prototype.parent = AcField.prototype;

/** A second constructor like function. 
* @param jqElementStr A jquery selector that points to a SELECT element.
* @param filteredFields An array of arrays dictating what other controls are filtered by this control and how.
* @param autoload If not true, this dropdown won't populate its options until another field causes it to. Useful if this field will never be used unfiltered.
* @param defaultValue The default value that should come up as selected when this dropdown populates choices. Should be a value that represents a pkey value of the desired choice.
*/
AcSelectbox.prototype.initialize = function(jqElementStr, filteredFields, autoload, defaultValue)
{
 jqElement = $(jqElementStr);
 if (autoload === undefined)
 	autoload = true; 

 if (filteredFields)
	 this.filteredFields = filteredFields;
 if (typeof(this.filteredFields) == "undefined")
 	this.filteredFields = [];

  if (jqElement.size() == 0)
  	return handleError("Error: Cannot find HTML element to bind to");
	
 oldId = jqElement[0].id;
 if (jqElement[0].nodeName.toLowerCase() != "select")
 	handleError("Error: Please be sure that all AcSelectboxes are bound to SELECT tags. ");

 this.defaultValue = defaultValue;
  
 AcField.prototype.initialize.call(this, jqElement);	
 jqElement.unbind("change");
 

 var myObject = this;
 
 jqElement.bind("change", function() 
  		{
	    if (myObject.oldValue != myObject.getValue()) 
	   		myObject.handleDropdownChange();	
	    myObject.oldValue = myObject.getValue();
		myObject.loadedKey = myObject.getKey();	
	    } );
		 
  if (autoload && (this.loadable > 1)) 
	  url = this.loadOptions(); 
}

////////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.loadField = function(primaryKeyData, type, source) 
{
	this.ensureLoading(source);
	this.parent.loadField.call(this, primaryKeyData, type, source);

}
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/** Load the list of choices for this control. Is called automatically on initialization.
*/ 
AcSelectbox.prototype.loadOptions = function(source)
{
	if (!this.ajaxMode)	
		return;
	
	this.statusLoading++;
	// disabled because it doesn't seem that useful AND it causes problems with AcJoinSelectbox which wants to load rows even if savable == -1
	//if ((this.savable == -1) && (this.defaultValue == null))
	//	return; ///*if savable is -1 then the field is read only and should only be populated with 1 value. */
		
	this.loadingOptions = true;
 	//clear all ?
	 this.setColor("#DDDDFF");

	if (this.requestDistinct && (this.optionsTexts != this.optionsKeys))
		handleError("Request distinct doesn't work when the values and options fields are different");
			
	var obj = {"AcFieldRequest": "getlist", "labels": this.optionsTexts, "table": this.optionsTable, "values": this.optionsKeys, "filters": this.filters, "distinct": this.requestDistinct,
		"requesting_page" : AcFieldGetThisPage(), "request_field" : this.uniqueId, "filters": this.filters };
	if (typeof(source) != "undefined" )
		{
		obj.requester = source.uniqueId;
		obj.requester_text = source.getText();
		obj.requester_key = source.getValue();
		}
	if (this.defaultValue > 0)
		obj['default'] = this.defaultValue;

	var data = JSON.stringify(obj);
	var url = document.location.toString(); //Current location

	if (this.overrideURL)
		url = this.overrideURL; // for cases where the default code just won't work.
		
	url = addParam(url, "_", new Date().getTime()); //prevent caching, since jquery seems to have trouble with this.
			
	if (DEBUG)
		window.open(url);
	
	var copy = this;
	this.previousValue = this.getValue();
	this.previousText = this.getText();	
	this.status = "loading";
	this.lastLoadOptions = url + "?request=" + encodeURIComponent(data);
	//this.loadJson(str, function () {} );
	this.clearDependentFields();
	$.post(url,{request:  data},function(a,b,c) {
		 	copy.setColor("");
			return copy.loadJson_callback(copy, a,b,c); 
			} );

	return url;
}

////////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.loadJson_callback = function(copy,response,isSuccess, d)
{	
	if ((response.length == 0) || ( (response.charAt(0) != "{" ) && (response.charAt(0) != "[")) )
		return handleError("Not Json in loaded Selectbox: " + response);
	
	response = JSON.parse(response);
	if (typeof(response.criticalError) != "undefined")
		return handleError("Critical Error: " + response.criticalError);
		
	copy.correspondingElement.empty();
//	var copy = this.jqElement;
	$.each(response, function(key, value)
	{
		//alert(JSON.stringify(value));
    	copy.correspondingElement.append($("<option/>").val(value.id).text(value.label));
	});
	
	copy.afterLoadOptions(); 
	
}
////////////////////////////////////////////////////////////////////////////////
/** A callback function that is internally used after the select options are loaded from the server
* default value represents a KEY.
**/

AcSelectbox.prototype.afterLoadOptions = function()
{
	if (--this.statusLoading > 0)	// don't do after-load for loads when a subsequent load request has already been issued.
		return  ;
		
	this._doAfterLoading = false;	
	this.status="ready";
	
	if (this.defaultValue)
		this.setValue(this.defaultValue, this.defaultType);
		
	else if (false) //a value is selected, perhaps it's the only choice
		{
		//except this IS necessary... so
		if (this.correspondingElement[0] === document.activeElement && ( this.correspondingElement[0].type || this.correspondingElement[0].href ))
			;//on blur will already do this. So disabled.
		else
			{
			this.updateDependentFields();
			this.updateFilteredFields();	
			}
		}
	this.defaultValue = null;
}

////////////////////////////////////////////////////////////////////////////////
AcSelectbox.prototype.lock = function()
{
	this.disabled = true; 
	this.getElement()[0].locked = true;
}
////////////////////////////////////////////////////////////////////////////////
AcSelectbox.prototype.unlock = function()
{
	this.disabled = false;
	this.getElement()[0].locked = false;
}

/////////////////////////////////////////////////////////////////////////////////
/** Returns the Value (rather than the text) of the currently selected option in the combo.

*/ 
AcSelectbox.prototype.getValue = function()  //Value in this case means key. I wouldn't use a function name that's so ambiguous except this is an overload.
{
	if (this.getElement()[0].selectedIndex == -1)
		return null;
	return this.getElement()[0].options[this.getElement()[0].selectedIndex].value ;
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what the user sees in a control.
*/
AcSelectbox.prototype.getText = function(key)
{
	if (this.getElement()[0].selectedIndex == -1)
		return null;
	return this.getElement()[0].options[this.getElement()[0].selectedIndex].text ;
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what key represents the value in this field
*/
AcSelectbox.prototype.getKey = function()
{
	return this.getValue();
}

/////////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.getValueForSave = function() 
{
	return this.getValue();
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return the value that will normally be used for updates and filters */

AcSelectbox.prototype.getUpdateKey = function()
{
	return 	this.getKey();
}
/////////////////////////////////////////////////////////////////////////////////
/** Use this to prevent multiple simultaneous calls to loadOptions.
*/ 
 AcSelectbox.prototype.ensureLoading = function(source) 
 {
	 if (this.loadingOptions != true)
	 	this.loadOptions(source);	 
 }
/////////////////////////////////////////////////////////////////////////////////
/** Selects a choice, from the dropdown list, will show up in the control.
@param key The value / key of the desired option (not the text).
*/
AcSelectbox.prototype.setValue = function(key, isKey) 
{			
		
	if (this.status == "ready" ) //Options loaded, proceed as normal
		{	/*	Determined there is no way to automatically decide if a value is a key or not. Must be specified by the user in future versions, perhaps
			on creation of the combo box (by making functions like UseKeyForSetAndGet or UseValueForSetAndGet);		*/
		this.defaultValue = null;
		
		if (key === null)
			{ //simply clearing the dropdown
			this.loadedKey = null;
			this.getElement()[0].selectedIndex = -1;	
			}
		else 
			{
			this.getElement()[0].selectedIndex = -1;
			this.loadedKey = null;
			var copy = this;
			$.each(this.getElement()[0].options, function (ind, val)
				{
				if (val.value == key)
					{
					copy.getElement()[0].selectedIndex = ind;
					copy.loadedKey = key;
					}
				});
			}
		
		this.handleDropdownChange(0, 1);
		this.previousValue = this.getValue();
		this.previousText = this.getText();	
		}
	else	//Options still populating... but load what we can anyways.
		{
		//this.ensureLoading();
		this.defaultValue = key;
		if (this.pkeyField == this.optionsKeys) //If we didn't use differentiate options... 
			this.updateDependentFields(); 
		else if (this.defaultValue)
			this.updateDependentFields(this.defaultValue);	//Yes. We CAN preload dependent fields based on our -KEY-, because combos are opposite of most controls. 
	 		
		// KEEP COMMENTED: this.updateFilteredFields(); //do not put key in here...
		// Though in theory it's nice to filter early, sometimes we filter by text, which isn't retrieved at this point...
		// also there's no easy-enough way to to tell here which filters will be text.
		}
}

/////////////////////////////////////////////////////////////////////////////
/**
@param value value does nothing, but will be passed automatically by hooks, so should be left here.
* @ASSUMES that the value and text of the field have already been updated.
*/
AcSelectbox.prototype.handleDropdownChange = function(value, denySave)
{ 
	if (this.correspondingElement != undefined)
		us = this;
		
	if (us.ajaxMode) //how the box reacts depends fundamentally on which of the two distinct modes it's using
		{		
		if ((us.getValue() == "") || (us.getValue() == null)) //this value doesn't exist in the database
			{
				us.clearDependentFields();
				us.flash("#FFBB99");
			}
		else
			{
				us.updateDependentFields();
				us.updateFilteredFields();
			}
			
		if ((this.savable > 0) && (! denySave))
			this.saveField(this.loadedKey);
		}
	else
		 AcField.prototype.handleChange.call(us, value);	

	this.previousValue = this.getValue();
	this.previousText = this.getText();	
				 
	if (us.onChange)
		us.onChange();
}

/////////////////////////////////////////////////////////////////////////////////////////
// /** Intelligent  */
 AcSelectbox.prototype.setValueFromLoad = function(key)
 {
   if (((key == null) || (key == "NULL")) && this.dontLoadNull)
  		return;
		
	this.setValue(key);		
 } 

/////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.resetValue = function()
{
	//this.setText("");
	this.loadedKey = null;
	this.getElement()[0].selectedIndex = -1;
	this.clearDependentFields();		
}

AcSelectbox.prototype.clearDependentFields = function()
{
	var x;
	for (x=0; x < this.dependentFields.length; x++)
		{
		this.dependentFields[x].resetValue();			
		}
	for (x=0; x < this.filteredFields.length; x++)
		{
		this.updateFilteredFields(null);			
		}
}

/////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.setColor = function(val)
{
	this.getElement()[0].style.backgroundColor = val;
}

/////////////////////////////////////////////////////////////////////////////

AcSelectbox.prototype.setBorder = function(val)
{
	this.getElement()[0].style.borderColor = val;
}

/////////////////////////////////////////////////////////////////////////////
/** This serves to allow the user to use two table/key pairs. One to get the default value [as most
* AcControls do] to LOAD FROM and SAVE TO and a separate one to select choices from a table [also filters]. 
* 
* @param texts Visible text value of each respective option
* @param table table used to pick options from
* @param values primary key values of each respective option
* @see Example documentation
*/
AcSelectbox.prototype.differentiateOptions = function(texts, table, values) 
{
	if (this.initialized)
		return handleError("Usually differentiate options should be called before initialization.");
	this.optionsTable = table;
	this.optionsKeys = values;
	this.optionsTexts = texts;
}


