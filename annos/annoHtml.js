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
const pdfjsLib = require ("pdfjs-dist/legacy/build/pdf.js");
const fs       = require ("fs");

console.log (process.argv);

/** CALL interface
 *    process.argv[2]   path of pdf file, starting from 
 *    process.argv[3]   path of html file
 *    process.argv[4]   scale factor for html annotations
 *    process.argv[5]   horizontal delta
 *    process.argv[6]   vertical delta
*/

var pdfFileName  = process.argv[2];
var htmlFileName = process.argv[3];
var htmlScale    = parseFloat (process.argv[4]);
var horizontalDelta = parseFloat (process.argv[5]);
var verticalDelta = parseFloat (process.argv[56]);

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

      let htmlTxt = "";
      // construct the html file with the annotations
      for (let j=0; j < annos.length; j++) {
        if ( !annos[j].url && !annos[j].unsafeUrl) {console.log ("skipping anno " + j); 
          // console.log (annos[j].url);
          continue;} else {console.log ("processing anno "  + j + "  "  + annos[j].url );}
        let geo = annoGeometry (annos[j], page, svport);
        
//          var newLeft = geo.left; 
        var newLeft = (parseInt (geo.left) - horizontalDelta ) + "px";
          
 //         var newTop =  geo.top; 
          var newTop = (parseInt (geo.top) - verticalDelta ) + "px";          
          
        console.log (`annotation geo object  orig: left=${geo.left} top=${geo.top}   new: left ${newLeft}  top ${newTop}`);
        geo.left = newLeft;
        geo.top  = newTop;
          
        let href = (annos[j].url || annos[j].unsafeUrl);
        href = href.replace('.pdf#[0,{"name":"Fit"}]','');
        href = href.replace (/ /g,"_");
          
        htmlTxt += `<a style='position: absolute; cursor:pointer;left:${geo.left};top:${geo.top};width:${geo.width};height:${geo.height}' class='pdf-external-anchor' `;
        htmlTxt += " href='"+  href + "' title='" + "Open " + annos[j].url + " in new tab or window" +   "' ></a>";
      }
      fs.writeFile (htmlFileName, htmlTxt, function (error) {if (error) {console.error("Error: " + error);} else {console.log("Converted HTML");} } );
  
   }
});
