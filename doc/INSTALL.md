


# Other aspects


## Server Configuration

We assume:
* the web server runs under user `www-data`.
* the user `www-data` has in `/etc/passwd` no home directory (or rather `/nonexistant`) and as a shell the `bash`.
* the directory `/var/www` exists and is write-able for user `www-data` (required since TeX will write into a font-generation directory in `/var/www` 


## SECURITY ASPECTS

1 tmp should contain a file .htaccess with content php_flag engine off
1 


## Requirements

* Mediawiki still needs PHP 7.4
* The installation of the newest PHP 7.4 subversion on Debian is a bit problematic, even on Debian 11. 
* Solutions can be found on https://computingforgeeks.com/how-to-install-latest-php-on-debian/


Mediawiki still needs PHP 7.4
The installation of the newest PHP 7.4 subversion on Debian is a bit problematic, even on Debian 11.
Solutions can be found on https://computingforgeeks.com/how-to-install-latest-php-on-debian/

pecl needs the correct version of php (7.4) to be set on the command line.

apt-get php-pear


### Data sets ###
We need php data sets for the cleanUp
We install using:
  pecl install ds

You'll need to add extension=ds.so to your primary php.ini file.


We currently expect the backend to be a Linux system with working installations of
* latex  
* pdflatex 
* node
* dvipng
* ghostscript
* mutool

We recommend EXT4 as file system (one reason being [file system performance](https://serverfault.com/questions/98235/how-many-files-in-a-directory-is-too-many-downloading-data-from-net)).



## Requirements 


