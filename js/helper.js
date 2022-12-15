/**  THIS file is inserted very early into the wiki page and hence suitable to do early modification stuff.
 *   TODO: should still minify this
 */
 
/** IMPORT configuration from config.php */     
var CONFIG = RLCONF.Parsifal;  // the usual way would be to use mw.config.get but Mediawiki did not document this sufficiently AND loads this too late so we patch it in like this.

/** DECONSTRUCT the configuration object */
const REG_START = new RegExp ( CONFIG.TAGS.map ( tag => "^<"+tag).join("|") );
const REG_END   = new RegExp ( CONFIG.TAGS.map ( tag => "</"+tag+">" ).join("|") );
const REG_ANY   = REG_END;


///////////////// TODO: what did we intend here ?!?!
/* function of a query extension in the url of specode */
let params = (new URL(document.location)).searchParams;
let name = params.get('specode'); 
if (name == "side") {
  var head = document.getElementsByTagName('head')[0];
  var s = document.createElement('style');
  s.setAttribute('type', 'text/css');
  s.appendChild ( document.createTextNode ( "#content, #footer, #mw-head {display:none;}" ) ); 
  head.appendChild(s);
}
else if (name == "main") {
  var head = document.getElementsByTagName('head')[0];
  var s = document.createElement('style');
  s.setAttribute('type', 'text/css');
  s.appendChild ( document.createTextNode ( "#mw-panel {display:none;}  .mw-body {margin-left:0em;}" ) ); 
  head.appendChild(s);
}



// return true if the page contains content with a tag requiring Parsifal tex activity
function pageContainsTex () { if ( ! REG_ANY.test (editBox.value) ) { console.log ("Parsifal: Found nothing to tex in page ", REG_ANY); return false;} else {return true;} }


/*** COLLAPSIBLES of PARSIFAL ***/

// defines a function which is used by <script> tags injected in TexProcessor for rendering collapsibles
function toggleNext (e) { 
  const button = e.target;
  const ele    = e.target.nextSibling;   
  // console.log ("button: ", button); console.log ("element: ", ele);
  $(ele).slideToggle(150);                               // slide open
  button.classList.toggle ("collapseButtonToggled");     // show button markup to better identify buttons of open areas
  if (!e.shiftKey) {                                     // no shift: close all the others
    $(".collapseResult").not(ele).hide();
    $(".collapseButtonToggled").not(button).removeClass ("collapseButtonToggled");
  }  
}

function toggleImg (e) { 
  const ele = e.currentTarget; 
  const button = e.currentTarget.previousSibling;
  console.log ("button: ", button); console.log ("element: ", ele);  
  $(ele).slideToggle(150);
  button.classList.toggle ("collapseButtonToggled");
}



/****** TODO */
// TODO: Dysfunctional - implement this in a different way
function adjust (wid) {  // for example "15em"
  var head = document.getElementsByTagName('head')[0];
  var s = document.createElement('style');
  s.setAttribute('type', 'text/css');
  s.appendChild ( document.createTextNode ( "div#mw-panel {width:15em;}  #left-navigation, #footer, #content {margin-left:15em;}" ) ); 
  head.appendChild(s);
}
// adjust();  // adjust the width of the sidebar !



function editPreviewPatch () {  // the clutch to PHP; we may adapat it to use CodeMirror, textarea or whatever client side editor we desire
  initializeTextarea();
  let params = (new URL (document.location)).searchParams;
  if (params.get("editormode") == "codemirror") {
    initializeCodeMirror ();  // additionally initialize a code mirror instance
  }
}




function initializeCodeMirror () {
  var myTextArea   = document.getElementById("wpTextbox1");
  var myCodeMirror = CodeMirror.fromTextArea ( myTextArea, { lineNumbers:true, matchBrackets:true} );    // returns an abstract CodeMirror object

  var cmElement = document.querySelector (".CodeMirror");
  cmElement.myFontSize = 14; cmElement.style.fontSize = cmElement.myFontSize + "pt";  cmElement.CodeMirror.refresh();
  cmElement.addEventListener ("keydown", (e) => { // console.log ("Key pressed: ", e.key);
    if (e.metaKey && (e.key=="+" || e.key=="-") ) {e.preventDefault (); e.stopPropagation(); 
      cmElement.myFontSize += ( e.key=="+" ? 2 : -2 ); cmElement.style.fontSize = cmElement.myFontSize + "pt"; 
      cmElement.CodeMirror.refresh();            // needed by code mirror after a font change
    }  });

  var storeResize = true;
  
  const wasResized = () => {
    const VERBOSE = false;
    var textareaWrapper =  cmElement;     // WAS : document.getElementById ("textarea-wrapper");
    var iepc            = document.getElementById ("inline-edit-preview-container");
    if (VERBOSE) console.log (`SIZE CHECK: textarea-wrapper = ${textareaWrapper.clientHeight} and inlineEditpreviewcontainer = ${iepc.clientHeight}`  );
    iepc.style.height = (textareaWrapper.clientHeight - 2) + "px";
    shouldReset   = true;  // next time we call the processing function, do a complete reset of all images (due to the changed resolution in the reset
    if (storeResize) {
      if (VERBOSE) {console.log ("Parsifal:helper.js: resize observer storing textarea dimensions ");}
      //window.localStorage.setItem ("textareaWidth", textarea.offsetWidth);
      //window.localStorage.setItem ("textareaHeight", textarea.offsetHeight);
    } 
    waitBeforeInvoke (300);  // invoke a redisplay of the preview after some waiting time
    if (status == 1) { previewContainer.style.height = "" + (newEditContainer.clientHeight - wrapper.clientHeight) + "px" };
  };

  new ResizeObserver (wasResized).observe (cmElement);          // NEW: cmElement
  new ResizeObserver (wasResized).observe (document.body);
}




// Apply path to the edit page of Mediawiki. Called by <script> tag injected in Parsifal.php: onEditPageshowEditForminitial
function initializeTextarea() { 
  if (! pageContainsTex()) {return;}  // do not patch the edit page if the page contains no tex code
  
  // generate a colored tab which earmarks the textarea of the edit field for a resize
  var pullTab = document.createElement ("div");   pullTab.id = "pullTab"; 
  // cannot put a title on the pullTab due to the z-index and the pointer-events: none (which is needed, as the events must go through to the textarea located below)
  
  // generate an element for toggeling the preview and its position
  var btn = document.createElement("div"); btn.id = "togglePreview";
  btn.style = `height:16px; width: 16px; position: absolute; top:2px; right:2px; background-color:orange; cursor: pointer;`;
  btn.setAttribute ("title", "Toggle preview visibility and position");
  btn.addEventListener ("click", togglePreview); 
  
  // var status = 0;
  if (window.localStorage.getItem ("previewStatus")) {var status = parseInt( window.localStorage.getItem ("previewStatus") );} else {status = 0;}  // initialize previewStatus
  
  var storeResize = true;                                   // shall the resize observer store the resize values?
  
  function togglePreview () {                               // called by UI to change the preview status
    status = (status + 1) % 3;                              // change status
    storeResize = false;                                    // changing the preview status will prompt a resize of the textarea size
                                                            //   this shall be prevented for one time (resize observer will turn on again)
    implementPreviewStatus (status);                        // implement status in UI
    window.localStorage.setItem ("previewStatus", status);  // store status in localStorage as user preference
  }
  
  
  function implementPreviewStatus (c) {
    const DEBUG = false;
    if (DEBUG) {console.info ("implementing preview status = ", c);}

    switch (c) {
      case 0: // image is right
        newEditContainer.style.flexDirection="row";
        textarea.style.resize          = "horizontal";
        wrapper.style.height           = "100%";      
        wrapper.style.width            = "auto";
        textarea.style.height          = "100%";
        // console.error ("helper.js: localStorage sees textareaWidth=" , window.localStorage.getItem ("textareaWidth"));
        if (window.localStorage.getItem ("textareaWidth")) {textarea.style.width = window.localStorage.getItem ("textareaWidth") + "px";}    
            
        
        previewContainer.style.display = "block";  
        previewContainer.style.height = "" + (textarea.clientHeight) + "px" ; // pixel perfection of box-sizing quirx
        btn.setAttribute ("title", "Toggle preview visibility and position. Current: Image is right");
        if (pullTab) pullTab.classList.remove ("pull-disabled");        
        break;
      case 1: // image is bottom
        newEditContainer.style.flexDirection = "column";
        textarea.style.resize = "vertical";
        wrapper.style.width   = "100%";
        wrapper.style.height  = "";     // should be empty, not auto
        textarea.style.width  = "100%";
        if (window.localStorage.getItem ("textareaHeight")) {textarea.style.height = window.localStorage.getItem ("textareaHeight") + "px";}                
        
        previewContainer.style.height = "" + (newEditContainer.clientHeight - wrapper.clientHeight) + "px" ;
        previewContainer.style.width = "" + (textarea.clientWidth) + "px" ;  // pixel perfection of box-sizing quirx
        btn.setAttribute ("title", "Toggle preview visibility and position. Current: Image is bottom");        
        if (pullTab) pullTab.classList.remove ("pull-disabled");
        break;
      case 2: // no image to be shown
        previewContainer.style.display = "none";  
        textarea.style.height          = "100%";
        wrapper.style.height           = "100%";
        textarea.style.resize          = "none";
        btn.setAttribute ("title", "Toggle preview visibility and position. Current: No image");  
        if (pullTab) pullTab.classList.add ("pull-disabled");    
        break;
    }
  }
  
  // pick up the textarea
  var textarea = document.getElementById("wpTextbox1");  
  if (window.localStorage.getItem ("textareaWidth")) {textarea.style.width = window.localStorage.getItem ("textareaWidth") + "px";}
  
  var textareaOldOffsetWidth = textarea.offsetWidth, textareaOldOffsetHeight = textarea.offsetHeight;


  
  const wasResized = () => {
    const VERBOSE = false;
    var textareaWrapper = document.getElementById ("textarea-wrapper");
    var iepc            = document.getElementById ("inline-edit-preview-container");
    if (VERBOSE) console.log (`SIZE CHECK: textarea-wrapper = ${textareaWrapper.clientHeight} and inlineEditpreviewcontainer = ${iepc.clientHeight}`  );
    iepc.style.height = (textareaWrapper.clientHeight - 2) + "px";
    shouldReset   = true;  // next time we call the processing function, do a complete reset of all images (due to the changed resolution in the reset
    if (storeResize) {
      if (VERBOSE) {console.log ("Parsifal:helper.js: resize observer storing textarea dimensions ");}
      window.localStorage.setItem ("textareaWidth", textarea.offsetWidth);
      window.localStorage.setItem ("textareaHeight", textarea.offsetHeight);
    } 
    waitBeforeInvoke (300);  // invoke a redisplay of the preview after some waiting time
    if (status == 1) { previewContainer.style.height = "" + (newEditContainer.clientHeight - wrapper.clientHeight) + "px" };
  };

  new ResizeObserver (wasResized).observe (textarea);
  new ResizeObserver (wasResized).observe (document.body);

  textarea.style.resize = "horizontal";  
  
  textarea.myFontSize = 14; textarea.style.fontSize = textarea.myFontSize + "pt";
  textarea.addEventListener ("keydown", (e) => { // console.log ("Key pressed: ", e.key);
    if (e.metaKey && (e.key=="+" || e.key=="-") ) {e.preventDefault (); e.stopPropagation(); textarea.myFontSize += ( e.key=="+" ? 2 : -2 ); textarea.style.fontSize = textarea.myFontSize + "pt"; }  });
  
  var newEditContainer = document.createElement ("div");   // container of textarea for editing and preview image
  newEditContainer.id = "new-edit-container";
  
  var parent = textarea.parentNode;
  parent.replaceChild (newEditContainer, textarea);
 
  var wrapper = document.createElement ("div");                    // generate a DIV which wraps the textarea; necessary to ensure correct behavior
  wrapper.id = "textarea-wrapper";
  if (pullTab) wrapper.appendChild (pullTab);
  wrapper.appendChild (btn);
  wrapper.appendChild (textarea);
  newEditContainer.appendChild (wrapper);
  
  var previewContainer = document.createElement ("div");          // generate a container for the preview
  previewContainer.id = "inline-edit-preview-container";
  newEditContainer.appendChild (previewContainer);
  
  implementPreviewStatus (status);
  
  processEdit ();                                                 // kick off display of first preview
}







// if Javascript is notified that an image is missing, we call an endpoint which purges the page from the cache and rebuilds the TeX structures
// if the rebuild fails, we get notified via usual error notification mechanism
// however, we must purge the page only ONCE not for every missing images
window.imageIsMissingCompleted = false; 


window.imageIsMissing = function imageIsMissing (title, hash) {
  if (window.imageIsMissingCompleted) {return;}
  window.imageIsMissingCompleted = true;
  console.error (`Parsifal:helper.js:imageIsMissing: Found missing image at title=${title} for hash=${hash}, will purge the Mediawiki parser cache and regenerate image` );
  var xhr = new XMLHttpRequest();
  xhr.open('POST', "/wiki4/api.php", true); /////////////////// PATH !!
  var formData = new FormData();
  formData.append("action", "purge");
  formData.append("titles", title);
  formData.append("format", "json");
  xhr.setRequestHeader("Content-Disposition", "form-data");
  xhr.send(formData);  
  xhr.onload = (e) => {
    console.error ("Parsifal:helper.js:imageIsMissing: The purge request returned ", e.target.response);
    console.error ("Parsifal:helper.js:imageIsMissing: Will now reload ", e.target.response);
    window.location.reload();
  }
}





/** BEGIN KUNDRY **/


const installDropZone = ( dropZone=document.documentElement, handler ) => {
  const VERBOSE = true;    
  const VVERBOSE = false;   // UI detail verbosity: Very Verbose
  var markElement = document.body; //(dropZone === document.documentElement ? document.body : dropZone);  // documentElement does not allow classList, body does
  dropZone.addEventListener ('dragenter',  (e) => { if (VVERBOSE) {console.log ("dragenter", e.target);}   markElement.classList.add ("dropzone-active");}     );
  dropZone.addEventListener ('dragleave',  (e) => { if (VVERBOSE) {console.log ("dragleave", e.target);}   markElement.classList.remove ("dropzone-active");}  );
  dropZone.addEventListener ('dragover',   (e) => { if (VVERBOSE) {console.log ("dragover",  e.target);}   markElement.classList.add ("dropzone-active");
    e.stopPropagation(); e.preventDefault();    // must prevent defaults on dragover (or the chrome extension kicks in)
    e.dataTransfer.dropEffect = 'copy';         // Show the copy icon when dragging over.  Seems to only work for chrome.
  });

  dropZone.addEventListener('drop', function(e) { if (VVERBOSE) { console.log ("drop", e.target); }
    markElement.classList.remove ("dropzone-active");         // remove marking again !
    e.stopPropagation();  e.preventDefault();
    var files = e.dataTransfer.files;
    if (VERBOSE) {console.log ("dropzone received " , files.length ,  " files");}
    let txt = [];                                            // accumulates the names
    for (var i=0, file; file=files[i]; i++) {
      if (VERBOSE) {console.log ("dropzone sees file: ", file);}
      txt.push (file.name);
      if ( file.type == "application/pdf" ) {
        console.log ("reading file");
        var reader = new FileReader();
        reader.onload = function(e2) { if (handler) { console.log ("handling file" ); handler (e2.target.result);} }      // if we have a handler, present the result to the handler as an ArrayBuffer
        reader.readAsArrayBuffer(file);  // note: this is MUCH faster than readAsDataURL
      }
      else {
        alert ("Cannot deal with a file of type: " + file.type);
      }
    } // for
    
  });
};


// handler for dropped files; expects to receive an array buffer <buf>
async function dropHandler (buf) {
  openEmbedded (buf);
  promiseSha1(buf); 
}

// promise to return the hex coded hash value of arraybuffer <buf>
const promiseSha1 = (buf) => {
  console.log (typeof buf);
  return crypto.subtle.digest('SHA-1', buf)
    .then ( hash => {
       const hexCodes = [];
       const view = new DataView(hash);
       for (let i = 0; i < view.byteLength; i += 1) {hexCodes.push( view.getUint8(i).toString(16) );}
       return hexCodes.join('');      
    });
};


var DATA;


///////////////////////////////////////////// ?????????????????????????????????? TODO: adjust this to the correct location
//
const DOCU_WINDOW_URL = document.location.protocol + "//" + document.location.host + "/wiki4/extensions/Parsifal/html/embedPresent.html";

// TODO: do something for persisting size and position ?????????????????????????????
////////////////////////////////////////////   The query portion below is used to force a reload and non-caching while we are developing !!!!!!!!!!!!!!!!!!!!!!!!!
function openEmbedded (buf) {
  var slave = window.open (DOCU_WINDOW_URL + "?" + Math.random(), "fresh", "width=600,height=600");  //// TODO: PATH into config ////////////////////////////////
  slave.onload = (e) => { slave.postMessage ({buf}, DOCU_WINDOW_URL);}  // as soon as that window is open, transfer the document to that window
  
}



window.sendToWiki = (x) => {uploadToWiki (x, "application/pdf");}

const MIME2EXT = { "application/pdf": "pdf" };


// upload contents of buffer <buf> under mime-type <mime> (e.g. application/pdf) and filename <filename> to the wiki
// if <filename> is missing, use sha1 of the file content
async function uploadToWiki (buf, mime, filename) {
  if (!filename) { filename = await promiseSha1 (buf); } 
  filename += "." + MIME2EXT[mime];
  
  console.log ("will now upload " + filename);
  var param = {filename, format: 'json'};
  var	fileInput = $( '<input/>' ).attr( 'type', 'file' ).value = new Blob ([buf], {type: mime});
  var api = new mw.Api();
  api.upload( fileInput, param )
    .done( function ( data ) {
      alert ("uploaded");
      console.log( data.upload.filename + ' has sucessfully uploaded.' ); } )
    .fail( function ( data ) { 
      alert ("upload error: " + data);
      console.log( "api reports: ", data );} );
}

/*****************/
/** END KUNDRY **/

 
/////// TODO: looks likle we must adjust this to be pixel perfect !! --------------------------- CURRENTLY we are not even using it
// mechanism for adjusting the proper height of the preview image when adjusting the textarea height
function heightAdjust () { 
  console.error ("#######################################heightadjust");
  var texta = document.getElementById ("wpTextbox1");
  var cont = document.getElementById ("inline-edit-preview-container");
  var editOpt = cont.parentNode;
  
  var height = texta.clientHeight;          // save the initial height of textarea
  var contHeight = cont.clientHeight;       // save the initial height of container of preview
  
  var onResize = (e) => { 
    var newHeight = e.target.clientHeight;
    var diff = newHeight - height;
     console.error ("##heightadjust: textarea resized from initially " +  height + " to now " + newHeight + " difference is " + diff);
 
        console.error ("## heightadjust target for container", (contHeight - diff) + "px");
    cont.style.height = ((contHeight - diff) + "px");
    editOpt.style.height = ((contHeight - diff) + "px");
   // console.error ("#######################################heightadjust resize freshf", (cont.clientHeight + diff) + "px");
  }
  
  texta.addEventListener ("mouseup", onResize);  // there is no resize event on teatarea
}


/** HOW to show the correct version of a preview?
 *
 *  PROBLEM: We may have several requests for previews pending at the server at the same time. The response may thus come out of sequence.
 *  1) We want to display the current preview and not an old one which happened to come later from the server.
 *  2) We want to display the previews in correct order, i.e. we might miss one which is already superseded, but we do not want to see the
 *     text building up in the wron sequence.
 *  3) We want to abort old, pending requests to be nice on server performance.
 *  
 *  We do not reuse xhr objects but we reuse img objects.
 * 
 *  xhr.img          Every xhr request has a .img reference to the img element where the result should display.
 *  xhr.number       Every xhr request gets a number which is unique and strictly increasing over ALL preview requests the client will issue; it is stored in .number
 *  img.showing      Every img has a number of the request it is currently is showing
 *  img.pending      Every img has an array containing references to the requests which are still pending for it
 *
 */

  

var nextNumberIssuing = 0;    // this is the number of the next request to be issued
var xhrReg = {};              // register old xmlhttprequests for cancellation or superfluous stuff; maps number of request to request xhr object


// when the client receives any response from the preview endpoint: patch the information into the area where we show the preview
function receivedEndpointResponse(e) {
  const VERBOSE = false;
  var target    = e.target;  
  var img       = target.img;
  
  ERROR.clearError ();    // remove error message which might still be there from an earlier invocation
  
  if (VERBOSE) {console.log (`received an endpointResponse, it is for request=${target.number}, img is showing=${target.img.showing}, pending are ${target.img.pending.length} requests: ${img.pending.map ( ele => ele.number).join( )}`);}
  if (VERBOSE) {target.img.pending.forEach ( ele => {console.log (`  request=${ele.number} with readyState= ${ele.readyState}`);}  );  }  // only if we want even more details

  img.pending = img.pending.filter ( x => {if (x.number < target.number) {  if (VERBOSE) {console.log (`receivedEndpointResponse: ABORTING as outdated the pending request ${x.number}`);}
    x.abort();  return false;} else {return true;} });
  if (target.number < img.showing)  { if (VERBOSE) {console.log (`receivedEndpointResponse: DISCARDING an old result. We received ${target.number}. We show currently ${img.showing}`);}  return;}
    
  // pick up headers we are interested in
  var hash         = target.getResponseHeader ('X-Latex-Hash');
  var type         = target.getResponseHeader ('Content-Type');
  var len          = target.getResponseHeader ('Content-Length');
  var errorStatus  = target.getResponseHeader ("X-Parsifal-Error");  
  var errorDetails = target.getResponseHeader ("X-Parsifal-ErrorDetails");

  // pick up any headers which might be present and useful for debugging
  if (VERBOSE) {
    var widthLatexCmWas        = target.getResponseHeader ("X-Parsifal-Width-Latex-Cm-Was");
    var availablePixelWidthWas = target.getResponseHeader ("X-Parsifal-Available-Pixel_Width-Was");
    var dpiUsed                = target.getResponseHeader ("X-Parsifal-Dpi-Used");
    var gammaUsed              = target.getResponseHeader ("X-Parsifal-Gamma-Used");
    console.info (`receivedEndpointResponse: of type=${target.response.type} size=${target.response.size} length=${len} hash=${hash}`);    
    console.log  (`receivedEndpointResponse from ${target.responseURL} with headers: hash=${hash}, type=${type}, len=${len}, readyState=${target.readyState}, status=${target.status} and target.response=`, target.response);   
    console.log  (`  additional headers received for check: widthLatexCmWas=${widthLatexCmWas} availablePixelWidthWas=${availablePixelWidthWas} dpiUsed=${dpiUsed} gammaUsed=${gammaUsed}`);
  }
   
  showLogLinks (hash);                                                                    // show links to log file of the Tex run   
   
   if (errorStatus == "Hard") {     console.error ("receivedEndpointResponse: received X-Parsifal-Error = Hard. NO result to display. Will display error message instead");
     // higher up we had to request a responseType of "blob" (for the image case); thus .responseText is not available now and we must convert and wait for Promise !
     target.response.text().then ( myText => {
       if (target.status != 200) { myText = "Server responded: " + target.status + " " + target.statusText + "\n" +  myText; } 
       console.error ("   error message received is: " + myText );           // instead of image we received a text error message :-(
       ERROR.showError (myText, errorStatus);                                                  // show the message which just was received completely
     
       img.showing = target.number;                                                            // update the number of the request which we display
       img.pending = img.pending.filter ( ele => (ele.number != target.number) );              // and remove the request just served successfully from the list of pending requests we have to check   
       if (VERBOSE) {console.log (`  have just completed serving request ${target.number}; now still ${img.pending.length} remaining on queue: ${img.pending.map ( ele => ele.number).join( )} `); }      
     });
     
     //// TODO: some stuff ??
     return;
   }
   else if (errorStatus == "Soft") { 
      if (!errorDetails) {errorDetails = "Soft error. Will probably vanish when completing Latex command";}
      ERROR.showError ( errorDetails, "Soft"); 
      // WE MIGHT request some error information 
    
    }
    
  
  
  // handle response depending on the MIME type header
  if (type.startsWith ('image')) {   if (VERBOSE) {console.log ("Client received IMAGE data from endpoint");}
    target.img.src    = window.URL.createObjectURL(target.response);                          // feed the image data to the image area
    target.img.onload = function() {                                                          // as soon as the image has loaded completely
      window.URL.revokeObjectURL(this.src);                                                   // we may revoke the object URL
      img.showing = target.number;                                                            // update the number of the request which we display
      img.pending = img.pending.filter ( ele => (ele.number != target.number) );              // and remove the request just served successfully from the list of pending requests we have to check
      if (VERBOSE) {console.log (`  have just completed serving request ${target.number}; now still ${img.pending.length} remaining on queue: ${img.pending.map ( ele => ele.number).join( )} `); }
      img.setAttribute ("data-image-width-requested-from-server", availablePixelWidthWas);    // ONLY debug     
      img.setAttribute ("data-image-width-measured", target.img.width);
      img.setAttribute ("data-image-natural-width-measured", target.img.naturalWidth);    
      }
  }
  
  else if (type.startsWith ('text')) {  if (VERBOSE) {console.log ("Client received TEXT data from endpoint");}


  }
  else {console.error ('faulting-in handle received an illegal type ' + type + ' for hash=' + hash);}    
  

  if (VERBOSE) {console.log (`at the end of the response handler for request=${target.number}, img is showing=${target.img.showing} and pending are still ${target.img.pending.length} requests: ${img.pending.map ( ele => ele.number).join( )}`);}
  if (VERBOSE) {target.img.pending.forEach ( ele => {console.log (`  request=${ele.number} with readyState= ${ele.readyState}`);}  );  } // only if we want even more details
  
};

// TODO: what is this now ???? deprecate maybe ??????? change ???????
//  checkForError (hash);  // check for an error in this call     TODO: would be faster if we placed this into a response header !!! and not into a separate server call
// YES, we need the function checkForError for the scenario that we load the media from cache. See ENDPOINTS.MD documentation



/* display an image only after a complete load: prevents that user sees partially loading image from slow loading process; called in img tag of rendered html */
window.showImage = function showImage (target, hash) {
  const VERBOSE = false; 
  if (VERBOSE) {console.log (" Parsifal:helper.js: showing image now ", hash, "target ", target);}
  target.style.display = "inline-block";
}


// function for injecting the annotation text at the right place, identified by hash
const injectAnnotation = (text, hash) => {
  var ele =document.getElementById ("ANCH-"+hash);       // ANCH-hash is the id of the respective tag where we should inject the annotation layer for the respectuve tag
  if (ele) {
    var div = document.createElement ("div");
    div.className = "annoLayer";
    div.innerHTML = text;
    ele.appendChild (div);}
  else {console.error ("Parsifal:helper.js: could not inject annotation since container element was not found");}  
};


// DEPRECATE THIS AS WELL
/* 
// NEW CODE
// returns a promise to get the annotations from url fullPath and inject them into the place identified by hash
// when successfull:   resolve ()
// when error:         reject (e) on error,   reject (2) on timeout 
function getAnnotations (hash, url) {
  const VERBOSE = true;
  console.error (`getAnnotations called for ${hash}, accessing ${url}` );
  var prom = new Promise ( (resolve, reject) => {
    if (VERBOSE) {console.log (`***** Parsifal:helper.js getAnnotations ${url}`);}
    var xhr2 = new XMLHttpRequest ();   
    xhr2.open ('GET', url, true);     // path to endpoint is provided by caller
    xhr2.setRequestHeader ('Content-Type', 'text/plain;charset=UTF-8'); 
    xhr2.onload = (e) => {
      if (xhr2.status == 200) {injectAnnotation (xhr2.responseText, hash); resolve ("ok");}
      else {resolve ("no");}
    };
    xhr2.onerror   = (e) => {console.error ("Parsifal:helper.js:getAnnotations: did not obtain annotation file for " + hash);    resolve (e);}
    xhr2.ontimeout = (e) => {console.error (`Parsifal:helper.js:getAnnotations: timed out annotation file ${url} for ${hash}`);  resolve ("timeout");}
    xhr2.timeout = 8000;                                                                                                                                      ///// CONFIGURE TODO: place in config.php
    xhr2.send();
  });
  return prom;
}
*/


// check if for the given hash we have a soft latex error indication and if yes, append class latex-error to the respective img file
window.checkForError = function checkForError (hash) {
  const VERBOSE = false;
  if (VERBOSE) console.log ("checkForError: now checking error status for " + hash);
  var xhr2 = new XMLHttpRequest (); 
  xhr2.open ('GET', "/wiki4/extensions/Parsifal/tmp/" + hash + ".mrk", true);     ///////////////////////////////////////////////  PARAMETRIZE HARDCODED WIKI4 VALUE !! TODO
  xhr2.send ();
  xhr2.onload = (e) => {
    if (VERBOSE) console.log ("checkForError onload handler got: ", e);
    if (VERBOSE) console.log ("checkForError sees: " , e.total, e.target.responseText);  
    var ele = document.getElementById (hash);                                           // marking for the main view
    if (ele) {ele.classList.add ( (e.total > 0 ? "latex-error" : "latex-ok" ) ) ;}
    ele = document.getElementById ("inline-edit-preview-container");                    // marking for the preview
    if (ele) {
      ele.classList.remove ("latex-error", "latex-ok");                                 // clear marking for the preview as this is done repeatedly
      ele.classList.add ( (e.total > 0 ? "latex-error" : "latex-ok" ) ) ;}
  };
}





// https://stackoverflow.com/questions/30106476/using-javascripts-atob-to-decode-base64-doesnt-properly-decode-utf-8-strings
// first we use encodeURIComponent to get percent-encoded UTF-8, then we convert the percent encodings into raw bytes which can be fed into btoa.

function b64EncodeUnicode(str) {return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function toSolidBytes(match, p1) {return String.fromCharCode('0x' + p1);} )); }


/** Request throtteling. 
 * 
 *  Prio 1: Fluent, responsive real time behavior
 *    THUS: Do NOT wait before issuing a request.
 *    THUS: Do NOT cancel a request when issuing one since it might be the one which should show soon (otherwise a fast typing speed can be so fast so as to always cancel pending 
   *    requests leading to a situation with no update at all)
 *  Prio 2: Do not have an outdated status on display
 *    THUS: Count requests and do not render results of an older request
 * 
 * Problem: Do not overload the server. When we are typing quickly, we might type faster than the server may respond and thus we are overloading the server.
 * 
 */ 

/* Scheduler: 
 */

// assume: fct is a function which when applied starts an asynchronous activity and immediately returns providing a promise
// Task.running    a Task which currently is running and awaiting completion or null if there is none
// Task.next       the Task awaiting execution next
function Task (fct) {
  const VERBOSE = true;
  console.log ("Parsifal:helper.js: Task called");
  if (Task.running) {  // if there is a task which is still running
    console.log ("Parsifal:helper.js: there is a task which is still running");
    if (Task.running.hasContinuation) { // if that running task already knows that it should kick off the next round: do nothing
      console.log ("Parsifal:helper.js: and this task already has a continuation set");
    }
    else { // if that running task does not yet know that it should kick off the next round: inform it that it has to do so
      console.log ("Parsifal:helper.js: and this task has no continuation set, so we shall do this now");
      Task.running.then ( ()=> {
        Task.running = null;  // the running task just has finished
        console.log ("Parsifal:helper.js: running a tasks continuation handler to check for a next task");        
        if (Task.nextOne) { Task.nextOne();
          Task.nextOne = null;  // we have no next one task now
        }  ////////////////////// PERHAPS we have to nullify nextOne here
        else {}
      });                  
      Task.running.hasContinuation = true;     // flag the task has knowing about this coninuation
    }
    Task.nextOne = fct;    // set the incoming task as the next one awaitin execution, possibly overwriting an older one 
  }
  else {  // if no task is running: run fct now 
    if (VERBOSE) console.log ("Parsifal:helper.js:Task: no task runnin, running fct now");
    Task.running = fct();
  }
}

Task.running = null;
Task.nextOne = null;




// this function is called whenever the user makes a change in the editor textarea box
function processEdit () {
  // console.info ("***** processEdit was invoked ");
   processEditDoNG ();   // classical - and THIS really is the fastest for editing, since we have no additional waiting time which we collect
  // new Task ( () => {return new Promise ( processEditDo );} );
  //waitBeforeInvoke (300);
}


var timer = null;
function waitBeforeInvoke (ms) { // this MUST be used when the preview area is resized (otherwise we get a too high rate of server requests - for every resie event fired by client)
  window.clearTimeout (timer);   
  timer = window.setTimeout ( processEdit, ms);       // start a timer and only then invoke processEdit  
}


const getCursor = textarea => textarea.value.substr(0, textarea.selectionStart).split("\n").length-1;


/* analyze the content of the edit box; return an object of the structure {arr, currentLine, currentColumn, currentChunk};
    arr:           is an array of chunk informations
    currentLine:   line the cursor currently is in
    currentColumn: column the cursor currently is in
    currentChunk:  index of the chunk the cursor currently is in; identifies chunk in arr array_map
    chunk info:
       .start      the first line which is part of the chunk (counting starts at 0)
       .end        the last line which is part of the chunk  (counting starts at 0)
       .chunk      content of the chunk
       .tag        Parsifal tag name or "TEXT" indicating the nature of the chunk
    
 */
function findChunks (txt) {
  const VERBOSE = false;
  const LineReg = /=+[a-zA-Z0-9()\s\/]+=+/;
  var arr = [];
    
  // get the line and column number of the current cursor position  
  var textLines     = txt.substr(0, editBox.selectionStart).split("\n");
  var currentLine   = textLines.length;
  var currentColumn = textLines[textLines.length-1].length;
  if (VERBOSE) console.log("findChunks: Current Line Number="+ currentLine + " Current Column=" + currentColumn );
  
  // analyze the rest
  textLines = txt.split("\n");    // split text into lines
  var start = 0;
  var match;
  var end;
  var tag = null;                 // saves the information on the mode we currently are parsing in
  var chunk = "";                 // collects lines belonging to the same parsing mode
  if (VERBOSE) console.log ("fundChunks: iterating lines ", textLines.length);
  for (var i=0; i < textLines.length; i++) {                                                   // check all the individual lines
    if (VERBOSE) {console.log ("line number " + i + " is: " + textLines[i]);}
    if ( match=textLines[i].match(REG_START) ) {                                               // CASE 1: line is starting with an opening Parsifal tag name
      if (VERBOSE) console.log ("findChunks: Found starting tag: ", textLines[i], match);
      if (tag) { 
        if (VERBOSE) console.log ("  playing out what we had thus far:", {start, end, chunk, tag}); 
        arr.push ( {start, end, chunk, tag} ); 
        if (start <= currentLine && currentLine <= end) {currentChunk = i;}
        chunk=""; start=i;} 
      tag = match[0].substring(1);  // pick up tag name
      chunk ="";                    // clear chunks at starting line
    }
    else if (textLines[i].search(REG_END) != -1) {                                             // CASE 2: line is starting with a closing Parsifal tag name
      if (VERBOSE) console.log ("findChunks: Found ending tag: ", textLines[i]);
      end = i;
      arr.push ( {start, end, chunk, tag} );
      if (start <= currentLine && currentLine <= end) {currentChunk = i;}
      tag = null;                  // clear identified tag
      chunk = "";
      start = i+1;
    }
   // else if ( textLines[i].startsWith ("=") ) {                                               // CASE 3: line is starting with a heading indication  =, ==, or similar
      
      
    else if ( LineReg.test( textLines[i].trimEnd()) ) {  
      
//    else if ( textLines[i].startsWith("=") && textLines[i].trimEnd().endsWith("=") ) {          // CASE 3: line is starting with a heading indication  =, ==, or similar     
      if (tag == null) {  // we are not inside of a different mode yet - then it is a heading after a heading or after a Parsifal closing tag
        arr.push ( {start:i, end:i, chunk:textLines[i], tag:"TEXT"} );  // play out this chunk as a seperate chunks
        chunk = "";  // reset 
        start = i+1; end = i+1;
      }
      else if (tag == "TEXT") {  // we are in TEXT mode already and this is a heading showing up after some ordinary text 
        arr.push ( {start, end, chunk, tag} );   // play out last chunk (it is a seperate TEXT portion)
        if (start <= currentLine && currentLine <= end) {currentChunk = i;}  
        arr.push ( {start:i , end:i, chunk: textLines[i], tag: "TEXT"} );    // play out this chunk 
        chunk = "";      // reset to a fresh parsing start
        start = i+1; end=i+1;
      }
      else {  // we are inside of a Parsifal tag and this just is an equal sign at the beginning of the next line
        chunk += textLines[i] + "\n";
        end = i;
      }
      tag=null;
    }
    else {                                                                                    // CASE 4: line is "other" text
      if (tag == null) {tag = "TEXT";}
      end = i;
      if (VERBOSE) console.log ("findChunks found normal line: ", textLines[i]);
      chunk += textLines[i] + "\n";}
  }
  if (tag == "TEXT") {arr.push ( {start, end, chunk, tag} );
    if (start <= currentLine && currentLine <= end) {currentChunk = i;}
  }
  
  
  var ret = {arr, currentLine, currentColumn, currentChunk};
  
  if (VERBOSE) {logChunks (ret.arr);}
  
  return ret;    
}


function logChunks (arr) {  // DEBUGGING and LOGGING support
  console.error ("*** *** ***"); console.error ("LOGGING CHUNK STRUCTURE");    
  for (var i = 0; i < arr.length; i++) {
    console.log (`Entry ${i} is from start=${arr[i].start} to end=${arr[i].end} with ${arr[i].chunk.length} characters and tag=${arr[i].tag}\n\n${arr[i].chunk}`);
  }
}








function escapeHtml(unsafe) {return unsafe.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');}



var shouldReset = false;    // flag. if true: next time we call processEditDoNG we should rebuild everything

var uiChunks = [];          // the UI elements which have been constructed from parsing the text
/* uiChunks will be an array of objects of the following signature:
     .tag      "TEXT"  in case the line starts with text
 
    The UI elements in uiChunks could be
    1) image elements 
    2) text  element  


  The objects can have two different structures.
  For the "TEXT" case:
    .implementedText   caches the text which IS/has been implemented in the UI element    
    .todoText          if present at all: contains text which should now be implemented in the UI element
    
    .implementedBody   caches the Latex content which IS/has been implemented in that image
    .todoBody          if present at all: contains the Latex content which should now be implemented in that image

*/
 
 
// given a text portion txt, return the text carrier element implementing it; returned value MUST be a node
function makeTextCarrierNode (txt) {
  txt = txt.replace (/^\x20*\n/gm, "");    // remove lineas which only consist of blanks and newline  
  var node;
  if (txt.trim().length == 0)            { node = document.createElement ("span"); node.innerHTML = "White Space";     }                  
  else if (txt.trim().startsWith ("="))  { node = document.createElement ("div");  node.innerHTML = escapeHtml (txt);  }    // mediawiki heading line
  else                                   { node = document.createElement ("pre");  node.innerHTML = escapeHtml (txt);  }    // other text  
  return node;  
} 


// this is the processing function producing more than one preview area when there are several tags
function processEditDoNG (resolve, reject) {
  const VERBOSE = false;
  const DETAILS = false;
  
  if (!editBox)            { if (VERBOSE) {console.log ("Parsifal: processEditDoNG returning, no editBox");}                return;}    // there is no editbox, return  
  if (!pageContainsTex())  { if (VERBOSE) {console.log ("Parsifal: processEditDoNG returning, contains no relevant tag");}  return;}    // there is no tex-tag on this page
  
  var buildChunks = false;
  var chunks      = findChunks (editBox.value);        // determine currently requested chunk structure from the text in the editBox
  console.log (`processEditDoNG entered: Have ${uiChunks.length} uiChunks, reparse found ${chunks.arr.length} chunks; shouldReset=${shouldReset}`);
  
  // compare to see if the structure has changed
  if (chunks.arr.length != uiChunks.length) {
    if (VERBOSE) {console.error (`PARSIFAL:helper.js: The number of chunks has changed from ${uiChunks.length} to ${chunks.arr.length}. We need a rebuild of the chunk structure.`);}
    buildChunks = true;}
  else { for (var i=0; i < chunks.arr.length; i++) { 
    if (chunks.arr[i].tag != uiChunks[i].tag) {
     if (VERBOSE) {console.error (`PARSIFAL:helper.js: chunk[${i}] has changed from ${uiChunks.arr[i].tag} to ${chunks.arr[i].tag}. We need a rebuild of the chunk structure.`);} 
      buildChunks = true; break;}  }  }

  var previewContainer = document.getElementById ("inline-edit-preview-container");
  
  if (buildChunks || shouldReset) {        // the chunk structure has changed or we found a reset flag: build it fresh
    shouldReset = false;                   // we did do the reset
    if (VERBOSE) {console.log ("Rebuilding the chunk structure now");}
    if (uiChunks.length > 0) {             // must cleanup old
      console.error ("  Cleaning up old chunks -- STILL MISSING CODE ");
      previewContainer.innerHTML = "";
      uiChunks = [];    //// OOPS: we MIGHT reuse some parts .... think still.... about it
    }   
    // implementedBody 
    // todoBody
    // img
    // tag
  
  if (VERBOSE) {console.log (`Parsifal.php:helper.js: rebuildng chunk structure. Found ${chunks.arr.length} chunks and will now be iterating over them`);}
    for (var i=0; i < chunks.arr.length; i++) { 
      if (chunks.arr[i].tag == "TEXT") {
        var myChunk = chunks.arr[i].chunk;                                                         // the text chunk on which we should work
        var textCarrierNode = makeTextCarrierNode ( myChunk );                                     // how we should render this text chunk
        var textDiv = document.createElement ("div"); textDiv.className = "preview-text-portion";  // the container for this chunk
        textDiv.appendChild (textCarrierNode);
        uiChunks[i] = {tag: "TEXT", textCarrierNode: textCarrierNode};   
        previewContainer.appendChild (textDiv);
      }
      else {
        let img = document.createElement ("img"); img.className = "inline-edit-preview-img";
        uiChunks[i] = {tag: chunks.arr[i].tag, img};
        previewContainer.appendChild (img);      
      }
    }
    if (VERBOSE) {console.log (`Chunk structure has been rebuilt`);}
  }
  else {if (VERBOSE) {console.log (`The chunk structure has stayed as it was`);}}


  // now iterate the ui elements and check where we still have tasks to do - and where we have: set up these tasks
  if (VERBOSE) {console.log (`Parsifal: helper.js now filling in tasks for the ${uiChunks.length} uiChunks `);}
  for (let i=0; i < uiChunks.length; i++) {
    if (chunks.arr[i].tag == "TEXT") {   // CASE for TEXT areas
      if (uiChunks[i].implementedText == chunks.arr[i].chunk) { if (DETAILS) {console.log ("ui chunk " + i + " is TEXT and already is correctly implemented");} }  
      else {  if (DETAILS) {console.log ("ui chunk " + i + " is TEXT but now has a different contents than earlier so it gest a fresh todoText field");}
        uiChunks[i].todoText = chunks.arr[i].chunk; 
        uiChunks[i].tag = chunks.arr[i].tag;  // TODO: is THIS stll necessary ?????
      } 
      uiChunks[i].todoText = chunks.arr[i].chunk;   // TODO: test if we still need this !!!!!
      uiChunks[i].tag = chunks.arr[i].tag;          // TODO:  test if we still need this !!!!!
    }
    else {                               // CASE for IMAGE areas
        if (uiChunks[i].implementedBody == chunks.arr[i].chunk) { if (DETAILS) {console.log ("ui chunk " + i + " is an image and already has a correctly implemented body ");} }  
        else {                                                    if (DETAILS) {console.log ("ui chunk " + i + " is an image and just got a fresh todoBody ", uiChunks[i].todoBody);}       
          uiChunks[i].todoBody = chunks.arr[i].chunk; uiChunks[i].tag = chunks.arr[i].tag;   // set .todoBody and indicate this chunk should be worked on
        } 
      }
  }

  // count if the number of image todos is larger than 1 - in which case we suppress the scrolling into view below
  var numOf = 0;for (let i = 0; i < uiChunks.length; i++) { if (uiChunks[i].todoBody) {numOf++;} if (numOf >= 2) {break;}}

  // implement the TODOs which have changed
  if (VERBOSE) {console.log ("helper.js: now implementing the todos which changed todos");} 
  for (let i = 0; i < uiChunks.length; i++) {
    if (uiChunks[i].todoBody) {   // work on a latex todoBody
      if (VERBOSE) {console.log (`uiChunk ${i} has a latex todoBody starting with: ${uiChunks[i].todoBody.substring(0,10)}` );}
      if (DETAILS) {console.log (`uiChunk ${i} is: `, uiChunks[i].todoBody)}
      var body = b64EncodeUnicode ( uiChunks[i].todoBody );
      const xhr = new XMLHttpRequest();
      xhr.img = uiChunks[i].img;  // patch in reference to the image which we want to modify and whose parent we modify in case of an error !
      xhr.img.idx = i;            // needed for selection from image to textarea
      if (typeof xhr.img.pending == "undefined") {xhr.img.pending = [];}
      if (typeof xhr.img.showing == "undefined") {xhr.img.showing = -1;}
      xhr.img.pending.push (xhr);
      xhr.number = nextNumberIssuing;
      xhrReg[xhr.number] = xhr;
      nextNumberIssuing++;      
      xhr.open ("POST", "extensions/Parsifal/endpoints/tex-preview.php", true);
      xhr.setRequestHeader ("Content-Type", "text/plain;charset=UTF-8");
      
      let para = {};
      let paraText = JSON.stringify (para);
      xhr.setRequestHeader ('X-Parsifal-Para', paraText);                 // parameters used in the tag as attributes   NONE is used in the preview   
      xhr.setRequestHeader ("X-Parsifal-Tag", uiChunks[i].tag);
      var iepc = document.getElementById ("inline-edit-preview-container");
      
      const AVAILABLE_PIXEL_WIDTH = iepc.clientWidth - 10;  
      // we report the number of pixels which are available for the preview to the server so that the server renders the required picture size.
      // 1) the server will calculate the scale and most of the time will send a picture which is 1 pixel larger due to rounding.
      // 2) the image has 4 pixels boundary left and right.
      // THE ISSUE is that sometomes we have and sometimes we do not have a scrollbar.
      // 
      // TODO: ??????????????????????????????????????? MOVE to  100vw  instead of 100% may help to cope with situations where we do not know scrollbar width.
      // As in https://stackoverflow.com/questions/3541863/css-100-width-but-avoid-scrollbar
      // 
      // 
      
      iepc.setAttribute ("data-available-width", AVAILABLE_PIXEL_WIDTH);    // just debug
      
      xhr.setRequestHeader ("X-Parsifal-Available-Pixel-Width", AVAILABLE_PIXEL_WIDTH);
      xhr.send ( body );
      
      console.time ( "server for chunk " + i + " request " + nextNumberIssuing );
      xhr.responseType = "blob";                                  // necessary to get response in blob form, needed vor ObjectURL creation !
      
      xhr.onload = (e) => {
        receivedEndpointResponse (e);
        uiChunks[i].implementedBody = uiChunks[i].todoBody;
        uiChunks[i].todoBody = undefined; 
      //  if (numOf ==1 ) uiChunks[i].img.scrollIntoView();          // if there is only one todo: scroll this one thing into view
        console.timeEnd ( "server for chunk " + i + " request " + nextNumberIssuing); 
      }  
    
    // when clicking on image select the text in the editor
    xhr.img.onclick = (e) => {
      synchPreviewToEditor (e.target.idx, chunks, uiChunks); 
      var ele = document.querySelector (".chunk-selected"); if (ele) {ele.classList.remove ("chunk-selected");}  
      e.target.classList.add("chunk-selected");
    }
    
      xhr.timeout = 12000; // TODO: move to configuration file
      xhr.ontimeout = () => { console.error ("The request for a preview timed out"); if (reject) {reject();}}   // TODO: FIX
      
    }
    else if (uiChunks[i].todoText) { // work on text portions
      if (VERBOSE) {console.log (`uiChunk ${i} has a todoText= ${uiChunks[i].todoText}`);}
      var newNode = makeTextCarrierNode ( uiChunks[i].todoText );  // make a new text carrier node and register it with uiChunks
      uiChunks[i].textCarrierNode.parentNode.replaceChild ( newNode, uiChunks[i].textCarrierNode);      
      uiChunks[i].textCarrierNode = newNode;   // register it with uiChunks
      uiChunks[i].implementedText = uiChunks[i].todoText;  // register the just implemented texta
      uiChunks[i].textCarrierNode.onclick = (e) => {
        synchPreviewToEditor (i, chunks, uiChunks);
        var ele = document.querySelector (".chunk-selected"); if (ele) {ele.classList.remove ("chunk-selected");}  
        e.target.classList.add("chunk-selected");
      }
    }
    else {
      if (VERBOSE) {console.log (`uiCchunk ${i} had neither todoBody nor todoText `);}
    }
  }

  // SYNCHRONIZE: selection from editor to preview
  if (typeof editBox.isInstrumented == "undefined") {   // do not set handlers twice
    editBox.addEventListener ("mouseup", (e) => {synchEditorToPreview (chunks, uiChunks); } ); 
    editBox.addEventListener ("keyup", (e) => {
      if ( ["ArrowDown", "ArrowLeft", "ArrowRight", "ArrowUp"].includes (e.key) ) {
        // check if user is selecting and if yes do not synch
        var sel = window.getSelection(); if (sel && sel.toString().length > 0) {return;}
        synchEditorToPreview (chunks, uiChunks);  
      }
    });
    editBox.isInstrumented = true;
  }
}


function synchEditorToPreview (chunks, uiChunks) {
  var pos   = getCursor (editBox);    console.log ("Cursor position in editbox reported at line: ", pos);
  var found = null;
  for (var i = 0; i < chunks.arr.length; i++) { if ( chunks.arr[i].start <= pos && pos <= chunks.arr[i].end ) { found = i; break;}} 
  // console.log ("cursor at", pos,  "index=", i, "start=", chunks.arr[i].start, "end=", chunks.arr[i].end, "tag=", chunks.arr[i].tag, "\n", chunks.arr[i].chunk); 
  var ele = document.querySelector (".chunk-selected"); if (ele) {ele.classList.remove ("chunk-selected");}                                         // unselect any other element
  if (uiChunks[found].img)             {uiChunks[found].img.classList.add ("chunk-selected"); uiChunks[found].img.scrollIntoView( {behavior:"smooth", block:"nearest"} );  }      // select the appropriate element
  if (uiChunks[found].textCarrierNode) {uiChunks[found].textCarrierNode.classList.add ("chunk-selected"); uiChunks[found].textCarrierNode.scrollIntoView( {behavior:"smooth", block:"nearest"} ); }
}


// TODO: in case the user writes stuff after or before the <amstag> or </amstag> we must raise an error messages
// TODO: in case the user writes  <amstex></amstex> in one line we also need an error message


function synchPreviewToEditor (idx, chunks, uiChunks) {
  var firstLine = chunks.arr[idx].start;
  var lastLine = chunks.arr[idx].end;
  
  if (chunks.arr[idx].tag != "TEXT") {firstLine++; lastLine--;}  // adjust: we will not select the tags themselves
  
  // now convert from line coordinate to character coordinate (for the start of the interval)
  var startChar = editBox.value.split ("\n",firstLine).join().length;  // detect position of the relevant \n
  if (startChar != 0) {startChar++;} // we do not want to start the selection with that \n, so add 1 (except for first line n entire universe which has no \n before it)
  
  // now convert from line coordinate to character coordinate (for the end of the interval)
  var endChar   = editBox.value.split ("\n",lastLine+1).join().length;  
  //console.log ("endchar is ", editBox.value[endChar], "pre: ", editBox.value[endChar-1], "post: ", editBox.value[endChar+1]);
  //console.log (`click on chunk number ${idx}, tag=${chunks.arr[idx].tag} start=${chunks.arr[idx].start}, end=${chunks.arr[idx].end}`);
  //console.log (`firstLine=${firstLine}, lastLine=${lastLine}, startChar=${startChar}, endChar=${endChar} `);
  
  editBox.focus ();                                                                            // must set focus first so that setSelectionRange works  
  editBox.setSelectionRange (startChar,endChar);
  
  // SCROLL the selection into view
  var linesOfBox = editBox.value.split ("\n").length;
  var pixPerLine = editBox.scrollHeight / linesOfBox;
  editBox.scrollTop = ((firstLine -4)* Math.floor(pixPerLine));
}



var editform;
var editBox;       // textarea of the Mediawiki edit page


var WORKER;

function initialize () {
  const VERBOSE = false;
  editform = document.getElementById ("editform"); 
  editBox  = document.getElementById ("wpTextbox1");
  if (editform && editBox) {                               // if we are in an edit context, attach an event listener and on every keyup event kickoff a process edit
    if (VERBOSE) {console.error ("Parsifal:helper.js: Detected an edit context, patching the Mediawiki edit page");}
    editform.addEventListener ("input", processEdit );     // install an event listener for changes in the textarea
  } 
  document.body.addEventListener ("keydown", (e) => {if (e.shiftKey && e.ctrlKey) {document.body.classList.toggle ("shift-down");}});  // install handler which hilites all img portions helpful when multiple portions are used
  
  initializeToc();  // hook into the modularized ToC functionality (see below or elsewhere)


  installDropZone (document.documentElement, dropHandler);
//  WORKER = new SharedWorker (CONFIG.JS_PATH + "/backJob.js");

}


const ERROR = ( ( ) => {
  /** MECHANISM for displaying an error text <x> to the user on the Mediawiki UI
   ** mode="Soft" or mode="Hard" informs on the error mode, see ENDPOINTS.md */
  const showError = (x, mode) => {
    console.error ("Parsifal:helper.js: showing Error:", x);
    var div = document.getElementById ("error-panel");
    if (!div) { div = document.createElement ("div"); div.setAttribute ("id", "error-panel"); div.className = "error-panel"; document.body.appendChild (div);}
    var node = document.createElement ("pre"); node.innerHTML = x; div.innerHTML = ""; div.appendChild (node);
  };
  
  const clearError = () => { var ele = document.getElementById ("error-panel"); if (ele) {ele.parentNode.removeChild (ele);} };
  
  return {showError, clearError};
})();




function showLogLinks (hash) {
  var div = document.getElementById ("log-links");
  if (!div) { div = document.createElement ("div"); div.setAttribute ("id", "log-links"); div.className = "log-links"; document.body.appendChild (div);}
  const logLink = `${CONFIG.HTML_URL}texLog.html?${hash}_pc_pdflatex`;   // link to the log
  const ErrorWidth = 1000;
  const left = window.screen.width - ErrorWidth;
  
  div.innerHTML = `<a href="${logLink}" 
    onclick="window.open('${logLink}', '_blank', 'location=yes,height=${window.screen.height},left=${left},width=${ErrorWidth},scrollbars=yes,status=yes');event.stopPropagation();event.preventDefault();"
    target='_new'>Log of TeX run</a>`;
}

function clearLogLinks () { var ele = document.getElementById ("log-links"); if (ele) {ele.parentNode.removeChild (ele);} }



// link together an annotation part and an img part of a hint feature
window.linkHint = function linkHint (id) {
  var aEle = document.querySelectorAll (`[data-id="a${id}"]`); // CAVE: we may have several link portions as link source area
  aEle.forEach (ele => {
    let imgEle = document.getElementById (`img${id}`);
    ele.addEventListener ("mouseover", (e) => {  
      console.log ("mousein " + e.target.innerHTML); e.stopPropagation();
      imgEle.timer = window.setTimeout ( ()=>{  
        console.log ("timer");
      imgEle.style.display= "block";
      
      // is the mouse event in the upper or in the lower part of the screen?
      
      var eTop = e.target.offsetTop; console.log ("etop = " + eTop);
      
      if (e.clientY < document.body.clientHeight / 2) {  // console.log (`we are in the upper part of the page`);
         Object.assign (imgEle.style, {left:"6px", top: (eTop+42)+"px"});      
      }
      else {Object.assign (imgEle.style, {left:"6px", top: (eTop-imgEle.clientHeight-10)+"px"}); }
      
      imgEle.classList.add ("showing")}, 300 );
    });
  ele.addEventListener ("mouseout", (e) => { console.log ("mouseout " + e.target.innerHTML);imgEle.style.display= "none";  imgEle.classList.remove ("showing"); window.clearTimeout (imgEle.timer);})  
});
}



/*** HELP SYSTEM ***/ // currently defunct
const show = (text) =>  {
  if (helpDiv === null) { // if the element does not exist, create it    
    helpDiv       = document.createElement("div"); 
    helpDiv.id    = "pdf-dualHelpSheet";
 //   helpDiv.style = HELP_STYLE;
    document.body.appendChild (helpDiv);
  }
  // element exists: fill in text and show
  helpDiv.style.height = (window.innerHeight - 50) + "px";
  helpDiv.style.maxWidth = (window.innerWidth /2) + "px";    
  // helpDiv.innerHTML = (text ? text : HELP_TEXT);  
  helpDiv.style.display = "block";
};

const hide = () => {if (helpDiv) {helpDiv.parentNode.removeChild (helpDiv); helpDiv = null;} }
  

/*** END help system code ***/ */


// service function for toggeling collapsible portions - TODO: not clear where this is used - we also rather have toggleNext
window.toggleMe = function toggleMe (e) {  
  $(e.target.nextSibling).toggle();  // toggle the visibility of the next sibling, which is the image to be shown
 }



// function for persisting the resize of the table of contents in localstore
function initializeToc () {  // initialize TOC functions - called by initialize in here
  const toggleMyToc = () => { const toc = document.getElementById ("toc"); toc.classList.toggle ("showtoc");}  // service function for toggeling the table of contents
  const initTocSize = () => { const toc = document.getElementById ("toc");
    var width = parseInt (localStorage.getItem ("tocWidth"));
    if (width) {toc.style.width = width + "px";}
    toc.style.display = "block";
  }
  
  // install handler for TOC only after DOMContentLoaded, only then the TOC is present in the DOM
  window.addEventListener('DOMContentLoaded', (event) => {
    var toc = document.getElementById ("toc");
    
     initTocSize ();
    new ResizeObserver( () => {
      localStorage.setItem ("tocWidth", parseInt (toc.style.width));
    } ).observe(toc);
    
    var ele = document.querySelector (".toctitle")  
    if (ele) {ele.addEventListener ("click", toggleMyToc); ele.setAttribute ("title", "Click to toggle visibility of table of contents"); }
  });
}


// copy the name of a named tag to the clipboard for reuse. shown next to the image
window.copyToClip = (e) => {
  var txt = event.target.dataset.name;
  navigator.clipboard.writeText(txt).then ( x => {console.log ("clipboard written: " + txt);}, (err) => {console.log ("clipboard error"); console.log (err);} );
};



initialize ();




//console.info ("Parsifal: helper.js has been loaded");
