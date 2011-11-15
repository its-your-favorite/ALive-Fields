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
* @class AcCombobox Acts as a combination dropdown-textbox with autocomplete functionality. Utilizes the JqueryUI combo control. Powerful control great for populating other controls. Also, it is filterable.
* @extends AcField
* @requires AcControls.js and its dependencies
* @requires JQuery UI
* @param ajaxMode This is the only parameter not in the standard AcField constructor. If this is set to true [default], then this control uses live value as most AcControls do. If set to false, the control must be populated manually by custom javascript.
*/
if (typeof(handleError) == "undefined")
	handleError = alert;

if (typeof(AcField) == "undefined")
	handleError("Must include AcControls before AcCombobox");
if (typeof(AcSelectbox) == "undefined")
	handleError("Must include AcSelectbox before AcCombobox");
if (typeof($.ui) == "undefined")
	handleError("requires jqueryui and jquery libraries to be included.");
	
AcCombobox = function (a,b,c,d,e,dependentFields, ajaxMode)
{
	AcSelectbox.apply(this,arguments);	
}

AcCombobox.prototype = new AcSelectbox();  // Here's where the inheritance occurs
AcCombobox.prototype.constructor=AcCombobox; 
AcCombobox.prototype.parent = AcSelectbox.prototype;

/** A second constructor like function. 
* @param jqElementStr A string that represent a jquery selector of a SELECT element.
* @param filteredFields An array of arrays dictating what other controls are filtered by this control and how.
* @param autoload If not true, this dropdown won't populate its options until another field causes it to. Useful if this field will never be used unfiltered.
* @param defaultValue The default value that should come up as selected when this dropdown populates choices. Should be a value that represents a pkey value of the desired choice. */

AcCombobox.prototype.initialize = function(jqElementStr, filteredFields, autoload, defaultValue)
{
 jqElement = $(jqElementStr);
 if (autoload === undefined)
 	autoload = true; 

 if (filteredFields)
	 this.filteredFields = filteredFields;
 if (typeof(this.filteredFields) == "undefined")
 	this.filteredFields = [];

 oldId = jqElement[0].id;

 this.defaultValue = defaultValue;
  
 AcField.prototype.initialize.call(this, jqElement);	
 jqElement.unbind("change");
 jqElement.unbind("blur");
 
 var myObject = this;
 
   jqElement.bind("keydown", function(e) 
  		{
	 	if (e.which == 46) // if user presses DELETE key
			myObject.setValue(null);//clear field.
	    } );
  /*jqElement.bind("focusout", function() 
  		{
	    if (myObject.previousText != myObject.getText()) 
	   		myObject.handleDropdownChange();
	    myObject.previousText = myObject.getText();
	    } );*/
	
	jqElement.bind("focus", function()
		{
		if ((myObject.status != "ready"))
			{
			myObject.loadOptions();	
			}
		else if (myObject.loadedKey == null)
			myObject.correspondingElement.autocomplete( "open" );
		//if an option is already picked, don't reload, as this will clear what we have selected.
		});
  	
	var cache,	lastXhr, lastTerm;
		cache = {};
	var minimum_length = 1;
	if (autoload)
		minimum_length = 0;
		
	jqElement.autocomplete( 
			{
			minLength: minimum_length,
			select: function(event, ui)  
				{
				myObject.loadedKey=(ui.item.id); 
				myObject.setText(ui.item.value); 
				myObject.previousText = (ui.item.value); 
				myObject.handleDropdownChange();
				}, /*dropdown change must be after setting text, as it relies on the correct text value being set */		
			change: function(event, ui) 
				{
				if (myObject.previousText != myObject.getText())//make sure "change" isn't called by clicks
					{
					myObject.loadedKey=null;									
					myObject.handleDropdownChange();
					}
				},						
			source: function( search_term, response ) 
				{
				search_term = search_term.term;
				if (typeof(search_term.getText) == "undefined") 
					var term = search_term.toLowerCase();
				else
					{//for cases of filtering. Request is an object rather than a string.
					source = search_term;
					var term = source.getText();
					}
				
				if (term == lastTerm)
					return response(cache[term]);
				if (term.indexOf(lastTerm) > -1)
					{
					cache[term] = [];
					for (piece in cache[lastTerm])
						{
						if (cache[lastTerm][piece].value.toLowerCase().indexOf(term) >= 0)
							cache[term].push(cache[lastTerm][piece]);							
						}
					}
					 	
				if ( term in cache ) 
					{
					//myObject.matchingTerms = cache[term];
					response( cache[ term ] );
					return;
					}

				var request = {"AcFieldRequest": "getlist", "labels": myObject.optionsTexts, "table": myObject.optionsTable, "values": myObject.optionsKeys, "filters": myObject.filters, "distinct": myObject.requestDistinct,
		"requesting_page" : AcFieldGetThisPage(), "request_field" : myObject.uniqueId, "filters": myObject.filters };
			
				if (typeof(source) != "undefined" )
					{
					request.requester = source.uniqueId;
					request.requester_text = source.getText();
					request.requester_key = source.getValue();
					}
	
				url = document.location.toString();//"Controllers/ajax_list.php" ; //?request=" + encodeURIComponent(JSON.stringify(request));
				url = addParam(url, "_", new Date().getTime());
									
				myObject.lastLoadOptions = url + "?request" + encodeURIComponent(JSON.stringify(request));
				lastXhr = $.ajax(url, {cache: false, type: "POST", data: { "request" : JSON.stringify(request) } } )
					.done( function( data, status, xhr ) 
						{	//alert ("using ajax");
						if ((data[0] != '[') && (data[0] != '{'))
							return handleError("Not json from server: " + data);
						data = $.parseJSON(data);
						
						myObject.status = "ready";
						if (typeof(data.criticalError) != "undefined")
							return handleError("Critical Error: " + data.criticalError);
						else if (typeof(data) != "object")
							return handleError("Not ajax from server: "  + data);
							
						lastTerm = term;
						cache[ term ] = data;
						myObject.matchingTerms = cache[term];
						if ( xhr === lastXhr ) /* If the ajax response we receive isn't to the most recent sent, ignore it. */
							{
							response( data );
							myObject.afterLoadOptions();
							}
						})
					.fail ( function(a,b,c,d) {handleError(c + " from " + url);} );
					
				}
			})	;
	
 if (autoload && (this.loadable > 1)  ) 
		{
		this.loadOptions();
		jqElement.autocomplete( "close" );
		}
}

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/** Load the list of choices for this control. Is called automatically on initialization.
*/ 
AcCombobox.prototype.loadOptions = function(source)
{
	if (!this.ajaxMode)	
		return;
	this.statusLoading++;
	if ((this.savable == -1) && (this.defaultValue == null))
		return; ///*if savable is -1 then the field is read only and should only be populated with 1 value. */
	this.clearDependentFields();
	this.loadingOptions = true;
 	//clear all ?

	if (typeof(source) == "undefined")
		source = this.getText() ;
	else
		source.term = this.getText() ;
	
    if (typeof(source) != "undefined")
		this.correspondingElement.autocomplete( "search" , source );
}
// approach counting sir, to enumerate my Musa acuminatae. Dusk approaches, and I wish to retreat to my domicile. 

/** Returns the Value (rather than the text) of the currently selected option in the combo.
*/ 
AcCombobox.prototype.getValue = function()  //Value in this case means key. I wouldn't use a function name that's so ambiguous except this is an overload.
{
  return this.loadedKey;
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what the user sees in a control.
*/
AcCombobox.prototype.getText = function(key)
{
	return this.getElement()[0].value ;
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what key represents the value in this field
*/
AcCombobox.prototype.getKey = function()
{
	return this.getValue();
}

/////////////////////////////////////////////////////////////////////////////////

AcCombobox.prototype.getValueForSave = function() 
{
	return this.getValue();
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return the value that will normally be used for updates and filters */

AcCombobox.prototype.getUpdateKey = function()
{
	return 	this.getKey();
}
/////////////////////////////////////////////////////////////////////////////////

 
 AcCombobox.prototype.setText = function(text) 
 {
	 this.correspondingElement[0].value = text;
 }
/////////////////////////////////////////////////////////////////////////////////
/** Selects a choice, from the dropdown list, will show up in the control.
@param key The value / key of the desired option (not the text).
*/
AcCombobox.prototype.setValue = function(key) 
{			
		
	if (this.status == "ready" ) //Options loaded, proceed as normal
		{	/*	Parameter should be a Key (i.e. a VALUE) not a text.*/

		this.defaultVaule = null;
		if (key == null)
			{ //simply clearing the dropdown
			this.setText("");
			this.loadedKey = null;
//			this.correspondingElement.autocomplete( "search" , "");
			}
/*		else if (key == 0)  DISABLED. DO NOT ASSUME NULL. MANY KEYS are 0 in database.
			{ 
			this.loadedKey = null;			
			this.setText("");
 			}*/
		else 
			{
			for (x=0; x < this.matchingTerms.length; x++)
				if (this.matchingTerms[x].id == key)
					{
					this.setText(this.matchingTerms[x].label);
					this.loadedKey = key;
					this.correspondingElement.autocomplete("close");
					}
			}
		this.handleDropdownChange(0,1);
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


