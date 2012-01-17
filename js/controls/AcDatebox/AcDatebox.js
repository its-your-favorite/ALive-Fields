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

// To do:
// lockable

// includes

if (typeof(handleError) == "undefined")
    handleError = alert;
    
if (typeof(AcTextbox) == "undefined")
    handleError("Must include AcTextbox before AcDatebox.");
    
if (typeof(jQuery.ui) == "undefined")
    handleError("Must include JqueryUI before AcDatebox.");
    
//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

/**
* @class AcDatebox A basic control for loading/saving fields that can be represented as dates. For date-times, see AcDateTimeSet. Is initialized with an &lt;input&gt; tag. 
* @extends AcField  
* @requires AcControls.js and its dependencies.
* @requires jQueryUI 
* @requires jQueryUI datepicker
* @see AcDateTimeSet
*/
if (typeof(strToDate) == "undefined")
{
    strToDate = function (str)
        {
         if (str instanceof Date)
            return str;
        if (str == null)
            return new Date();
            
         str = (str.toLowerCase().replace("pm", " pm").replace("am", " am")); //fix for chrome    
        
         if (str.indexOf('-') == 4) //year-first format
            {
                str += ' ';//necessary to prevent undefined in case of date with no time.
                pieces = str.split("-");
                halves = pieces[2].split(" ");
                return new Date(pieces[1] + "-" + halves[0] + "-" + pieces[0] + " " + halves[1]); 
            }
         else
            return new Date(str);
        }
}

AcDatebox = function (field,table,pkey,loadable,savable,dependentFields)
{
 AcTextbox.call(this, field,table,pkey,loadable,savable,dependentFields); //call parent constructor
}

AcDatebox.prototype = new AcTextbox();  // Here's where the inheritance occurs
AcDatebox.prototype.constructor=AcDatebox; 
AcDatebox.prototype.parent = AcTextbox.prototype;


AcDatebox.prototype.initialize = function(jqElementStr) //paramater only exists to be passed on
{
  AcTextbox.prototype.initialize.call (this,  jqElementStr); //call parent constructor 
  $(jqElementStr).addClass("acDate"); //enables jquery ui date-picker to theme this

  this.getElement().keydown(this, function (param) { return param.data.handleKeydown(param);} ) ; 
 //blur already bound through AcControl
 this.correspondingElement.unbind("blur");
 
  this.correspondingElement.bind("change", this, function(myEvent)
             {
            if (myEvent.data.oldValue != myEvent.data.getValue()) 
                   myEvent.data.handleChange();
            myEvent.data.oldValue = myEvent.data.getValue();
            } );    
  //call the parent constructor first... it's okay that it will be called twice
  $(jqElementStr).datepicker();
//  nada.datepicker("hide");
  //this.setValue(new Date());    
}

/////////////////////////////////////////////////////////////////////////////////////

AcDatebox.prototype.validate = function()
{
    this.setValue(this.getValue()); //this actually does validate the field.
}

/////////////////////////////////////////////////////////////////////////////////////

AcDatebox.prototype.resetValue = function ()
{
    this.correspondingElement.val("");
}


////////////////////////////////////////////////////////////////////////////////////

AcDatebox.prototype.setValue = function (param)
{
    $(this.correspondingElement.selector).datepicker("setDate", strToDate(param)); 
    return;
    if (typeof param == "string")
        param = strToDate(param);    
    else if (param instanceof Date)
        ;
    else if (! param)
        param = new Date();
    else
        handleError("Tried to set datebox to unknown value: " + param);
        
//    if (param.getYear() < 1950)
    //    param.setYear(param.getYear() + 100); //translate dates ending with /05 from 1905 to 2005

    result = param.getYear() % 100;
    if (result.length < 2)
        result = "0" + result;
    result = param.getDate() + "/" + result;    
    if (result.length < 5)
        result = "0" + result;
    result = (param.getMonth()+1) + "/" + result;
    if (result.length < 8)
        result = "0" + result;

    this.correspondingElement[0].value = result;
}


AcDatebox.prototype.getValueForSave = function()
{
    var my_value = strToDate(this.getValue());
    if (! my_value )
        return null;
    else
        return my_value.getFullYear() + "-" + (my_value.getMonth()+1) + "-" + my_value.getDate();
}

AcDatebox.prototype.getValue = function()
{
    if (this.getElement()[0].value == "")
        return null;
    else
        return this.getElement()[0].value;
    
}
//////////////////////////////////////////////////////////////////////////////

AcDatebox.prototype.lock = function ()
{
    this.correspondingElement.datepicker( "disable" );
    this.correspondingElement[0].readOnly = true;
}

AcDatebox.prototype.unlock = function ()
{
    this.correspondingElement.datepicker( "enable" );
    this.correspondingElement[0].readOnly = false;
}