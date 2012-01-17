
function handleError(message, obj) //later to do something more meaningful, post development
{
    alert(message); 
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////
// Convert String to Date
function strToDate(str)
{
 if (str instanceof Date)
    return str;
 str = (str.toLowerCase().replace("pm", " pm").replace("am", " am")); //fix for chrome    

 if (str.indexOf('-') == 4) //sql server's year-first format
     {
        pieces = str.split("-");
        halves = pieces[2].split(" ");
        return new Date(pieces[1] + "-" + halves[0] + "-" + pieces[0] + " " + halves[1]); 
    }
 else
     return new Date(str);
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////
function pageTerminating()
{
    handleError = function(){};//stifle errors about all failed ajax requests    
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
function mssqlTimestamp(x) //takes [optionally] datetime object
{
    if (x == null)
        x = new Date();
    else    
        x = strToDate(x);
        
    return x.getFullYear() + '-' + (1 + x.getMonth()) + "-" + x.getDate() + " " + x.getHours() + ":" + x.getMinutes() + ":" + x.getSeconds() + ":" + x.getMilliseconds();
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function escapeRegex(str)
{
  var specials = new RegExp("([.*+?|()\\[\\]{}\\\\])", "g"); // .*+?|()[]{}\

  str = str.replace(specials, "\\" + "$&");

  return str;
}
/////////////////////////////////////////////////////////////////////////////
//Only tests for M / D / Y format. (Y/M/D will fail)
function validateDate(str)
{
    if (str.length < 8)
        return false;
    if (str.substr(1,1) == "/")
        str = "0" + str;
    if (str.substr(4,1) == "/")
        str = str.substr(0,3) + "0" + str.substr(3);
    //make into fixed-length 
    
    if (str.substr(2,1) != "/")
        return false;
    if (str.substr(5,1) != "/")
        return false;    
    
    month = parseInt(str.substr(0,2));
    day = parseInt(str.substr(3,2));
    year = parseInt(str.substr(6,4));
            
    newDate = new Date(year, month, day);    
    if (isNaN(newDate))
        return false;
    return true;
}
/////////////////////////////////////////////////////////////////////////////
function str_repeat (input, multiplier)
     {
    // Returns the input string repeat mult times  
    // 
    // version: 1102.614
    // discuss at: http://phpjs.org/functions/str_repeat    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // *     example 1: str_repeat('-=', 10);
    // *     returns 1: '-=-=-=-=-=-=-=-=-=-='
    return new Array(multiplier + 1).join(input);
    } 

///////////////////////////////////////////////////////////////////////////////
// This function prevents the backspace key from sending the user browser BACK. 
function disableBackspace() // ON by default. See call immediately below this function.
{

if (typeof window.event != 'undefined') // IE
  document.onkeydown = function() // IE
    {
    var t=event.srcElement.type;
    var kc=event.keyCode;
    return ((kc != 8 && kc != 13) || ( t == 'text' &&  kc != 13 ) ||
             (t == 'textarea') || ( t == 'submit' &&  kc == 13))
    }
else
  document.onkeypress = function(e)  // FireFox/Others 
    {
    var t=e.target.type;
    var kc=e.keyCode;
    if ((kc != 8 && kc != 13) || ( t == 'text' &&  kc != 13 ) ||
        (t == 'textarea') || ( t == 'submit' &&  kc == 13))
        return true
    else 
        return false;// alert('Sorry Backspace/Enter is not allowed here');         

    }
 }
 disableBackspace();
 
///////////////////////////////////////////////////////////////////////////////////////////////////////////
function setFieldAjax(fields, pkeys, table, action, isAsync, newWind)
{
    if (action == null)
        action = "save"; //action can also be load.
    var information = { "primaryInfo" :pkeys,  "fieldInfo" :fields ,  "table" :  table, "action" : action};
    information = encodeURIComponent(JSON.stringify(information));
    var url = "../Controllers/ajax_field.php?request=" + (information);
      
    if (newWind == 'SHOW')
        window.open(url);
    
    try {
     $.ajax({
      url: url,
      context: this,
      async: isAsync,
      success: function(data, b, c, d, e)
      {  
      if (data.substr(0,1) != "{")
              return handleError("Data not JSON: " + data);
       structure = JSON.parse(data); // Data should be Json 
       if (structure.criticalError != null)
               {
            handleError(structure.criticalError, structure);            
            }
       //Need to make a "find or Need field" option... for ... hmm
       }
       
      });
     }
     catch (e) {handleError (e); }
 // make ajax request.
 // on Success flash box green, then call dependent fields
 // on Failure flash box red, then submit an error to log.
}

function markGridLoading(grid)
{
  grid.clearAll();
  grid.objBox.style.backgroundImage = "url('../css/ajax-loader.gif')";
  grid.objBox.style.backgroundRepeat = "no-repeat";
  grid.objBox.style.backgroundPosition = "50% 50%";
  grid.objBox.style.border = "0px #F00 none";  
//  grid.obj.style. = "";
//  alert("pre");
  //grid.__    
}

function afterGridLoad(grid)
{
  grid.objBox.style.backgroundImage = "none";    
  if (! grid.getRowsNum())
          grid.objBox.style.border = "1px #F00 solid";  

}

function getSelectedGridValue(mygrid, columnName, error)
{
          var row = mygrid.getSelectedRowId();             
         var col = mygrid.getColIndexById(columnName);
         if (!row)
             {
            alert(error);
             return undefined;
            }
            
        return mygrid.cells(row,col).getValue();    
}

function date_custom(a, b, order) //grid sorting
    {
    var n = a.length;
    var m = b.length;
    if (order == "asc")
        return ((strToDate(n) > strToDate(m)) ? 1: -1);
    else
        return ((strToDate(n) < strToDate(m)) ? 1: -1);
    }

function gotoLink(url)
{
 window.open(url);    
}


function addAlexFiltering(grid)
{//takes the grid object
   var filtersDiv = document.createElement("div");
//   var oText = document.createTextNode("www.java2s.com");
//     oNewP.appendChild(oText);

   var beforeMe = grid.objBox.parentElement;
   beforeMe.parentElement.insertBefore(filtersDiv, beforeMe);
    filtersDiv.style.width = beforeMe.style.width;    
    filtersDiv.innerHTML = "Filter: <select name=mygridfilters ></select>         By Value: <input type=text style='width:150px'>";
    
    var filtersSelect = filtersDiv.children[0];
 
    var arr = topgrid.columnIds;
    for (x = 0; x < arr.length; x++)
        {
        filtersSelect.options[x] = new Option(arr[x],x);
        }
    grid.makeFilter(filtersDiv.children[1],0);
    filtersSelect.boundGrid = grid;
    filtersSelect.onchange = function () //make it so the filter is switchable
        {     
        var filters = this.boundGrid.filters;
        for (x=0; x < filters.length; x++)
            if (filters[x][0] === filtersDiv.children[1])
                {
                filters[x][1] = this.value;//set the internal filter in the control to use the column corresponding to our dropdown choice
                filters[x][0].value = "";  //clear the textbox
                this.boundGrid.filterByAll(); //refresh filters.
                }
     };
}

var window_unloading = false; //serves to help stop "fetch field failed" errors when changing pages before fields get ajax responses.
window.onbeforeunload =  function () { window_unloading=true; } ;