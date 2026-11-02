<?php
if(PHP_SAPI!=='cli') exit;
$config=require __DIR__.'/../app/config.local.php';
$db=new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
echo "-- IBG: importar en una base de datos vacía desde phpMyAdmin.\n-- Incluye catálogo y textos. Excluye usuarios, contraseñas y solicitudes.\nSET NAMES utf8mb4;\n";
echo file_get_contents(__DIR__.'/../database/schema.sql')."\nSTART TRANSACTION;\n";
foreach(['categories','services','packages','package_services','settings'] as $table) {
 foreach($db->query('SELECT * FROM '.$table) as $row) {
  echo 'INSERT INTO '.$table.' (`'.implode('`,`',array_keys($row)).'`) VALUES ('.implode(',',array_map(fn($v)=>$v===null?'NULL':$db->quote((string)$v),array_values($row))).");\n";
 }
}
echo "COMMIT;\n";
