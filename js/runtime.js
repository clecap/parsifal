
// console.error ("Parsifal runtime.js has started to load");

// the following is an already minified version of slidetoggle.js (see this directory, in package)
// we can use it as a drop-in replacement for slideToggle since we do not have a jquery instance in place in all occaions where we need the Parsifal runtime
!function(t,o){"object"==typeof exports&&"object"==typeof module?module.exports=o():"function"==typeof define&&define.amd?define([],o):"object"==typeof exports?exports.slidetoggle=o():t.slidetoggle=o()}(this,(function(){return(()=>{"use strict";var t,o,e,n={d:(t,o)=>{for(var e in o)n.o(o,e)&&!n.o(t,e)&&Object.defineProperty(t,e,{enumerable:!0,get:o[e]})},o:(t,o)=>Object.prototype.hasOwnProperty.call(t,o),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},r={};n.r(r),n.d(r,{hide:()=>u,show:()=>p,toggle:()=>f}),function(t){t.parseOrElse=function(t,o){return void 0===o&&(o="0"),t?parseInt(t):o&&"string"==typeof o?parseInt(o):0}}(t||(t={})),function(o){var e=function(t){return t instanceof HTMLElement};o.setStyles=function(t,o){Object.keys(o).map((function(e){t.style[e]=o[e]}))},o.getBoxStyles=function(o){var e=window.getComputedStyle(o);return{height:t.parseOrElse(e.height),padding:{top:t.parseOrElse(e.paddingTop),bottom:t.parseOrElse(e.paddingBottom)},border:{top:t.parseOrElse(e.borderTopWidth),bottom:t.parseOrElse(e.borderBottomWidth)}}},o.getElement=function(t){if(e(t))return t;var o=document.querySelector(t);if(e(o))return o;throw new Error("Your element does not exist in the DOM.")},o.setAttribute=function(t,o,e){t.setAttribute(o,e)},o.getAttribute=function(t,o){return t.getAttribute(o)}}(o||(o={})),function(t){t.on=function(t,o,e){return t.addEventListener(o,e),{destroy:function(){return t&&t.removeEventListener(o,e)}}}}(e||(e={}));var i,d,l=function(t,o){var e={};for(var n in t)Object.prototype.hasOwnProperty.call(t,n)&&o.indexOf(n)<0&&(e[n]=t[n]);if(null!=t&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(n=Object.getOwnPropertySymbols(t);r<n.length;r++)o.indexOf(n[r])<0&&Object.prototype.propertyIsEnumerable.call(t,n[r])&&(e[n[r]]=t[n[r]])}return e};!function(t){var n="data-slide-toggle",r=function(t){requestAnimationFrame(t)},i=function(t){var o=t.miliseconds,e=void 0===o?200:o,n=t.transitionFunction;return"all "+e+"ms "+(void 0===n?"linear":n)+" 0s"};t.shouldCollapse=function(t){if(!o.getAttribute(t,n)){var e=o.getBoxStyles(t).height;return e&&e>0}return"true"===o.getAttribute(t,n)},t.hide=function(t,d){var a;if(!function(t){return"false"===o.getAttribute(t,n)}(t)){null===(a=d.onAnimationStart)||void 0===a||a.call(d);var u=o.getBoxStyles(t),s=u.height,p=l(u,["height"]);o.setStyles(t,{transition:""}),r((function(){o.setStyles(t,{overflow:"hidden",height:s+"px",paddingTop:p.padding.top+"px",paddingBottom:p.padding.bottom+"px",borderTopWidth:p.border.top+"px",borderBottomWidth:p.border.bottom+"px",transition:i(d)}),r((function(){o.setStyles(t,{height:"0",paddingTop:"0",paddingBottom:"0",borderTopWidth:"0",borderBottomWidth:"0"});var n=e.on(t,"transitionend",(function(){var t;n.destroy(),null===(t=d.onAnimationEnd)||void 0===t||t.call(d)}))}))})),o.setAttribute(t,n,"false")}},t.show=function(t,d){var a;if(!function(t){return"true"===o.getAttribute(t,n)}(t)){var u=d.elementDisplayStyle,s=void 0===u?"block":u;null===(a=d.onAnimationStart)||void 0===a||a.call(d),o.setStyles(t,{transition:"",display:s,height:"auto",paddingTop:"",paddingBottom:"",borderTopWidth:"",borderBottomWidth:""});var p=o.getBoxStyles(t),c=p.height,f=l(p,["height"]);o.setStyles(t,{display:"none"}),r((function(){o.setStyles(t,{display:s,overflow:"hidden",height:"0",paddingTop:"0",paddingBottom:"0",borderTopWidth:"0",borderBottomWidth:"0",transition:i(d)}),r((function(){o.setStyles(t,{height:c+"px",paddingTop:f.padding.top+"px",paddingBottom:f.padding.bottom+"px",borderTopWidth:f.border.top+"px",borderBottomWidth:f.border.bottom+"px"});var n=e.on(t,"transitionend",(function(){var e;o.setStyles(t,{height:"",overflow:"",paddingTop:"",paddingBottom:"",borderTopWidth:"",borderBottomWidth:""}),n.destroy(),null===(e=d.onAnimationEnd)||void 0===e||e.call(d)}))}))})),o.setAttribute(t,n,"true")}}}(i||(i={})),function(t){t.on=function(t,o){i.hide(t,o)}}(d||(d={}));var a,u=function(t,e){d.on(o.getElement(t),e)};!function(t){t.on=function(t,o){i.show(t,o)}}(a||(a={}));var s,p=function(t,e){a.on(o.getElement(t),e)},c=function(){return(c=Object.assign||function(t){for(var o,e=1,n=arguments.length;e<n;e++)for(var r in o=arguments[e])Object.prototype.hasOwnProperty.call(o,r)&&(t[r]=o[r]);return t}).apply(this,arguments)};!function(t){var o=function(t){return function(){var o,e;null===(o=t.onClose)||void 0===o||o.call(t),null===(e=t.onAnimationEnd)||void 0===e||e.call(t)}},e=function(t){return function(){var o,e;null===(o=t.onOpen)||void 0===o||o.call(t),null===(e=t.onAnimationEnd)||void 0===e||e.call(t)}};t.on=function(t,n){i.shouldCollapse(t)?i.hide(t,c(c({},n),{onAnimationEnd:o(n)})):i.show(t,c(c({},n),{onAnimationEnd:e(n)}))}}(s||(s={}));var f=function(t,e){s.on(o.getElement(t),e)};return r})()}));
//# sourceMappingURL=slidetoggle.js.map


/* display an image only after a complete load: prevents that user sees partially loading image from a slow loading process; may be called in img tag of rendered html produced by PHP */



/*** COLLAPSIBLES of PARSIFAL ***/
// #region
// defines a function which is used by <script> tags injected in TexProcessor for rendering collapsibles
// called when user clicks on a collapsing button
window.toggleNext = function toggleNext (e) { 
  console.log ("parsifal: toggleNext");
  const button = e.target;
  const ele    = e.target.nextSibling;   
  $(ele).slideToggle(300);                               // slide open  // TODO: put to more prominent and thus better visible and adjustable place 

// trying to get it done without jquery since we do not have that loaded now !
 // ele.style="overflow: hidden; transition: all 300ms ease-in;"
 // if (ele.style.display=="none")  { ele.style.display="block"; ele.style.height = "100px";  }
 // if (ele.style.display=="block") { ele.style.height = "0px";}

  button.classList.toggle ("collapseButtonToggled");     // show button markup to better identify buttons of open areas
  if (!e.shiftKey) {                                     // no shift: close all the others
    $(".collapseResult").not(ele).hide();
    //let elems = document.querySelectorAll (".collapseResult"); for (var i = 0; i < elems.length; ++i) { if (elems[i] != ele ) { elems[i].style.display="none"; } }

    $(".collapseButtonToggled").not(button).removeClass ("collapseButtonToggled");
   // elems = document.querySelectorAll (".collapseButtonToggled"); for (var i = 0; i < elems.length; ++i) { elems[i].classList.remove ("collapseButtonToggled"); }

  }  
}



window.toggleImg = function toggleImg (e) { 
  const ele = e.currentTarget; 
  const button = e.currentTarget.previousSibling;
  console.log ("button: ", button); console.log ("element: ", ele);  
  $(ele).slideToggle(150);
  button.classList.toggle ("collapseButtonToggled");
}
// #endregion


window.PRT = PRT = (() => {

// what is our load path? necessary to have in the mediawiki and endpoint situations always the correct path available
let myPath=document.currentScript.src;
let lastIndex = myPath.lastIndexOf ("/");
myPath = myPath.substring (0,lastIndex);


// called to dynamically initialize image tags
// for every hash value there should be only one img tag, however in test pages we sometimes use the same text in several amsmath and so there might be
// several img tasg with the same id value - and we have to initalize all of them - that is why we do a loop over all #hash
const init = (hash, wgServer, wgScriptPath, cache_url) => {
  // let imgs = document.querySelectorAll ("#" + hash);     // in new version of chrome this produces a hard error since this is not a valid descriptor
  let imgs = [document.getElementById (hash)];
  const iniEle = (ele) => {  // function which is initializing an img element
    if (ele.hasAttribute ("data-error")) {} 
    else {Object.assign ( ele, {onerror: imageIsMissing, onload: showImage} );}
    let finalImgUrl3   = wgServer + wgScriptPath + cache_url + hash + "_pc_pdflatex_final_3.png"; 
    ele.setAttribute ("src", `${finalImgUrl3}`);
  };
  imgs.forEach (elem => iniEle (elem));
}



// TODO: currently this does not work exactly as needed

const imageIsMissing = (e) => { return;

  console.log ("image missing at: ", e);
  var src = e.target.src;
  console.log ("image src was: ", src);
  window.setTimeout ( () => {e.target.src = src;}, 5);
  return;

};


const showImage = (e) => {
  //console.warn ("-------- Parsifal runtime: showing image:", e.target.currentSrc, "width=", e.target.width, e.target,  " viewportwidth=", window.visualViewport.width, e);
  e.target.style.display = "inline-block";

}


const editPreviewPatch = () => {  // the clutch to PHP; we may adapat it to use CodeMirror, textarea or whatever client side editor we desire
  initializeTextarea();
  let params = (new URL (document.location)).searchParams;
  if (params.get("editormode") == "codemirror") {
    initializeCodeMirror ();  // additionally initialize a code mirror instance
  }
};

// function supporting debug and development of srcset feature
const srcDebug = (hash) => {

  window.onresize = () => {
    console.warn ("----Parsifal runtime debug: resizes of window to viewportwidth=", window.visualViewport.width);
    let elems = document.querySelectorAll (".texImage");
    elems.forEach ( ele=> {
        console.warn ("----Parsifal runtime debug: ", "src=", ele.src, "currentStrc=", ele.currentSrc, "width=", ele.width);
    });
  }

};


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
      if (VERBOSE) {console.log ("Parsifal Runtime: resize observer storing textarea dimensions ");}
      //window.localStorage.setItem ("textareaWidth", textarea.offsetWidth);
      //window.localStorage.setItem ("textareaHeight", textarea.offsetHeight);
    } 
    waitBeforeInvoke (300);  // invoke a redisplay of the preview after some waiting time
    if (status == 1) { previewContainer.style.height = "" + (newEditContainer.clientHeight - wrapper.clientHeight) + "px" };
  };

  new ResizeObserver (wasResized).observe (cmElement);          // NEW: cmElement
  new ResizeObserver (wasResized).observe (document.body);
}




let pdfJsLib          = null;    // handle to the pdfJsLib library
let pdfJsLibRequested = false;   // has the pdfJsLib library already been requested

// promises to return a reference to pdfJsLib
const getPdfJsLib = () => {
  if (pdfJsLib) {return Promise.resolve (pdfJsLib);}
  return new Promise ( (resolve, reject) => {
    var script = document.createElement("script");
    script.onload = () => { 
      pdfJsLib = window['pdfjs-dist/build/pdf'];
      pdfJsLib.GlobalWorkerOptions.workerSrc = myPath + "/../vendor/pdfJs/pdf.worker.min.js";  
      resolve ( pdfJsLib ); };
    script.src = myPath + "/../vendor/pdfJs/pdf.min.js";
    document.body.appendChild (script);
    pdfJsLibRequested = true;
  });
};

// API call function: promises to render the PDF connected with hash asap
const renderPDF = async ( url, hash ) => {
  let pdfJsLibRef  = await getPdfJsLib();

  let loadingTask =  await pdfjsLib.getDocument (url);
  let pdf          = await loadingTask.promise;
  let page         = await pdf.getPage(1);
 
  let scale = 4;
  let viewport = page.getViewport ( { scale: scale } );
  let outputScale = window.devicePixelRatio || 1;   // Support HiDPI-screens

  outputScale = outputScale;

  let canvas  = document.getElementById('canvas-' + hash);
  let context = canvas.getContext('2d');
  canvas.width       = Math.floor(viewport.width * outputScale);
  canvas.height      = Math.floor(viewport.height * outputScale);

  console.log ("Parsifal runtime renderPdf size:  canvas size=" + canvas.width + "x" + canvas.height + "   canvas style size=" + Math.floor(viewport.width) + "x" + Math.floor(viewport.height) );

  Object.assign (canvas.style, {width: Math.floor(viewport.width) + "px", height: Math.floor(viewport.height) + "px" } );

  var transform = (outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null);
  transform = null;  // TODO ??
  let renderContext = { canvasContext: context,  transform: transform,  viewport: viewport };
  let job = await page.render(renderContext);
  return job;
};




const jsRender = (hash, width, height, scale, titelInfo, wgServer, wgScriptPath, CACHE_URL) => {
  var basis  = wgServer +  wgScriptPath + "/extensions/Parsifal/html/pdfIframe.html";    // path to the html page generating canvas 
  var url    = wgServer +  wgScriptPath + CACHE_URL + hash + "_pc_pdflatex.pdf";
  var urlSearch = "url=" + encodeURIComponent (url);
  var urlScale  = "scale=" + encodeURIComponent (scale);
  var urlInfo   = "info=true";
  var urlHash   = "hash=" + encodeURIComponent (hash);
  var iframeUrl = basis + "?" + urlSearch + "&" + urlScale + "&" + urlInfo + "&" + urlHash;

//  $style = "max-width:100%;" . "width:".$width."px; height:".$height."px; border:1px solid green;";

  width += 172;  // 62  // TODO: WHAT IS THIS ??

  var style = "max-width:100%;" + "width:" + width + "px; height:" +  height + "; border:1px solid red; overflow:hidden; ";

//  $style = "max-width:100%; width:100%; border:1px solid green;";

  var infoLine = `<div>TexProcessor.php: Infoline: iframe is: width=${width}  height=${height}</div>`;

  var html = infoLine + "<iframe   scrolling='no'     style='" + style + "' src='" + iframeUrl + "'  id='iframe-" + hash + "' title='" + hash + "' ></iframe>";
  
  console.warn (html);
  document.write (html);
  return html;
}


const toggleLimitSize = () => {
  let sizeLimit = window.localStorage.getItem ("Parsifal-size-limit") === "true";  
  limitSize ( !sizeLimit);
};

const limitSize = (flag) => { 
  window.localStorage.setItem ("Parsifal-size-limit", (flag ? "true" : "false"));
  implementLimitedSize();
};

const implementLimitedSize = () => {
  let sizeLimit = window.localStorage.getItem ("Parsifal-size-limit") === "true";  
  if (sizeLimit) { document.documentElement.classList.add     ("sizeLimit"); }
  else           { document.documentElement.classList.remove  ("sizeLimit");}
};



const toggleVariants = () => {
  let showingVariants= window.localStorage.getItem ("Parsifal-show-variants") === "true";  
  showVariants ( !showingVariants);
};

const showVariants = (flag) => { 
  window.localStorage.setItem ("Parsifal-show-variants", (flag ? "true" : "false"));
  implementShowVariants();
};

const implementShowVariants = () => {
  let showingVariants = window.localStorage.getItem ("Parsifal-show-variants") === "true";  
  if (showingVariants) { document.documentElement.classList.add     ("showVariants"); }
  else                 { document.documentElement.classList.remove  ("showVariants");}
};


// patches the edit section links before a parsifal container
// called for every parsifal container via php injected script tag line
const patchParsifalEditLinks = (sc) => {
  //console.info ("runtime.js: patchParsifalEditLinks: ", sc);
  var pc       = sc.previousSibling;
  //console.info ("parsifalContainer: ", pc);

  pc.addEventListener ("mouseenter", (e) => { if (e.shiftKey) {pc.style.outline = "1px dotted grey";} } );
  pc.addEventListener ("mouseleave", (e) => { pc.style.outline = ""; } );
  return;


  
    var hElement = pc.previousSibling;
    while ( hElement && !["H1","H2","H3","H4","H5","H6"].includes (hElement.tagName)) { hElement = hElement.previousSibling;}
    //console.info ("h-element", hElement);
    var err = pc.querySelector (".errorWrap");
    //console.info ("err", err);
    var es = hElement.querySelector (".mw-editsection");
    //console.info ("editsection", es);
    es.parentNode.removeChild (es);
    err.appendChild (es);
};


// when this function is called on an element ele then the hash id of the image contained in this element
// is broadcast 
const broadcastPosition = (ele) => {
  let danteBC = new BroadcastChannel ("danteBC" );
  let img = ele.querySelector ("img.texImage");
  let id  = img.id; 
  danteBC.postMessage (  {"positionAtId": id } );  
};



const showAsIframe = (url) => {   console.log (" show " + url + " as iframe inside of the current window", url);
  let frame =  document.getElementById ("errorIframe");
  console.warn (frame);
  if (frame) {return;}  // already showing
  frame = document.createElement ("iframe");
  frame.src=url;
  url.parentNode.style.display="none";
  frame.id="errorIframe";
  frame.style = "position:fixed; top:10px; left:10px; width:800px; height:800px; overflow:scroll;background-color:white;";
  document.body.appendChild (frame);
};

const showAsWin = (url) => {   console.log (" show " + url + " as iframe inside of the current window");
  url.parentNode.style.display="none";
  const handle = window.open( url, "errorWindow", "left=1,top=1,width=800,height=800");
};

const hilite = (hash) => {
  let ele = document.getElementById (hash);
  ele.style.outline = "1px dotted lightgrey";

};


const lowlite = (hash) => {
  let ele = document.getElementById (hash);
  ele.style.outline = "0px solid red";

};


return ( {imageIsMissing, renderPDF, jsRender, showImage, srcDebug, init, 
         limitSize, implementLimitedSize, toggleLimitSize,
         showVariants, implementShowVariants, toggleVariants, showAsIframe, showAsWin, hilite, lowlite,
         patchParsifalEditLinks});    // export functions to the PRT Parsifal Run Time object

})();  // END of object PRT definition


// code to debug the multiple load problem (which now seems to be solved)
// if (typeof window.PRTLC === "undefined") {console.error ("first time loaded"); window.PRTLC = 1;} else {console.error ("multiple times loaded - why??");}

PRT.implementLimitedSize();

PRT.implementShowVariants();

// console.error ("Parsifal runtime.js has loaded successfully");
