@cls 
@echo off 

c:\xampp\php\php -f mysql_import.php default download
call runtime\default\download.bat

c:\xampp\php\php -f mysql_import.php default unzip
call runtime\default\unzip.bat

c:\xampp\php\php -f mysql_import.php default import
call runtime\default\import.bat

@pause