#!/bin/bash


apt-get update
apt-get install -y wget unzip tar make fontconfig perl openjdk-8-jre libgetopt-long-descriptive-perl libdigest-perl-md5-perl libncurses5 python3-pygments

wget http://mirror.ctan.org/systems/texlive/tlnet/install-tl-unx.tar.gz 
tar xzf install-tl-unx.tar.gz 
rm install-tl-unx.tar.gz 
cd install-tl* 

# note: basic is a bit to less for doing amstex stuff
echo "selected_scheme scheme-small"   > install.profile 
echo "tlpdbopt_install_docfiles 0"   >> install.profile 
echo "tlpdbopt_install_srcfiles 0"   >> install.profile
echo "tlpdbopt_autobackup 0"         >> install.profile
echo "tlpdbopt_sys_bin /usr/bin"     >> install.profile
./install-tl -profile install.profile 
cd .. 

## /usr/local/texlive/2018/bin/x86_64-linux/tlmgr path add