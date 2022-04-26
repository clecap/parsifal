<?php

/*** NOTE: This config.php file MUST contain only const declarations and no declarations of global variables 
 *   This is due to a number of issues with PHP design where 1) const cannot be used in string interpolation 
 *   and 2) const definitions are not allowed to contain (most) operations and 3) the peculiar way we neeed this 
 *   file in mediawiki as well as in our own endpoint files.
*/

/* *** ADDING another xml tag ***

1) Parsifal.php  for the hooks
2) TexProcessor.php  for the mapping

The place where we want to insert the xml tag content:
  magic-line-identifier-for-inclusion

When we use a preloaded format we must use the correct versions(one for latex, one for pdflatex).
We do so by placing the formats into different format directories and use different environment variables.
*/

/*** THESE THINGS must be adapted to the platform on which we are installing */

const GHOSTSCRIPT       = "/usr/bin/gs";                       // path to the ghostscript executable: determine by which gs
const MUTOOL            = "/usr/bin/mutool";                   // path to the mutool executable:      determine by which mutool
const NODE_BINARY       = "/usr/local/bin/node";               // path to the node executable:        determine by which node


///////////////////// TODO: update the paths to the new installation

const DVIPNG_BINARY     = "/usr/local/texlive/2021/bin/x86_64-linux/dvipng";                   // path to the dvipng executable:      determine by which dvipng
const LATEX_BINARY      = "latex";
const PDFLATEX_BINARY   = "pdflatex";                 // path to pdflatex executable;


/* Regular expressions */
// for detecting a line which starts or ends a Latex text portion */
/* ensure that the tags are prefix free (so no tex and textest at the same time */
const TAG_REGEX_START_STRING = "<amsmath|<large:amsmath|<pre-amsmath|<tex|<pdftex";
const TAG_REGEX_END_STRING   = "<\/amsmath>|<\/large:amsmath>|<\/pre-amsmath>|<\/tex>|<\/tex>";


const BASIC_SIZE = 1000;


const TAGS = array("amsmath", "tex");


const MAGIC_LINE         =  "magic-line-identifier-for-inclusion";                     // this line in the latex template is replaced by the content between the tags

/** IMPORTANT: MUST HAVE SAME VALUE AS IN CONFIG.JS */
const ERROR_PARSER_START = "START-MARKER-TYPED-OUT-FOR-ERROR-PARSER";
const ERROR_PARSER_END   = "END-MARKER-TYPED-OUT-FOR-ERROR-PARSER";

/* MAP the tag to the total width used in the template. BE SURE TO INCLUDE MARGINS USED on BOTH SIDES */
const TAG2WIDTHinCM = array (
  "amsmath" => 15.14,
  "tex"     => 15.14
);



 
// minipage is to hold the page width stable 

const PC_LATEX_ADDON = array (
  "amsmath" => "\\begin{document}\\begin{minipage}{15cm}\myInitialize\\relax ".MAGIC_LINE."\\end{minipage}\\end{document}",
  "tex"     => "\\begin{document}\\begin{minipage}{15cm}\myInitialize\\relax ".MAGIC_LINE."\\end{minipage}\\end{document}"
);

const PC_PDFLATEX_ADDON = array (
  "amsmath" => "\\begin{document}\\begin{minipage}{15cm}\myInitialize\\relax ".MAGIC_LINE."\\end{minipage}\\end{document}",
  "tex"     => "\\begin{document}\\begin{minipage}{15cm}\myInitialize\\relax ".MAGIC_LINE."\\end{minipage}\\end{document}"
);





/** PATH must include normal Linux path (such as sed, uname, mkdir, rm and possibly more) and path to pdflatex */
const PATH     = "/usr/local/bin:/usr/bin:/bin:/usr/local/texlive/2021/bin/x86_64-linux";
const HOME        = "/var/www";



/** ENVIRONMENT as it was used in the TeXLive installer **/
/** ONLY Modification:  The ~ has been replaced by  /var/www  which is the home directory of user www-data */
const TEXDIR          = "/usr/local/texlive/2021";                    // main TeX directory
const TEXMFLOCAL      = "/usr/local/texlive/texmf-local";             // directory for site-wide local files
const TEXMFSYSVAR     = "/usr/local/texlive/2021/texmf-var";          // directory for variable and automatically generated data
const TEXMFSYSCONFIG  = "/usr/local/texlive/2021/texmf-config";       // directory for local config
const TEXMFVAR        = "/var/www/.texlive2021/texmf-var";                   // personal directory for variable and automatically generated data
const TEXMFCONFIG     = "/var/www/.texlive2021/texmf-config";                // personal directory for local config
const TEXMFHOME       = "/var/www/texmf";                                    // directory for user-specific files

const TEXINPUTS       = __DIR__."/local:";                           // directory to search for TeX input files  : is imperative to add the path to the standard classes  
/*** END ENVIRONMENT ***/


const PREFIX_ERROR = "max_print_line=10000  error_line=254  half_error_line=238";  // prefix for generating reasonable error file format; see https://tex.stackexchange.com/questions/52988/avoid-linebreaks-in-latex-console-log-output-or-increase-columns-in-terminal?rq=1

const LATEX_COMMAND    =  PREFIX_ERROR . " " . LATEX_BINARY . "  --interaction=nonstopmode  -file-line-error-style  "; 
const PDFLATEX_COMMAND =  PREFIX_ERROR . " ". PDFLATEX_BINARY . " --interaction=nonstopmode -file-line-error-style ";

const NODE_SCRIPT      = __DIR__."/annos/offline.mjs";                            // path to the offline.mjs script
const PDF2HTML              = NODE_BINARY." ".__DIR__."/annos/annoHtml.js ";

/*** System dependent components used in the filesystem path for php ***/
const TEMPLATE_PATH        =  __DIR__."/template/";                 // path to the template directory, relative to the installation path $IP  must end with a slash
const LATEX_FORMAT_PATH    =  __DIR__."/formats_latex/";     
const PDFLATEX_FORMAT_PATH =  __DIR__."/formats_pdflatex/";     

/*** The path for the cache ***/
const CACHE_PATH    =  __DIR__."/tmpa/";                                             // path to the temporary cache directory, relative to the installation path $IP  must end with a slash
$CACHE_PATH         = CACHE_PATH;                                                    // moving this from a const to a $ since PHP disallows consts in string extrapolations
const CACHE_URL     = "/extensions/Parsifal/tmpa/";                                  // URL relative to scriptPath, MUST end on /  // WAS:    /extensions/Parsifal/tmp/


/*** Some Log Paths ***/
const LOG_PATH      =  __DIR__."/log/mylog";                                       // path to log file
const ERR_PATH      =  __DIR__."/log/errors";                                      // path to log file
const JS_PATH       =  __DIR__."/js/";                                             // path to the directory containing javascript


// map collapsible attributes c-* and o-* to specific names, if existing
// do this serverside (i.e. compile time) and not clientside (i.e. run time) for page-control by author 
const ATT2NAME = array (
  "proof"    => "Proof",     "beweis"    => "Beweis",
  "example"  => "Example",   "beispiel"  => "Beispiel",
  "remark"   => "Remark",    "bemerkung" => "Bemerkung",
  "note"     => "Note"
);

const ATT2STYLE_SPEC = array (
  "proof"     =>  "background-color:yellow;", 
  "notation"  =>  "background-color:salmon;",
  "example"   =>  "background-color:green;",
  "remark"    =>  "background-color:lightblue;",
  "note"      =>  "background-color:AliceBlue;"
);

const ATT_DEFAULT_STYLE_SPEC = "background-color:white;";
const ATT_DEFAULT_NAME = "Info";



/*** Components which may be addressed via URL */
const TEMPLATE_URL       = "/extensions/Parsifal/template/";

const ENDPOINT_URL       = "/extensions/Parsifal/endpoints/tex-preview.php";           // endpoint for the preview
const ENDPOINT_REFRESH   = "/extensions/Parsifal/endpoints/tex-refresh.php";           // endpoint for the preview
const HTML_URL           = "/extensions/Parsifal/html/";                               // path to the html directory containing texLog.html and others

?>