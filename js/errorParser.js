  

/* import parameters from config.js global space into local name space */
//const ERROR_PARSER_START = window.ERROR_PARSER_START;    // at this line the error messages relevant for us start
//const ERROR_PARSER_END   = window.ERROR_PARSER_END;      // at this line the error messages relevant for us end

// input: <txt>    a string of LaTeX error messages using our specific formatting
//        <name>   the name of the tex file including the full filesystem path on the server as it is used within LaTeX
// output: an array of error information Objects
//   .core    element belongs to the area of core interest
//   .txt     text portion 
//   .line
function errorParser (txt) {
  var idx;
  var arr = [];
  var pastMarker = false;
  let obj;
  txt.split("\n").forEach ( ele => {     // split error messages into line elements <ele>
    if ( ele.indexOf (ERROR_PARSER_START) != -1 ) { pastMarker = true;}  // line consists of the HERE_MARKER
    if ( (idx = ele.indexOf ( name + ".tex:"   )) != -1 ) { // found no file identification line
      obj.txt += ele;
    }
    else {                              // found a file identification line; this opens a FRESH object
      arr.push (obj);                   // push the old object
      obj = {};                         // get a fresh object
      lineNum = ele.substring (idx);   
      obj.line = lineNum;            //// TODO: ev: parse 
      obj.txt  = "";
      obj.core = pastMarker;
    }
  } );
  return arr;
}


function errorFormatter (arr) {
  var logContainer = document.getElementById ("logContainer");  // the container into which we fill in the info
  arr.forEach ( (obj) => {
    var txtNode = document.createTextNode (obj.txt);
    var lineNode = document.createElement ("span"); lineNode.innerHTML = obj.line;
    
    var cont = document.createElement("div");
    cont.className = (obj.core ?  "impErr" : "err" );
    cont.appendChild (lineNode);  cont.appendChild (txtNode); 
    
    logContainer.appendChild (cont);
  } );
}




