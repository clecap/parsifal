#!/bin/sh

# import an initial set of templates into the wiki itself

# get directory where this script resides wherever it is called from
DIR="$( cd "$( dirname $0 )" && pwd )"

php ${DIR}/../../../maintenance/importTextFiles.php --prefix "MediaWiki:ParsifalTemplate/" --rc --overwrite ${DIR}/../initial-templates/*