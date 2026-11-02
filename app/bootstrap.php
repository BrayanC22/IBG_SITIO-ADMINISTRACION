<?php
declare(strict_types=1);
date_default_timezone_set('America/Guayaquil');
$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) { http_response_code(503); exit('Configura app/config.local.php siguiendo README.md.'); }
$config = require $configFile;
ini_set('display_errors','0');
$sessionDir=__DIR__.'/storage/sessions';
if(!is_dir($sessionDir)) mkdir($sessionDir,0700,true);
session_save_path($sessionDir);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>$config['secure_cookies'] ?? true,'path'=>'/']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
function db(): PDO { global $config; static $db; if(!$db) { $db=new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); $db->exec("SET time_zone = '-05:00'"); } return $db; }
function query(string $sql,array $args=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($args); return $s; }
function fail(string $message,int $status=400): never { http_response_code($status); echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE); exit; }
function out($data): never { echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function admin(): void { if(empty($_SESSION['admin']) || time()-($_SESSION['last_activity']??0)>7200) { unset($_SESSION['admin']); fail('Inicia sesión para continuar.',401); } $_SESSION['last_activity']=time(); }
function csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??'') || empty($_SESSION['csrf'])) fail('Sesión caducada. Recarga la página.',403); }
function textval($value,int $max=300,bool $required=false): string { if(!is_scalar($value) && $value!==null) fail('Valor de texto inválido.'); $v=trim((string)$value); if(($required && $v==='') || mb_strlen($v)>$max) fail('Completa los campos y respeta su longitud máxima.'); return $v; }
function integer($v,int $min=0,int $max=1000000): int { if(filter_var($v,FILTER_VALIDATE_INT)===false || (int)$v<$min || (int)$v>$max) fail('Número fuera de rango.'); return (int)$v; }
function media($value): string { $v=textval($value,250); if($v!=='' && !preg_match('~^assets/(?:uploads/)?[a-zA-Z0-9_.-]+\.(?:jpeg|jpg|png|webp)$~',$v)) fail('Selecciona una imagen de la biblioteca.'); return $v; }
function throttle(string $action,int $max,int $seconds): void {
 $key=hash('sha256',$action.'|'.($_SERVER['REMOTE_ADDR']??'local')); $now=time();
 query('INSERT INTO rate_limits (id,attempts,reset_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(reset_at<=?,1,attempts+1),reset_at=IF(reset_at<=?,VALUES(reset_at),reset_at)',[$key,$now+$seconds,$now,$now]);
 if((int)query('SELECT attempts FROM rate_limits WHERE id=?',[$key])->fetchColumn()>$max) fail('Demasiados intentos. Inténtalo más tarde.',429);
}
