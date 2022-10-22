


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

We currently expect the backend to be a Linux system with working installations of
* latex  
* pdflatex 
* node
* dvipng
* ghostscript
* mutool

We recommend EXT4 as file system (one reason being [file system performance](https://serverfault.com/questions/98235/how-many-files-in-a-directory-is-too-many-downloading-data-from-net)).





## Requirements 

Use a recent mutool version.

* mutools must be compiled from the sources since the packaged versions are old.
* This has a number of dependencies which are not easy to be met. In particular:

Get sources from

we must apt-get install:
  apt-get install libgl1-mesa-dev  libglu1.mesa-dev  libx11-dev  libxi-dev libxrandr-dev 
  
  
  make
  make install 
  
  see Heinricht:/opt  


