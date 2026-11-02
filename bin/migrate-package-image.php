<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../app/bootstrap.php';
if(!query("SHOW COLUMNS FROM packages LIKE 'image'")->fetch()) query("ALTER TABLE packages ADD COLUMN image VARCHAR(250) NOT NULL DEFAULT ''");
echo "Package image schema ready.\n";
