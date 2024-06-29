

## What to do when maintaining Parsifal?

* Adjust the paths in Parsifal/config to the current configuration
* For manual testing, you can use Parsifal/config/setenv.sh


## Important 

* We have to call /opt/myenv/bin/activate to activate the virtual python environment for PyMuPDF to work 
  properly, since we have decided to run PyMuPDF in virtual environment (as this makes installation much easier)


* php/8.2 
  /etc/php/8.2/fpm/pool.d/www.conf

set p max_children  to higher value !!!


TODO: eventually set ;pm.process_idle_timeout  and  set the strategy to ondemand to have that timeout activated !!!! TESt !!!!!



/home/dante/dantescript/activate-python.sh
Make executeable mode 755







source /opt/myenv/bin/activate

/home/dante/dantescript/activate-python.php

/home/dante/dantescript/activate-python.sh    

In den php ini file TODO welchen
php_value[auto_prepend_file]=/home/dante/dantescript/activate-python.php





Startup check for php8-2-fpm
  php-fpm8.2 -t


