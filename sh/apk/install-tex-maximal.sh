#!/bin/sh


apk --no-cache update
apk --no-cache add wget unzip tar make fontconfig perl openjdk8-jre 
apl --no-cache perl-getopt-long-descriptive perl-digest-md5 ncurses py3-pygments

rm -Rf install-tl*
wget http://mirror.ctan.org/systems/texlive/tlnet/install-tl-unx.tar.gz 
tar xzf install-tl-unx.tar.gz 
rm install-tl-unx.tar.gz 
cd install-tl* 

# note: basic is a bit to less for doing amstex stuff
echo "selected_scheme scheme-full"   > install.profile 
echo "tlpdbopt_install_docfiles 0"   >> install.profile 
echo "tlpdbopt_install_srcfiles 0"   >> install.profile
echo "tlpdbopt_autobackup 0"         >> install.profile
echo "tlpdbopt_sys_bin /usr/bin"     >> install.profile
./install-tl -profile install.profile 
cd .. 
