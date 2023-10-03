#
#
#
#
#  Shell script for manual tests which generates the required environment for manual tex runs
#



####### CAVE: TODO: This is not up to date wrt the installation of Texlive (and we currently do not use it)

export PATH="/usr/local/bin:/usr/bin:/bin:/usr/local/texlive/2021/bin/x86_64-linux"
export HOME="/var/www"

# ENVIRONMENT as it was used in the TeXLive installer *
# ONLY Modification:  The ~ has been replaced by  /var/www  which is the home directory of user www-data 
export TEXDIR="/usr/local/texlive/2021"                         # main TeX directory
export TEXMFLOCAL="/usr/local/texlive/texmf-local"              # directory for site-wide local files
export TEXMFSYSVAR="/usr/local/texlive/2021/texmf-var"          # directory for variable and automatically generated data
export TEXMFSYSCONFIG="/usr/local/texlive/2021/texmf-config"    # directory for local config
export TEXMFVAR="/var/www/.texlive2021/texmf-var"                   # personal directory for variable and automatically generated data
export TEXMFCONFIG="/var/www/.texlive2021/texmf-config"                # personal directory for local config
export TEXMFHOME="/var/www/texmf"                                    # directory for user-specific files