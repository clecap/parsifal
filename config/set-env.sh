#!/bin/bash

# set parameters for a manual testing of the latex commands when debugging

export TEXMFVAR=/var/www/.texlive2024/texmf-var
export TEXMFHOME=/var/www/texmf

export TEXMFLOCAL=/usr/local/texlive/texmf-local
export TEXINPUTS=/var/www/texinputs
export TEXMFSYSVAR=/usr/local/texlive/2024/texmf-var

export TEXDIR=/usr/local/texlive/2024

export TEXMFSYSCONFIG=/usr/local/texlive/2024/texmf-config
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/local/texlive/2024/bin/x86_64-linux:/bin:
export TEXMFCONFIG=/var/www/.texlive2024/texmf-config