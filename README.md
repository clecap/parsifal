# Parsifal

Parsifal is a MediaWiki extension for rendering mathematical texts using LaTeX.

It lives on https://github.com/clecap/Parsifal

Author cheat sheet: 

## Goals

### Mediawiki-based LaTeX Document Production

Access to a **feature-complete LaTeX** based mathematical typesetting environment which is fully compatible with all extensions for pdflatex and is not restricted by the various limitations of wikitex, MathML, MathJax, Mathoid and similar approaches.
* **Highest rendering quality** for the final results.
* **Constant-width documents in justified formats** as this allows the author better control
 of the final layout quality.

### Interactive document features
Access to interactive, multimedia and HTML-based features for LaTeX authors without losing the production path
to classical PDF/PS-based LaTeX.
* **Collapsible** elements (for example for proofs, examples, remarks etc.)
* **Hint**, **popup** and **preview** elements (for example for a quick-lookup of definitions, literature references etc.)
* **Clickable links** in the [hyperref](https://ctan.org/pkg/hyperref?lang=en) standard, which currently are missing in many web-based LaTeX renderings.
* **Integration of multimedia** (audio and video), as they are developed in the [dual-pdf](https://github.com/clecap/dual-pdf) project for PDF-formats.
* **Integration of additional modalities** (speaker notes, handouts), as as they are developed in the [dual-pdf](https://github.com/clecap/dual-pdf) project in PDF-formats.

### Author Workflow

Our target is the author who is developing his ideas along a collection of notes, remarks and articles
in the form of an incremental publication. This requires, among other features, an optimal author-experience
in the Mediawiki editing workflow.

* **Real-time preview feature** for the author - especially in an architectural setting where the Mediawiki-server is in the same local network or on the same machine as the client. Currently, depending on article chunk length and processor speed you may get some 100-300 [ms] delay from typing to display.
* **Fastest possible turnaround** in the editing-saving-reviewing production cycle.
* Access to **error messages** and **log-files** of the backend processors.
* **Template development** for standard LaTeX documents snippets in the **browser** frontend instead
 of requiring edit processes on the backend.
* Support for LaTeX template **precompilation**.
* **Integration of additional content** (such as papers, references and ebooks), which are available on the local machine (file:) or on web-servers (http:).
* Better access to **editor customization** by Javascript-savy users.

## Non-Goals

Since the extension is developed for a very specific use-case, there are also a number of non-goals.
* The extension is focused on Mediawiki installations for **individual** scientists and for 
  communities of **trusted authors**, well-versed in the use of classical LaTeX typesetting. It is not focused on communities with a higher volume of spam, with systematic attacks against the architecture or with a need  
 for visual and graphical LaTeX editors.
  
### Security Warning
* We are not sanitizing LaTeX input in any way. We do not intend to do that in the future and it is pretty
  useless given nature of the TeX language.
  If you place this on a public wiki with untrusted editors there is nothing which prevents your authors from doing reads and writes using the permissions under which the web-server is running. You might consider
  attacks like `\input{/etc/passwd}`.
* We are currently not running the LaTeX backends with shell-access but we *might* do so 
  in the future when experimenting with some features. Also, some of the other backend parts are not hardened.
  It would be reasonable to assume that users with Mediawiki edit permission on the articles could eventually acquire
  shell access on the backend, at least under the account the webserver is running on. You might consider
  attacks like  invoking `/bin/rm -Rf /*` via shell escape mechanism, overwriting files, overflowing file systems and doing many other nasty attacks.
* For our specific use case this is not a problem.
* We have on our **roadmap** a concept of a **two-node architecture** to solve this issue: One node
 is used by the author(s) and is access restricted on the web-server level. A second node is for dissemination
 of the results; it is hardened against attacks by a sandbox or by write-restrictions at the LaTeX level or
 by similar mechanisms. 

## Existing State of the Art

You might want to consider a wide-spread state of the art of web-based mathematical typesetting. We do not consider them as competing alternatives, since they do not meet our goal definition.

# Installation
To be written.

## Requirements

We currently expect the backend to be a Linux system with working installations of
* latex  
* pdflatex 
* node
* dvipng
* ghostscript
* mutool

We recommend EXT4 as file system (one reason being [file system performance](https://serverfault.com/questions/98235/how-many-files-in-a-directory-is-too-many-downloading-data-from-net)).

# Contributing

* **New Bugs** Please report in Github.
* **Existing bugs** can be found in github issues.
* **Planned features** can be found in github issues.
* **Feature requests** should be reported in github issues.
* **Coding style** is idiosyncratic. Sorry for that.

# Additional convenience functions


