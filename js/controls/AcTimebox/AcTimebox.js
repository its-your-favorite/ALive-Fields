/*!
 * ALive Fields V 1.0
 * http://alexrohde.com/
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 *
 * Last Revision: 
 * Date: Oct 11 2011 2:00PM
 *
 * Includes jquery.js, jqueryUI.js
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 */

/* 
* Javascript set of classes to extend standard HTML controls to make them ajax, powered
* and connect them to a database in a way that mirrors access.
* Requires jquery, Requires json2 (https://github.com/douglascrockford/JSON-js/blob/master/json2.js)
*/

// To do:
// Make a way to handle seconds? Maybe?



//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

/**
* A control that is used to pick a time of day (hours: minutes). It is represented by a &lt;input&gt; tag, and filters input to valid times only.
* @class AcTimebox 
* @extends AcField
* @see AcDatebox
* @see AcDateTimeSet
*/
if (typeof(handleError) == "undefined")
	handleError = alert;
	
if (typeof(AcField) == "undefined")
	handleError("Must include AcControls before AcTimebox");

AcTimebox = function ( field,table,pkey,loadable,savable,dependentFields)
{
 AcTextbox.call(this,  field,table,pkey,loadable,savable,dependentFields); //call parent constructor
}

AcTimebox.prototype = new AcTextbox();  // Here's where the inheritance occurs
AcTimebox.prototype.constructor=AcTimebox; 
AcTimebox.prototype.parent = AcTextbox.prototype;


AcTimebox.prototype.initialize = function(nada) //paramater only exists to be passed on
{
  nada = $(nada);
  this.correspondingElement = [];
  AcTextbox.prototype.initialize.apply (this,  arguments); //call parent constructor 
  nada.addClass("acTime"); //to allow custom styling through style sheets
  this.getElement().keydown(this, function (param) { return param.data.handleKeydown(param);} ) ; 
  this.getElement().blur(this, function (param) {  return param.data.validate();   } ) ; 
  //call the parent constructor first... it's okay that it will be called twice
  this.setValue(0);	
}

AcTimebox.prototype.validate = function()
{
	this.setValue(this.getValue()); //this actually does validate the field.
}
///////////////////////////////////////////////////////////////////////////////////////////////
AcTimebox.prototype.correctCursor = function(force)
{
	var theEle = this.getElement()[0];
	var cursorPos = getCursorLocation(theEle);				
	var cursorEnd = getSelectionStop(theEle);
	
	if ((cursorEnd == cursorPos) || (force))
		{
		setCursorLocation(theEle,cursorPos - cursorPos % 3, 2)	
		}
}
///////////////////////////////////////////////////////////////////////////////////////////////
AcTimebox.prototype.handleKeydown = function(param)
{
	var theEle = this.getElement()[0];
	
	if (theEle.readOnly || 	theEle.disabled)
		return ;
		
	if (param == null)
		param = window.event; //browser compatibility

	cursorPos = getCursorLocation(theEle);				
	cursorEnd = getSelectionStop(theEle);
	
			if ((param.keyCode >= 96) && (param.keyCode <= 105))
				param.keyCode -= 48; // translate numpad numerals to normal numbers
			
			if (param.keyCode == 9) //tab
				return true;	
			if (param.keyCode == 46) //delete
				{
				this.setValue(0);
				setCursorLocation(theEle, 0, 2);
				}
			else if ((param.keyCode == 39) || (param.keyCode == 13)	) // right arrow or ENTER (for numpad)
				{
				if (cursorPos < 3)
					setCursorLocation(theEle, 3, 2);
				else 
					setCursorLocation(theEle, 6, 2);
				}
			else if ((param.keyCode == 109) || (param.keyCode == 40	)) // down arrow or -
				{
				if (cursorPos < 3)
					this.setValue(this.getValue() - 60);
				else if (cursorPos < 6)
					this.setValue(this.getValue() - 1);
				else
					this.setValue(this.getValue() - 60*12);
				setCursorLocation(theEle, cursorPos);	
				this.correctCursor(true);			
				}
			else if (param.keyCode == 36	) // Home
				{
				setCursorLocation(theEle, 0,2);	
				}
			else if (param.keyCode == 37	) // left arrow 
				{				
				if (cursorPos <= 3)
					setCursorLocation(theEle, 0, 2);
				else if (cursorPos <= 6)
					setCursorLocation(theEle, 3, 2); 
				else
					setCursorLocation(theEle, 6, 2);
				}
			else if ((param.keyCode == 65) )
				{
					this.setValue(this.getValue() % (60 * 12)); // AM
				}
			else if ((param.keyCode == 80) )
				{
					this.setValue(this.getValue() % (60 * 12) + 60 * 12); // pm
				}
			else if ((param.keyCode == 107) || (param.keyCode == 38	)) // up arrow  or +
				{
				if (cursorPos < 3)
					this.setValue(this.getValue() + 60);
				else if (cursorPos < 6)
					this.setValue(this.getValue() + 1);
				else
					this.setValue(this.getValue() + 60*12);
				setCursorLocation(theEle, cursorPos);						
				this.correctCursor(true);
				}
			else if ((param.keyCode >= 48) && (param.keyCode <= 57)) // any number key
				{
 				  if (cursorEnd == cursorPos)	//no current selection
					{				
					if (cursorPos % 3 != 2)
						setCursorLocation(theEle, cursorPos+1,2);
						//if in the right spot, auto-select 2 following numbers and continue				
					}
				if (cursorPos >= 6)
					return false;
				if (cursorPos%3 == 1)
					{
					setCursorLocation(theEle, cursorPos, 1); //overwrite 1 char
					return true;
					}
				else if (cursorPos%3== 0)
					{
					setCursorLocation(theEle, cursorPos + 2);
					insertAtCursor(theEle, ' ');
					setCursorLocation(theEle, cursorPos, 2);
					return true;
					}
				else
					{
					if (cursorPos == 2)
						{
						setCursorLocation(theEle, cursorPos+1, 1);//move to next box
						return true; //allow overwriting minutes, not AM/PM
						}
					setCursorLocation(theEle, cursorPos+1, 2);//move to next box
					return false;	
					}
				//setCursorLocation(theEle, cursorPos-cursorPos%3,2);		
					
				}
			else
				return false;	
			return false;
}

////////////////////////////////////////////////////////////////////////////////
AcTimebox.prototype.getValue = function ()
{
	var value = this.getElement()[0].value;
	
	if ((value.length < 7))
		value = "0" + value; //add trailing 0 when necessary to make fixed length
		
	if (value.length != 8)
		return alert("error");// error
	
	var hours = parseFloat(value.substr(0,2)) % 24;
	if (hours == 12)
		hours = 0;
	var minutes = parseFloat(value.substr(3,2));

	if ((hours < 12)) //allow the user to type in 03 00 to indicate 3 PM. a bit confusing though potentially.
		if (value.substr(6) == "PM")
			hours += 12;
		
	return hours*60+minutes;
}


/////////////////////////////////////////////////////////////////////////////////////
AcTimebox.prototype.setValue = function (param)
{
	if (isNaN(param))
		param = 0;
	param %= 60 * 24; //loop around if > 1 day
	if (param < 0)
		param += 60 * 24;
	
	var hours = parseInt(param / 60); 	
	var minutes = parseInt(param % 60); 
	
	if (minutes < 10)
		minutes = "0" + minutes; //insert leading 0
		
	var suffix = "AM";
	
	if (hours >= 12) //detect if PM
		{
		hours -= 12;
		suffix = "PM";	
		}
	
	if (hours == 0)
		hours = 12; //the zeroth hour is 12:00, AM or PM
		
	if (hours < 10)
		hours = " " + hours; 
		
	this.getElement()[0].value = hours + ":" + minutes + " " + suffix;
}


///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////

AcTimebox.prototype.getValueForSave = function()
{ // form HH:MM[:SS.iiii]
  var value = this.getValue();
  var hours = parseInt(value / 60) + "";
  if (hours.length == 1)
  	hours = "0" + hours;
	
  minutes = parseInt(value % 60) + "";
  if (minutes.length == 1)
  	minutes = "0" + minutes;
	
  rest = ""; //:00.0000";
  
  return hours + ":" + minutes + rest;
}

///////////////////////////////////////////////////////////////////////////////////

AcTimebox.prototype.setValueFromLoad = function(value)
{ // form HH:MM[:SS.iiii]
if (value == null)
	return this.setValue(0);
	
  if (value instanceof Date) //handle DateTime
  	value = value.toTimeString().substr(0,5); // convert dateTime to Time
  else if (value == parseInt(value)) 
  	return this.setValue(value); //handle timestamp 
  else if (value.indexOf(" ") >= 0)
  	value = (strToDate(value)).toTimeString().substr(0,5);

 //Handle string
	
//Should tidy this up at some point, right now would accept .7:.9 as a time.
  this.setValue(parseFloat(value.substr(0,2)) * 60 + parseFloat(value.substr(3,2)) ); 
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////
function insertAtCursor(CurrentTextBox, value)
{
	x = getCursorLocation(CurrentTextBox);	
	CurrentTextBox.value = CurrentTextBox.value.substr(0,x) + value + CurrentTextBox.value.substr(x);
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////
function getCursorLocation(CurrentTextBox)
        {
  		 if (!document.selection)
		 	return CurrentTextBox.selectionStart;
 
 		var r = document.selection.createRange();   
 	    if (r == null) {        return 0;      }  
			   
          var re = CurrentTextBox.createTextRange(),    rc = re.duplicate();
 	         re.moveToBookmark(r.getBookmark()); 
				      rc.setEndPoint('EndToStart', re);  
		     return rc.text.length;  
		}
//////////////////////////////////////////////////////////////////////////////////////////////////////////
function getSelectionStop(CurrentTextBox)
        {
		 if (document.selection)
		 	{
			var r = document.selection.createRange();   
			   if (r == null) {        return 0;      }  
			   
           var re = CurrentTextBox.createTextRange(),    rc = re.duplicate();
		         re.moveToBookmark(r.getBookmark()); 
				      re.setEndPoint('StartToStart', rc);  
		     return re.text.length;  
			 }
		 else
		 	return CurrentTextBox.selectionEnd;
		}

//////////////////////////////////////////////////////////////////////////////
		
function setCursorLocation(oField, iCaretPos, iCaretStop) 
{     // IE Support
	if (iCaretStop == null)
		iCaretStop = iCaretPos; 
		
     if (document.selection) 
	 {  
       // Create empty selection range
       var oSel = oField.createTextRange ();
  
       // Move selection start and end to 0 position
       oSel.collapse(true);
       oSel.moveStart('character', iCaretPos);
       oSel.moveEnd('character', iCaretStop);
	   
       oSel.select ();
     }

     // Firefox support
     else if (oField.selectionStart || oField.selectionStart == '0') 
	 {
       oField.selectionStart = iCaretPos;
       oField.selectionEnd = iCaretStop + iCaretPos;
       oField.focus ();
     }
   }
   
///////////////////////////////////////////////////////////////////////////////////////////////////////
