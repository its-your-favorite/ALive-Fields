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
 * Includes json2.js
 * http://www.JSON.org/json2.js
 *
 * Last Revision: 
 * Date: Oct 11 2011 2:00PM
 */

var DEBUG; //global variable

/* Class AcField (Base class, abstract)
* @CLASS @extends?  
*/

if (typeof(handleError) == "undefined")
	{
	handleError = function(err){alert(err);};//great place for a breakpoint. Change this code as appropriate.};
	}
	
if (typeof(jQuery) == "undefined")
	handleError("This library requires jquery.");
	
/** BASE
 * @class Base class for a set of controls that extend standard HTML controls to make them ajax, powered
 and connect them to a database in a way that resembles Microsoft Access.
 * @this {AcField}
 * @param {string} field -The field in the database which this record loads from or saves to.
 * @param {string} table -The table in the database which this record loads from or saves to.
 * @param {string} pkeyField -The primary key in the table which this field loads from / saves to.
 * @param {int} paramLoadable - {0 = manual load, 1 = Autoload} Whether the field loads its value via ajax automatically.
 * @param {int} paramSavable - {-1 = Field is readonly, 0 = manual save, 1 = Autosave} Whether/how this field saves its value back to the database.
 * @param {[AcField]} dependentFields - An array of fields to populated (that is, their load function is called with this fields VALUE) whenever this field's value changes.
 *
 * @requires jQuery
 * @requires json2 (https://github.com/douglascrockford/JSON-js/blob/master/json2.js)
 * @requires alex_functions.js
 *
 * @see Word document on AcControls.
 */
 
AcField = function (id, paramLoadable, paramSavable, dependentFields)
{
 if (!arguments.length)
 	return ; // don't mark this initialized if it's called by a constructor without valid params

  if ((dependentFields != null) && (dependentFields.length == undefined))
  	{
	if (dependentFields instanceof AcField)	//use some common sense
		dependentFields = [ dependentFields ] ;
	else
	  	return handleError("Cannot initalize dependent field that is not an array ");
	}

 this.dontLoadNull = false; //can only be set by programmer manually. Prevents loading null fields.
 
 this.savable = paramSavable; 
 this.loadable = paramLoadable;
 
 this.instanceIndex = indexerFunc();
 this.dependentFields = dependentFields;
 this.initialized = 0;
 this.correspondingElement = null;
 this.disabledReqs = {};
 this.lockReqs = {};
 this.onChange = null;
 this.oldValue = "[uninitialized]";
 this.cancelLoad = false;
 this.afterLoadCustom = false;
}

AcField.prototype.constructor = AcField; // Otherwise this would have a constructor of AcField


////////////////////////////////////////////////////////
/**
* A second constructor-like function that must be called before a control can be used.
*
*  
* @param {jQuery Selector} jqElement Takes a jQuery (which should have only one matching element) to determine which HTML field this control on the page this control will be bound to.
*/
// NOTE: Any values saved here must be initialized to 0 in the constructor, to avoid potential interference.
//It seems that if a class attribute has not been initialized for an instance, it will just take that
// value from a different instance... So initialize all of them.

AcField.prototype.initialize = function(jqElementStr) 
{// waits until all constructors have been called
	var jqElement = $(jqElementStr);
	if (! jqElement instanceof jQuery)
		return alert("Initialize called with a non-jq-selector: " + jqElement);
		
   if (this.initialized)
		return alert("double initializing. Selector = " + jqElement);//see if this produces false positives
   else
   	   ; //alert("first init");
 this.initialized = true;
 
  if (jqElement[0] == undefined)
  	return handleError("Control can't be found, can't find: " + jqElement.selector);
 if (jqElement.length > 1)
 	return handleError("Control can't be initialized. Jquery selector finds multiple results " + jqElement.selector);
	
  if (jqElement[0].boundAcControl != null)
  	return handleError("Control already bound:" + jqElement.selector, jqElement[0]);
 
  this.correspondingElement = jqElement;
  jqElement[0].boundAcControl = this; //not an infinite loop since these are merely pointers.
  	//however, this may prevent them from being json Encoded. If this becomes an issue, change it
	// from a pointer to the actual element to a boolean.
	///*
	this.correspondingElement.bind("blur", this, function(myEvent)
		 	{
			if (myEvent.data.oldValue != myEvent.data.getValue()) 
	   			myEvent.data.handleChange();
		    myEvent.data.oldValue = myEvent.data.getValue();
			} );	
	
	if (this.savable < 0)
		this.lock('noSave'); //don't let the user edit the content of the field, thus make it clear the field cannot be changed & saved.
	/**/
}

////////////////////////////////////////////////////////
indexerFunc = function()
{
	if (indexerFunc.indexVal == null)
		indexerFunc.indexVal = 0;
	return indexerFunc.indexVal++;	
}

////////////////////////////////////////////////////////

AcField.prototype.resetValue=function() //function resetValue
{
 this.setValue("", true);
}

////////////////////////////////////////////////////////
/*
* @description Virtual function that is called whenever a key is pressed. 
*/
/*AcField.prototype.handleKeydown = function(value)
{  
 // if key is enter then call handleChange
}*/

////////////////////////////////////////////////////////
/**o
* @description Called whenever box is changed. 
*/
AcField.prototype.handleChange = function()
{
	if (this.savable > 0)
	  if (this.loadedKey != null)
		this.saveField(this.loadedKey);	
		
	this.updateDependentFields();
	//why not filtered fields too?
	if (this.handleChangeCallback)
		this.handleChangeCallback();
}

////////////////////////////////////////////////////////
/**
* @description Called whenever a key is pressed. 
*/
AcField.prototype.handleKeydown = function()
{
	//to be overridden by subclasses.
}

////////////////////////////////////////////////////////
/** @description This function saves the given field to the database.
* @param primaryKeyData This value is used to determine which row in table this value will be saved to.
*/
AcField.prototype.saveField = function()
{
  if (this.savable < 0)
	return handleError("Field not set to savable");
 
   var theObject = this;
   information = {"AcFieldRequest": "savefield" , "fieldInfo" : [[ this.correspondingField,   this.getValueForSave() ]] ,  "action" : "save", "requesting_page" : AcFieldGetThisPage(), "request_field" : this.uniqueId};

	url = document.location.toString();
	this.lastRequest = url; //for debugging sake	
 	this.setColor("#FFFFBB");
	
	 $.ajax({
	  url: url,
	  type: "POST",
	  data: {"request":  JSON.stringify(information)},
	  context: this,
	  error : function (a, b , c) { if (typeof(window_unloading) != "undefined") handleError(" Saving of field interrupted by leaving page."); }, 
	  success: function(data, b, c, d, e)
	  {  
	  if ((data.substr(0,1) != "[") && (data.substr(0,1) != "{"))
	  		return handleError("Not Json in lodade field: " + data);
	  
	   structure = JSON.parse(data); // Data should be Json
		if (typeof(structure.criticalError) != "undefined")
	   		{
	   	   	 if (structure.criticalError != 'expectedError')
				handleError(structure.criticalError);
	   		theObject.setColor("#FF9999");

			theObject.setValue(this.oldValue);			
			}
		else
			{
		    theObject.flash("#99FF99");
			}
	   //Need to make a "find or Need field" option... for ... hmm
	   }
	   
	  });	
 // make ajax request.
 // on Success flash box green, then call dependent fields
 // on Failure flash box red, then submit an error to log.
}


 // make ajax request.
 // on Success flash box green, then call dependent fields
 // on Failure flash box red, then submit an error to log.
////////////////////////////////////////////////////////
/**
/* @description This function loads the given field from the database.
/* @param primaryKeyData This value is used to determine which row in table this value will be loaded from.
*/
AcField.prototype.loadField = function(primaryKeyData, type, source) 
{  //if ((this.optionsValues == undefined) || (this.optionsKeys == this.pkeyField))
  if (type == null)
  	type = "dynamic";
	
  this.loadedKey = primaryKeyData;  //otherwise we're just loading a choiceText, not changing the underlying key to save to.
	
   if (primaryKeyData == null)
 	return this.resetValue(); // handleError("error, no pkey"); //error
	
  if ( this.loadable <  0 )
	return handleError("Field not set to be loadable");
	

  this.disable('loading'); //don't make unique to this load
  if (primaryKeyData == "" )// then clear the field
  	{
    this.setBorder("#777700");
  	return this.resetValue(); //this should also clear dependent fields.
	}
	
  this.setBorder("#9999DD");	
  var theObject = this; // "VAR" is CRUCIAL in this line!
  
  if (this.correspondingField == "")
  	return handleError("Cannot load a control that has no fieldname.");
	
  information = {"AcFieldRequest": "savefield" ,"primaryInfo": [this.pkeyField , primaryKeyData] , "requesting_page" : AcFieldGetThisPage(), "request_field" : this.uniqueId};

  if (type == "static")
  	information.action = "hardcoded_load";
  else
  	{
    information.action = "dynamic_load";
	information.source_field = source.uniqueId;
	}

   information = (JSON.stringify(information));
   url = document.location.toString();//"Controllers/ajax_field.php";
   this.lastRequest = url; //for	 debugging sake
	
 tmp = {
  url: url,
  type: "POST",
  data: {"request": information},
  context: this.lastRequest,
  error : function (a, b , c) { if (typeof(window_unloading) != "undefined") handleError(" field fetch failed "  ); }, 
  success: function (data, b, c) 
  		{   
		var structure={};//crucial line.
		if (this != theObject.lastRequest) //have we made a newer request for the same field? If so, discard old results.
			return ;
		 
		if (data.substr(0,1) != "{")
			{
			if (data.indexOf("Maximum execution") >= 0)
				structure = {'criticalError': "Site Operating Too slowly"};
			else
				structure = {'criticalError': "Not json in loaded field: " +  data};
			}
		else
			structure = JSON.parse (  data  ); // Data should be Json // Dubious use of eval here
			
		 if (structure.criticalError != null)
	   		{
			handleError(structure.criticalError, structure);	
	   		theObject.setColor("#FF9999");
			theObject.setValue(this.oldValue);		
			theObject.lock('bad load');
			return;
			}
		theObject.setColor("");//clear any red from previous failed attempts
		theObject.setBorder("");
		theObject.enable('loading');
  		
   	   theObject.setValueFromLoad(structure.value);
	   theObject.oldValue = structure.value;
     //Need to make a "find or Need field" option... for ... hmm
	 
	 	if (theObject.loadFieldCallback)
			theObject.loadFieldCallback(); //call handlers
			
		theObject.afterLoad(); //this exists for subclasses to overwrite
		if (theObject.afterLoadCustom) //this exists for the user of a particular page to overwrite
			theObject.afterLoadCustom();
	 	}};

 $.ajax(tmp);
}

/**
* @description Get a control's value. Will vary highly based on the type of control.
* @returns {string} The controls self-determined representation of its value.
*/
AcField.prototype.getValue=function()
{
	return this.getElement()[0].value;
}

	
/**
* @description Set a control's value. Will vary highly based on the type of control.
* @param {string} param The value this control will be set to.
*/
AcField.prototype.setValue=function(param, isClearing)
{   		
	tmp = this.handleChange;//prevent field from saving from its own load action
	this.handleChange = "";
	if (param == null)
		param = ""; //better than putting the word "null" in.
	this.getElement()[0].value = param;
	this.handleChange = tmp;
	
 		this.updateDependentFields();
		this.updateFilteredFields();	
 
}

/**
* @description Set a control's color for 400 miliseconds. Used internally to indicate fields failing/succeeding to load/save.
* @param {string} color The HTML Color code e.g. "#FFEE10"
* @param {boolean} off [Do not use] Internal use.
*/
AcField.prototype.flash=function(color, off)
{
	 if (off)
		 this.setColor("");
	 else
		{
		var aCopy = this;
		this.setColor(color);
		setTimeout(function() {
			aCopy.flash('',1);
			},400);
		}
}
//////////////////////////////////////////////////////////////////////////////////
// @description Set a control's border's color for 400 miliseconds. Used internally to indicate fields failing/succeeding to load/save.
// @param {string} color The HTML Color code e.g. "#FFEE10"
// @param {boolean} off [Do not use] Internal use.
AcField.prototype.flashBorder=function(color, off)
{
 if (off)
	 this.setBorder("");
 else
 	{
	var copyObj = this;
	this.setBorder(color);
	setTimeout(function() {
		copyObj.flashBorder('',1);
		},400);
	}
}
//////////////////////////////////////////////////////////////////////////////////
AcField.prototype.setColor=function(color)
{ //to be overloaded as necessary
	this.getElement()[0].style.backgroundColor = color;
}

//////////////////////////////////////////////////////////////////////////////////

AcField.prototype.setBorder = function(color)
{
	this.getElement()[0].style.borderColor = color;
}

//////////////////////////////////////////////////////////////////////////////////
// @description Disables a control, until enable is called with the same 'reason' parameter. Thus multiple independent disable requests will keep a control disabled until ALL of them call Enable
// @param {string} reason [optional] reason a unique identifier

AcField.prototype.disable=function(reason)
{ //to be overloaded as necessary
	if (reason == null)
		reason = 'default';
	this.getElement()[0].disabled = true;
	this.disabledReqs[reason] = true;
}

/*/////////////////////////////////////////////////////////////////////////////////
// @description Enables a control, until enable is called with the same 'reason' parameter. Thus multiple independent disable requests will keep a control disabled until ALL of them call Enable
// @param {string} reason [Optional] unique identifier. If none provided, then all locks are removed and control is unconditionally enabled.
*/
AcField.prototype.enable=function(reason)
{ //to be overloaded as necessary
	if (reason != null)
		delete this.disabledReqs[reason];
	else
		this.disabledReqs = {}; 
		
	this.getElement()[0].disabled = false;
	var x;
	for (x in this.disabledReqs) 
		this.getElement()[0].disabled = true;
}

//////////////////////////////////////////////////////////////////////////////////
AcField.prototype.lock=function(reason)
{ //to be overloaded as necessary
	if (reason == "undefined")
		reason = " "; //just a generic lock
	this.getElement()[0].readOnly = true;
	this.lockReqs[reason] = true;
}

////////////////////////////////////////////////////////////////////////////////////
// THis function exists to be overloaded in subclasses that return different values from .getValue // than how they are stored in the database (like certain timestamps)
AcField.prototype.unlock=function(reason)
{
	if (reason != null)
		delete this.lockReqs[reason] ;
	else
		this.lockReqs = {}; 
		
	this.getElement()[0].readOnly = false;
	var x;
	for (x in this.lockReqs)
		this.getElement()[0].readOnly = true;		
}

////////////////////////////////////////////////////////////////////////////////////
/** This function exists to be overloaded in subclasses that take different values from the
 than how they are taken from setValue (i.e. they format it diff for the DB)
 */
AcField.prototype.setValueFromLoad=function(value)
{
  if (((value == null) || (value == "NULL")) && this.dontLoadNull)
  		return;
		
  this.setValue(value);	
}


////////////////////////////////////////////////////////////////////////////////////
/** This function exists to be overloaded in subclasses that take different values from the
* than how they are taken from setValue (i.e. they format it diff for the DB)
*/
AcField.prototype.getValueForSave=function()
{
  return this.getValue();	
}

////////////////////////////////////////////////////////////////////////////////////
/** I do not believe this function serves any purpose. Simply serves as an accessor.
*/
AcField.prototype.getElement = function() //this functions serves to be overloaded.
{
	return  this.correspondingElement;
}

/////////////////////////////////////////////////////////////////////////////
/** This function will tell all DependentFields to load themselves from the database. This function is automatically called whenever this field changes.
*/
AcField.prototype.updateDependentFields = function(key)
{
		
	if (this.dependentFields == undefined)
		return;
		
	if (key==null)
		key=this.getUpdateKey();
//Pretty sure this is right, on principal. Don't use this.key unless it's a DD. But even DDs give the value (not text) with the getValue, no?
// This is worthy of some discussion. In principal it's pretty dumb to update with the same key you just got (why not have the this.updater change this.updatee directly?) UNLESS it's a Dropdown in which case the updator is the user.
// Still, potentially confusing.
		
	// sending a null key is now used to clear the fields...
	var x; //god wasted an hour learning that Var is critical... once again.
	for (x=0; x < this.dependentFields.length; x++)
		{
		if ((this.dependentFields[x] instanceof AcField) || ((typeof(AcControlSet) != "undefined" ) && (this.dependentFields[x] instanceof AcControlSet)))
			this.dependentFields[x].loadField(key, "dynamic", this);			
		else
			return handleError("Tried to update invalid dependent field [ " + x + " ] from " + this.correspondingElement[0].id ); 
		}
}
/////////////////////////////////////////////////////////////////////////////////
/** Is not used. May be removed. !
*/
AcField.prototype.getFilterKey = function()  //this serves to manually overwritten (not overloaded) on a per-script basis
{
	return alert("I thought this function wasn't used anymore.");
	//can we do this intelligently / dynamically, by figuring out which field within the filter
	//thing that we are actually giving, and giving the right value? - Yes... Or 
	return this.getValueForSave();
}

/////////////////////////////////////////////////////////////////////////////////
/** A function used to change the fields which this object filters.
*/
AcField.prototype.setFilteredFields = function(x) 
{
	this.filteredFields = x;
}

/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what the user sees in a control.
    By definition, this should ALSO return the value used for "TEXT"-type filtering.
*/
AcField.prototype.getText = function(key)
{
	return 	this.getValue();
}

/////////////////////////////////////////////////////////////////////////////
/** 
*/
AcField.prototype.afterLoad = function()
{
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what key represents the value in this field. 
    By definition, this should ALSO return the value used for "VALUE"-type filtering.
*/
AcField.prototype.getKey = function()
{
	return 	this.loadedKey;
}

/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return the value that will be used for updates. 
*/
AcField.prototype.getUpdateKey = function()
{
	return 	this.getValue();
}


/////////////////////////////////////////////////////////////////////////////
/** Similar to update dependent fields, but operates on filtered fields. Called whenever this Control changes value.
*/
AcField.prototype.updateFilteredFields = function(key)
 { //this function is called by the combobox that was just changed, it finds out the controls its filters refers to, sets their .filters value, then has them refresh.
	var myIdentifier = "zerg" + new Date(); 
	var field = null;
	
 	if (this.filteredFields == null) // validity checks
		return;
	if (this.filteredFields.length < 1)  // validity checks
		return;
	if (this.correspondingElement)  // Allow filtering from a dummy field (i.e. that was never initialized)
		myIdentifier = this.correspondingElement[0].id;

	var x;
	for (x=0; x < this.filteredFields.length; x++) // we want to be able to set filters from different places		
		{											// independently 
		if (this.filteredFields[x] == undefined)
			return handleError("Control to be filtered didn't resolve (declared too late?): " + myIdentifier);
		if (this.filteredFields[x].filters == undefined)
			return handleError("Field not filterable: " + this.filteredFields[x][0].correspondingElement[0]);
			
		this.filteredFields[x].filters[ myIdentifier ] = [];
		}
		
	for (x=0; x < this.filteredFields.length; x++)
		{
		if (this.getValue() != null)
			this.filteredFields[x].filters[myIdentifier ].push(this.uniqueId); // ? same field filtering twice?;
		//alert("Applying filter to " + this.filteredFields[x][0].correspondingElement.selector + " from " + myIdentifier +
		//		"\n Filtering results to " + field + " = " + key);
		if ( typeof( this.filteredFields[x] ) == "undefined")
			return handleError ("cannot filter fields that do not have loadOptions");
		this.filteredFields[x].loadOptions(this); //refresh
		}
	
 }
 
////////////////////////////////////////////////////////////////////////////////////
