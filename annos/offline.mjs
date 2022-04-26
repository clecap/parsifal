/**
 *  This file is an offline driver for Parsifal
 *  It takes as argument a filename in the local file system or a URL in the web.
 *  It produces as output for every page in the PDF document a png file and an html fragment file.
 *
 *  Author and Copyright: (C) Clemens H. Cap 2021
 *  License: GNU Affero 3.0
 */

// generate a require
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

// imports
const Canvas   = require ("canvas");
const assert   = require ("assert").strict;
const pdfjsLib = require ("pdfjs-dist/legacy/build/pdf.js");
const fs       = require ("fs");

const CROP = require ("png-crop");

console.log (process.argv);

/** CALL interface
 *    process.argv[2]   path of pdf file, starting from 
 *    process.argv[3]   path of png file
 *    process.argv[4]   path of html file
 *    process.argv[5]   scale factor for pngScale, if equal to 0 do not produce a png
 *    process.argv[6]   scale factor for html annotations, if equal to 0 do not produce a html
*/

var pdfFileName  = process.argv[2];
var pngFileName  = process.argv[3];
var htmlFileName = process.argv[4];
var pngScale     = parseFloat (process.argv[5]);  
var htmlScale    = parseFloat (process.argv[6]);



function NodeCanvasFactory() {}

NodeCanvasFactory.prototype.create = function NodeCanvasFactory_create(width, height) {
  assert(width > 0 && height > 0, "Invalid canvas size: width and height must be > 0 but width="+width+ " height="+height );
  const canvas  = Canvas.createCanvas(width, height);
  const context = canvas.getContext("2d");
  return { canvas, context};
};

NodeCanvasFactory.prototype.reset = function NodeCanvasFactory_reset(canvasAndContext, width, height) {
  assert(canvasAndContext.canvas, "Canvas is not specified");
  assert(width > 0 && height > 0, "Invalid canvas size");
  canvasAndContext.canvas.width = width;
  canvasAndContext.canvas.height = height;
};

NodeCanvasFactory.prototype.destroy = function NodeCanvasFactory_destroy(canvasAndContext) {
  assert(canvasAndContext.canvas, "Canvas is not specified");

  // Zeroing the width and height cause Firefox to release graphics
  // resources immediately, which can greatly reduce memory consumption.
  canvasAndContext.canvas.width = 0;
  canvasAndContext.canvas.height = 0;
  canvasAndContext.canvas = null;
  canvasAndContext.context = null;
};



// Some PDFs need external cmaps.
const CMAP_URL    = "node_modules/pdfjs-dist/cmaps/";
const CMAP_PACKED = true;
const STANDARD_FONT_DATA_URL =  "node_modules/pdfjs-dist/standard_fonts/";

// Loading file from file system into typed array.
const pdfPath = pdfFileName;
const data    = new Uint8Array(fs.readFileSync(pdfPath));

// Load the PDF file.
const loadingTask = pdfjsLib.getDocument({ data, cMapUrl: CMAP_URL, cMapPacked: CMAP_PACKED, standardFontDataUrl: STANDARD_FONT_DATA_URL });

// DUPLICATE from pdfAnnoProc.js is NOT DRY !
var annoGeometry = (data, page, svport, delta) => {
  const VERBOSE = false;
  if (delta === null || delta === undefined) {delta = 0;}
  if (typeof delta == "number") {delta = {top: delta, bottom: delta, left: delta, right: delta};}
  
  var view            = page.view;
  var diff            = [ data.rect[0], view[3] - data.rect[1] + view[1], data.rect[2], view[3] - data.rect[3] + view[1]];
  var norm            = pdfjsLib.Util.normalizeRect( diff );
  var transformArray  = svport.transform;                                // calculate transformation matrix belonging to the scaled pdf.js 
  var transform       = 'matrix(' + transformArray.join(',') + ')';      // convert the transformation matrix into CSS string style
  var transformOrigin =  (-norm[0]) + 'px ' +  (-norm[1]) + 'px';        // generate CSS string specification for the origin of the transform
  var obj             =  {left: (norm[0] - delta.left) + 'px', top: (norm[1] - delta.top) + 'px', width: (norm[2]-norm[0] + (delta.left+delta.right)/svport.scale) + "px", height: (norm[3]-norm[1] + (delta.top+delta.bottom)/svport.scale) + "px", transform, transformOrigin};

  if (VERBOSE) {
    console.log ("------------ annoGeometry");
    console.log ("view element    of page:           ", view);
    console.log ("scaled viewport of page:           ", svport);
    console.log ("rectangle info  of anno:           ", data.rect);
    console.log ("diff info:                         ", diff);
    console.log ("normalized diff info:              ", norm);
    console.log ("transformation array:              ", transformArray);
    console.log ("transformation origin:             ", transformOrigin);
  }
  

  var obj2 = {left: (Math.ceil((svport.scale*norm[0]))-delta.left) + 'px', top: (Math.ceil((svport.scale*norm[1]))-delta.top ) + 'px', 
    width: (Math.ceil((norm[2]-norm[0])*svport.scale) + delta.left + delta.right) + 'px',  height: (Math.ceil((norm[3]-norm[1])*svport.scale) + delta.top + delta.bottom ) + 'px'}

  //console.log (obj2);

  return obj2;
};




function probeCol (canAC, x, yFrom, yTo) {
  var context = canAC.context;  
  let data =  context.getImageData (x, yFrom, 1, yTo-yFrom).data;  //context.getImageData (right, top, 1, bottom-top).data;


  var rmax, gmax, bmax, amax;
  var rmin, gmin, bmin, amin;
  rmax = gmax = bmax = amax = 0;
  rmin = gmin =bmin = amin = 300;

  for (var i=0; i < data.length ; i+=4) {
    if (data[i] < rmin) {rmin = data[i];}
    if (data[i+1] < gmin) {gmin = data[i+1];}    
    if (data[i+2] < bmin) {bmin = data[i+2];}    
    if (data[i+3] < amin) {amin = data[i+3];}
    if (data[i] > rmax) {rmax = data[i];}
    if (data[i+1] > gmax) {gmax = data[i+1];}
    if (data[i+2] > bmax) {bmax = data[i+2];}
    if (data[i+3] > amax) {amax = data[i+3];}                    
    
  //  if (true || data[i] != 255 || data[i+1] != 255 || data [i+2] != 255 || data[i+3] != 255)  console.log ("FFFFFFFFF", i, data [i], data[i+1], data[i+3], data[i+3]); 
  }
  
  var max = Math.max (...data);
  var min = Math.min (...data);
  console.log (`Column at x=${x} from y=${yFrom} to y=${yTo} has ${data.length} entries MIN=${min}  MAX=${max}  ${rmin} ${gmin} ${bmin} ${amin}  ${rmax} ${gmax} ${bmax} ${amax}`);  
}





// try to get a minimal tight crop around what we have in the 
// // stolen from here:   https://github.com/mozilla/pdf.js/issues/2288
function cropCheck (canAC, width, height) {
  
  function isSingleColor(imageData, flag) {
    let stride = 4
    for (let offset = 0; offset < 1; offset++) {
      let first = imageData[offset];
    //  if (flag) {console.log ("first = ", first);}
      for (let i = offset; i < imageData.length; i+=stride) {
    //    if (flag) {console.log ("offset=", offset, " i=",i," data=",imageData[i]);}
        if (first !== imageData[i]) {return false;} }
    }
    return true;
  }

  let top = 0;
  let bottom = height-1;
  let left = 0;  let right = width-1;
  console.log ("before crop: ", top, bottom, left, right);

 var context = canAC.context;
 
 // getImageData (x, y, width, height)
  while (top < bottom) {let data = context.getImageData(left, top, right - left, 1).data;     if (!isSingleColor(data)) { break; } top++; }
  while (top < bottom) {let data = context.getImageData(left, bottom, right - left, 1).data;  if (!isSingleColor(data)) { break; } bottom--;}
  while (left < right) {let data = context.getImageData(left, top, 1, bottom - top).data;     if (!isSingleColor(data)) { break; } left++;}
  while (left < right) {let data = context.getImageData(right, top, 1, bottom - top).data;    
   // console.log (`SAMPLE TAKEN right=${right}  top=${top} ${1}  height=${bottom-top}`);  
    if (!isSingleColor(data, true)) { break; } right--;}

 // console.log (`after crop: top=${top} bottom=${bottom} left=${left} right=${right}`);
  return {top, bottom, left, right};
}



loadingTask.promise
  .then(async function (pdfDocument) {
    var VERBOSE = true;
    if (VERBOSE) {console.log(`PDF document loaded successfully and has ${pdfDocument.numPages} pages`);}

    for (let i = 1; i < 2 /* pdfDocument.numPages */; i++) {
      if (VERBOSE) {console.log (`now getting page ${i}`);}
      var page = await pdfDocument.getPage(i);
      if (VERBOSE) {console.log ("got page ", i);}
      
      const svport            = page.getViewport( {scale: htmlScale} );  
      console.log ("viewport obtained is: ", svport);

      
      const annos = await page.getAnnotations();
      console.log ("got annotations: " + annos.length);
      
      // render the PDF to a png
      const pngViewport         = page.getViewport({ scale: pngScale });
      const canvasFactory    = new NodeCanvasFactory();
          
      const canvasAndContext = canvasFactory.create(pngViewport.width, pngViewport.height);
      console.log ("png canvas size: " + pngViewport.width + "  " + pngViewport.height);
      
      const renderContext    = {canvasContext: canvasAndContext.context, viewport: pngViewport, canvasFactory, };
      const renderTask       = page.render(renderContext);
      
      renderTask.promise.then ( () => {
        console.log ("rendertask completed ", i);  
        
        var box = cropCheck (canvasAndContext, pngViewport.width, pngViewport.height);   // occasionally throws core 
        console.log ("past cropCheck ", i);        
        const image = canvasAndContext.canvas.toBuffer();
        console.log (`Cropping detector suggests: left=${box.left} right=${box.right} WIDTH=${box.right-box.left}  top=${box.top}  bot=${box.bottom}  height=${box.bottom-box.top}`);
        CROP.cropToStream(image, {top:box.top, left:box.left, width: box.right-box.left, height:box.bottom-box.top}, function(err, outputStream) {
          if (err) throw err;
          outputStream.pipe(fs.createWriteStream(pngFileName));
        } );
      
        // construct the html file with the annotations
        let htmlTxt =  "";  
        let lastHtml = "";  // the last portion to be added (helpful for z index) 
        let scripts  = "";  // the scripts at the very end !
        let arr = [];     // registers the references so that we only have ONE image event if it is referenced several times
        let comments =  `<!-- Found ${annos.length} annotations \n`   
        for (let j=0; j < annos.length; j++) {
          comments += ` anno ${j} has subtype ${annos[j].subtype} \n`;
          
          if ( !annos[j].url && !annos[j].unsafeUrl ) { comments += ` anno ${j} skipped since it has no url and no unsafeUrl \n`;  continue; } 
          
          let geo = annoGeometry (annos[j], page, svport);
          geo.left = (parseInt (geo.left) - box.left ) + "px";
          geo.top = (parseInt (geo.top) - box.top ) + "px";  
          var geoStyle =  `position:absolute;left:${geo.left};top:${geo.top};width:${geo.width};height:${geo.height};`;              
          
          if ( annos[j].unsafeUrl && annos[j].unsafeUrl.startsWith ("frame:")) {
            var localSrc = annos[j].unsafeUrl.replace ("frame:","");
            
            // CAVE: we may have several PDF annotation areas having the same URL (set in several places by the author or links broken into two lines by Latex)
            // TODO!!!!!!!!!!!!!!!! we only want ONE copy of the image !!!   
            htmlTxt += `<a data-id='a${localSrc}' style='${geoStyle}cursor:pointer;' class='pdf-external-hint' href='${localSrc}' title='Show hint ${localSrc}' ></a>`;
            if (!arr.includes (localSrc)) {
              lastHtml += `<img id='img${localSrc}' src='${localSrc}' class='img-hint' style='display:none;position:absolute;'/>`;
              scripts += `<script>linkHint('${localSrc}' )</script>`;
              arr.push (localSrc);
            }  
          }
          else {
          
          
          let href = (annos[j].url || annos[j].unsafeUrl);
          href = href.replace('.pdf#[0,{"name":"Fit"}]','');
          href = href.replace (/ /g,"_");
          
          htmlTxt += `<a style='${geoStyle}cursor:pointer;' class='pdf-external-anchor' href='${href}' title='Open ${annos[j].url} / ${annos[j].unsafeUrl} in new tab or window' ></a>`;
        }
        } // end for loop
        comments += "\n -->";
        fs.writeFile (htmlFileName, comments + htmlTxt + lastHtml + scripts, function (error) {if (error) {console.error("Error: " + error);} else {console.log("Converted HTML");} } );
      
      }) // end promise.then 
      .catch(function (reason) {console.log(reason);});
   }
});

