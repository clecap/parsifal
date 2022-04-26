The extension fires on edit / save (in the parser).

Solution: If we PURGE the page then mediawiki redoes the edit cycle itself. So purge with php maintenance script or in cron or via
an extension which does that via an admin button.


## Design Goals


* GOAL 1: SPEED
* The render function is called, when the production of the rendering is not found in the Mediawiki cache system. 
* This is the case upon the first save of an article. Since this is called frequently while working on a page, it is imperative to keep the
* time spent in this function short, even if this comes at the price of having a worse page quality during this phase of working on then
* article.
* 
* GOAL 2: EVENTUAL PERFECTION
* If we accept a worse page quality to buy some speed during the edit circle we nevertheless need an automatism which eventually AND
* without additional user intervention guarantees the perfect quality to show up on the page.
* 
* GOAL 3: FAULT TOLERANCE
* There may be reasons why eventually the off-database resources get destroyed. This should be detected and healled automagically.
* 
* IMPLEMENTATION of GOAL 2 (EVENTUAL PERFECTION)
* We detect in the render function whether the optimal quality resources are in place already. If not, then we 1) mark the page as
* not cached (this guarantees calling the renderer again when the next time the page is viewed - and then this can be corrected).
* However we can quickly follow through in the first call by using those resources we already should have from edit preview.
* And 2) we kick off a mechanism for obtaining the missing resources by a process which we send into the background.
* 
* TESTS on TIMING on a reasonably sized chunk
* THUS for preview the DVI-PNG path is the best - and we should also pick up the size from the PNG file from the preview


### Tasks

We need to
* convert the input to TeX code, adding template and/or precompilation information
* convert TeX to DVI and/or PDF 
* convert DVI and/or PDF to PNG
* obtain bounding box information 
* crop the PNG, if not already done by the tool chain  
* generate the HTML code for the annotation layer   


### Timing

* ghostscript seems to be pretty slow
* mutools seem to be quite fast 


## File Cache Architecture


## Coding Alternative

We could also code this in a way where we first determine all data necessary for the Wiki to Html translation and only lateron generate 
files which are necessary. For example, the HTML based annotation layer could be generated later.

We do not do this now, since we found a fast way to generate both at the same time.

However, a working code portion for this concept would be:

```
if ($areWeDone) { // all compilations we eventually might require have now run
  $version="final";
}
else {  
   //self::debugLog (" WE ARE STILL MISSING stuff for $hash, setting cache expiry to 0\n");                                                                                  // we still lack PNG or HTML, but can move ahead since we know the hash of the TeX source
   $version="prelim";                                                                     // mark the image as preliminary (this is then picked up in style file latex.css via data-prelim)
   $parser->getOutput()->updateCacheExpiry(0); $wgOut->enableClientCache( false );        // disallow caching for this version; this ensures we trigger another call of the parser upon next display (where then this issue will be fixed)
//    self::completeInBackground ($hash);                                                   // kick off completion of all details which then will be available and used upon the next display
}
 if ($VERBOSE) {self::debugLog ("  cache expiry time: " . $parser->getOutput()->getCacheExpiry(). "\n" );}
``` 


## Library Access 

### Goals 

* We want to connect Mediawiki texts directly to references.
** Example 1: We have a DoI 
** Example 2: We have a URL 
** Example 3: We have a file and are allowed to provide it to others
** Example 4: We have a file and are not allowed to provide it to others 


### Use Case:

* When writing, we are likely to have the file open.
* We drop the file on a specific area of the edit page
** We get the SHA-1 hash, copyable to clipboard.
** Optional: We get the file displayed. 
** We register file meta data in a database, if not yet done already.
** We store the meta information in the database  

### Database

### References*
*  https://www.mediawiki.org/wiki/Manual:Schema_changes



## Export / Import / Pull / Push / Cherrypick 


References:
* https://github.com/wikimedia/mediawiki-extensions-Push/










