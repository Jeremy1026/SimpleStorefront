<?php
// app/config/parameters.php
$connstr = %getenv('MYSQLCONNSTR_localdb');
// $connstr = "Database=localdb;Data Source=127.0.0.1:53269;User Id=azure;Password=6#vWHD_$";


#hostname
preg_match('/Data\sSource=([^:]+):/', $connstr, $matches);
// printf("setParameter('database_host', $matches[1]);");
$container->setParameter('database_host', $matches[1]);

#port
preg_match('/Data\sSource=[^:]+:([^;]+)/', $connstr, $matches);
// printf("setParameter('database_port', $matches[1]);");
$container->setParameter('database_port', $matches[1]);

#username
preg_match('/User\sId=([^;]+)/', $connstr, $matches);
// printf("setParameter('database_user', $matches[1]);");
$container->setParameter('database_user', $matches[1]);

#password
preg_match('/Password=([^;]+)/', $connstr, $matches);
// printf("setParameter('database_password', $matches[1]);");
$container->setParameter('database_password', $matches[1]);
#db_name

preg_match('/Database=([^;]+)/', $connstr, $matches);
// printf("setParameter('database_name', $matches[1]);");
$container->setParameter('database_name', $matches[1]);

$container->setParameter('mailer_transport','smtp');
$container->setParameter('mailer_host','127.0.0.1');
$container->setParameter('mailer_user','~');
$container->setParameter('mailer_password','~');
$container->setParameter('secret','ThisTokenIsNotSoSecretChangeIt');

?>