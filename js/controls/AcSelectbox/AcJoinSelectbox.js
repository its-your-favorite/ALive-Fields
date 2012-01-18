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
    
if (typeof(AcSelectbox) == "undefined")
    handleError("Must include AcSelectbox before AcJoinSelectbox");
        
AcJoinSelectbox = function (a,b,c,d,e,dependentFields, ajaxMode)
{
    AcSelectbox.call(this, a,b,c,d,e,dependentFields); //call parent constructor. Must be done this way-ish
    this.controlType = "AcJoinSelect";
}


AcJoinSelectbox.prototype = new AcSelectbox();  // Here's where the inheritance occurs
AcJoinSelectbox.prototype.constructor=AcJoinSelectbox; 
AcJoinSelectbox.prototype.parent = AcSelectbox.prototype;

////////////////////////////////////////////////////////////////////////////////

AcJoinSelectbox.prototype.loadField = function(primaryKeyData, type, source) 
{
    this.ensureLoading(source);
    AcField.prototype.loadField.call(this, primaryKeyData, type, source);

}

///////////////////////////////////////////////////////////////////////////////

AcJoinSelectbox.prototype.initialize = function(jqElementStr, filteredFields, autoload, defaultValue)
{
    this.parent.initialize.call(this,    jqElementStr, filteredFields, autoload, defaultValue);
    jqElement.unbind("change"); // Since multiple clicks (which each would trigger a change) are required to update a select multiple...
    jqElement.unbind("blur"); // it is bound, inappropriately, by AcField
   
    if (this.getElement()[0].locked) 
        this.lock();  //we need to refresh this since change handlers are unbound (which are used in the lock mechanism)
        
    myObject = this;
      
    jqElement.bind("blur" , function() 
    {
        if ($(myObject.oldValue).not(myObject.getValue()).get().length == 0 && 
                $(myObject.getValue()).not(myObject.oldValue).get().length == 0) 
            return ; //arrays are identical

        myObject.handleDropdownChange();    

        myObject.oldValue = myObject.getValue();
        myObject.loadedKey = myObject.getKey();    
    } );
        
    if (!$(jqElementStr)[0].multiple)
        return handleError("A Join Select control must be bound to a select *multiple* input element.");
}
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/** Load the list of choices for this control. Is called automatically on initialization.
*/ 
/* No changes... 
AcSelectbox.prototype.loadOptions = function(source)
{
    if (!this.ajaxMode)    
        return;
    
    this.statusLoading++;
    if ((this.savable == -1) && (this.defaultValue == null))
        return; // if savable is -1 then the field is read only and should only be populated with 1 value. 
    
    this.loadingOptions = true;
     //clear all ?

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
    $.post(url,{request:  data},function(a,b,c) {return copy.loadJson_callback(copy, a,b,c); } );

    return url;
}*/

////////////////////////////////////////////////////////////////////////////////

AcJoinSelectbox.prototype.loadJson_callback = function(copy,response,isSuccess, d)
{    
    if ((response.length == 0) || ( (response.charAt(0) != "{" ) && (response.charAt(0) != "[")) )
        return handleError("Not Json in loaded Selectbox: " + response);
    
    response = JSON.parse(response);
    if (typeof(response.criticalError) != "undefined")
        return handleError("Critical Error: " + response.criticalError);
        
    copy.correspondingElement.empty();

    $.each(response, function(key, value)
    {
        copy.correspondingElement.append($("<option />").val(value.value).text(value.label).attr("selected", (value.isset?'selected':null) ) );
    });
    
    copy.afterLoadOptions(); 
    this.oldValue = this.getValue();
}
////////////////////////////////////////////////////////////////////////////////
/** A callback function that is internally used after the select options are loaded from the server
* default value represents a KEY.
**/
/*
AcJoinSelectbox.prototype.afterLoadOptions = function()
{
    if (--this.statusLoading > 0)    // don't do after-load for loads when a subsequent load request has already been issued.
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
*/
////////////////////////////////////////////////////////////////////////////////
AcJoinSelectbox.prototype.lock = function()
{
    // simulate readonly
    this.getElement().change( function(ev)
    {
        myObject.setValue( myObject.oldValue, true);
    });

    this.getElement()[0].locked = true;
}
////////////////////////////////////////////////////////////////////////////////
AcJoinSelectbox.prototype.unlock = function()
{
    // simulate readonly
    jqElement.unbind("change");
    this.getElement()[0].locked = false;
}
////////////////////////////////////////////////////////////////////////////////
AcJoinSelectbox.prototype.getValueForSave = function()  
{
    return JSON.stringify(this.getValue());    
}

/////////////////////////////////////////////////////////////////////////////////
/** Returns the Value (rather than the text) of the currently selected option in the combo.

*/ 
AcJoinSelectbox.prototype.getValue = function()  //Value in this case means key. 
{
    result = [];
    $.each(    this.getElement()[0].options, 
        function (key, option)
        {
            if (option.selected)
                result.push(option.value);                      
        } );
    return (result);    
}
/////////////////////////////////////////////////////////////////////////////
/** By definition, should always return what the user sees in a control.
*/
AcJoinSelectbox.prototype.getText = function(key)
{
    result = [];
    $.each(    this.getElement()[0].options, 
        function (key, option)
        {
            if (option.selected)
                result.push(option.text);                      
        } );
    return (result);    
}

/////////////////////////////////////////////////////////////////////////////////
/** Selects a choice, from the dropdown list, will show up in the control.
@param key The value / key of the desired option (not the text).
*/
AcJoinSelectbox.prototype.setValue = function(key, isKey) 
{            
    if (key.constructor.toString().indexOf('Array') == -1) //not an array
        return handleError("Setting value on an AcJoinSelectbox requires an array.");
        
    if ( this.getElement()[0].options.length == 0)
        return;
        
    if (isKey && (key.length > 0) && (typeof(key[0]) != typeof(this.getElement()[0].options[0].value)) )
        return handleError("Set value on AcJoinSelectbox requires key array to be same type as values (string) to function.");

    if ((!isKey) && (key.length > 0) && (typeof(key[0]) != typeof(this.getElement()[0].options[0].text)) )
        return handleError("Set value on AcJoinSelectbox requires key array to be same type as values (string) to function.");
    
    $.each(    this.getElement()[0].options, 
        function (loop_key, option)
        {
            if (isKey)
                option.selected = ($.inArray(option.value, key) >= 0);
            else
                option.selected = ($.inArray(option.text, key) >= 0);
        //             result.push(option.value);                      
        } );
    return;    

    // Leaving this code here, despite it not being used right now, in case upon reflection it has necessary considerations for future complications [inherited from parent].


    if (this.status == "ready" ) //Options loaded, proceed as normal
    {    /*    Determined there is no way to automatically decide if a value is a key or not. Must be specified by the user in future versions, perhaps
            on creation of the combo box (by making functions like UseKeyForSetAndGet or UseValueForSetAndGet);        */
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
    else    //Options still populating... but load what we can anyways.
    {
        //this.ensureLoading();
        this.defaultValue = key;
        if (this.pkeyField == this.optionsKeys) //If we didn't use differentiate options... 
            this.updateDependentFields(); 
        else if (this.defaultValue)
            this.updateDependentFields(this.defaultValue);    //Yes. We CAN preload dependent fields based on our -KEY-, because combos are opposite of most controls. 
             
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
AcJoinSelectbox.prototype.handleDropdownChange = function(value, denySave)
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
AcJoinSelectbox.prototype.setValueFromLoad = function(key)
{
    if (((key == null) || (key == "NULL")) && this.dontLoadNull)
        return;
        
    this.setValue(key);        
} 

/////////////////////////////////////////////////////////////////////////////

AcJoinSelectbox.prototype.resetValue = function()
{
    //this.setText("");
    this.loadedKey = null;
    this.getElement()[0].selectedIndex = -1;
    this.clearDependentFields();        
}
