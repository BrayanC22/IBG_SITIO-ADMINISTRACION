<?php
require __DIR__.'/../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$action=$_GET['action']??'catalog';
function quote_result(string $reference): array {
 $q=query('SELECT * FROM quotes WHERE reference=?',[$reference])->fetch();
 $settings=query('SELECT id,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
 $items=query('SELECT name,quantity,unit_price FROM quote_items WHERE quote_id=? ORDER BY id',[$q['id']])->fetchAll();
 $lines=['Hola '.($settings['brand']??'IBG').', quisiera cotizar mi proyecto.','Referencia: '.$reference,'','DATOS DE CONTACTO','Nombre: '.$q['name'],'WhatsApp: '.$q['phone'],'Ciudad / sector: '.$q['city']];
 if($q['email']!=='') $lines[]='Correo: '.$q['email'];
 $lines[]=''; $lines[]='SERVICIOS SOLICITADOS';
 foreach($items as $item) $lines[]='• '.$item['name'].' × '.$item['quantity'].($item['unit_price']!==null?' — USD '.number_format((float)$item['unit_price'],2,'.','').' por unidad (referencial)':' — a cotizar');
 if(!$items) $lines[]='Consulta personalizada';
 $lines[]=''; $lines[]='DETALLES DEL PROYECTO'; $lines[]=$q['details'];
 $lines[]=''; $lines[]='El alcance y precio final están sujetos a evaluación.';
 $message=implode("\n",$lines);
 return ['reference'=>$reference,'whatsapp_url'=>'https://wa.me/'.$settings['phone'].'?text='.rawurlencode($message),'email_url'=>'mailto:'.$settings['email'].'?subject='.rawurlencode('Solicitud '.$reference).'&body='.rawurlencode($message)];
}
try {
 if($_SERVER['REQUEST_METHOD']==='GET') {
  if($action==='catalog') {
   $_SESSION['csrf']??=bin2hex(random_bytes(32));
   $settings=query('SELECT id,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
   out(['csrf'=>$_SESSION['csrf'],'settings'=>$settings,'categories'=>query('SELECT * FROM categories WHERE active=1 ORDER BY sort_order,id')->fetchAll(),'services'=>query('SELECT s.* FROM services s JOIN categories c ON c.id=s.category_id WHERE s.active=1 AND c.active=1 ORDER BY s.sort_order,s.id')->fetchAll(),'packages'=>query('SELECT p.* FROM packages p WHERE active=1 AND (start_date IS NULL OR start_date<=CURRENT_DATE) AND (end_date IS NULL OR end_date>=CURRENT_DATE) AND EXISTS (SELECT 1 FROM package_services ps WHERE ps.package_id=p.id) AND NOT EXISTS (SELECT 1 FROM package_services ps JOIN services s ON s.id=ps.service_id JOIN categories c ON c.id=s.category_id WHERE ps.package_id=p.id AND (s.active=0 OR c.active=0)) ORDER BY sort_order,id')->fetchAll(),'package_services'=>query('SELECT * FROM package_services')->fetchAll()]);
  }
  if($action==='session') { $_SESSION['csrf']??=bin2hex(random_bytes(32)); out(['csrf'=>$_SESSION['csrf'],'logged_in'=>!empty($_SESSION['admin']) && time()-($_SESSION['last_activity']??0)<=7200]); }
  admin();
  if($action==='admin_data') {
   $data=[]; foreach(['categories','services','packages','package_services','quotes','quote_items'] as $table) $data[$table]=query('SELECT * FROM '.$table.($table==='quotes'?' ORDER BY id DESC':''))->fetchAll();
   $data['settings']=query('SELECT id,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
   $data['media']=array_map(fn($x)=>'assets/'.basename($x),glob(__DIR__.'/assets/*.{jpg,jpeg,png,webp}',GLOB_BRACE));
   foreach(glob(__DIR__.'/assets/uploads/*.{jpg,jpeg,png,webp}',GLOB_BRACE) as $x) $data['media'][]='assets/uploads/'.basename($x);
   out($data);
  }
  fail('Ruta no encontrada.',404);
 }
 if($_SERVER['REQUEST_METHOD']!=='POST') fail('Método no permitido.',405);
 csrf();
 if((int)($_SERVER['CONTENT_LENGTH']??0)>6000000) fail('Solicitud demasiado grande.',413);
 $input=json_decode(file_get_contents('php://input'),true)??[];
 if($action==='login') {
  throttle('login',10,900); $email=textval($input['email']??'',190,true); $password=textval($input['password']??'',200,true);
  $user=query('SELECT * FROM admins WHERE email=?',[$email])->fetch();
  if(!$user || !password_verify($password,$user['password_hash'])) fail('Correo o contraseña incorrectos.',401);
  session_regenerate_id(true); $_SESSION['admin']=$user['id']; $_SESSION['last_activity']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32)); out(['ok'=>true,'csrf'=>$_SESSION['csrf']]);
 }
 if($action==='quote') {
  throttle('quote',10,3600);
  if(!empty($input['website'])) fail('Solicitud inválida.');
  if(($input['consent']??false)!==true) fail('Debes autorizar el contacto para gestionar tu solicitud.');
  $key=textval($input['submission_key']??'',64,true); if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/',$key)) fail('Identificador inválido.');
  $old=query('SELECT reference FROM quotes WHERE submission_key=?',[$key])->fetchColumn(); if($old) out(quote_result($old));
  $name=textval($input['name']??'',100,true); $phone=textval($input['phone']??'',30,true); if(!preg_match('/^[+0-9 ()-]{7,30}$/',$phone)) fail('Revisa el teléfono.');
  $email=textval($input['email']??'',190); if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Revisa el correo.');
  $city=textval($input['city']??'',120,true); $details=textval($input['details']??'',3000,true); $items=$input['items']??[]; if(!is_array($items) || count($items)>40) fail('Carrito inválido.');
  $resolved=[];
  foreach($items as $item) {
   if(!is_array($item)) fail('Artículo inválido.');
   $type=$item['type']??''; $id=integer($item['id']??0,1); $quantity=integer($item['quantity']??0,1,99);
   if($type==='service') $row=query('SELECT s.* FROM services s JOIN categories c ON c.id=s.category_id WHERE s.id=? AND s.active=1 AND c.active=1',[$id])->fetch();
   elseif($type==='package') $row=query('SELECT p.* FROM packages p WHERE id=? AND active=1 AND (start_date IS NULL OR start_date<=CURRENT_DATE) AND (end_date IS NULL OR end_date>=CURRENT_DATE) AND EXISTS (SELECT 1 FROM package_services ps WHERE ps.package_id=p.id) AND NOT EXISTS (SELECT 1 FROM package_services ps JOIN services s ON s.id=ps.service_id JOIN categories c ON c.id=s.category_id WHERE ps.package_id=p.id AND (s.active=0 OR c.active=0))',[$id])->fetch();
   else fail('Tipo de artículo inválido.');
   if(!$row) fail('Uno de los servicios ya no está disponible. Actualiza el carrito.');
   $resolved[]=[$type,$id,$row['name'],$quantity,$row['price']];
  }
  db()->beginTransaction(); $ref='IBG-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
  query('INSERT INTO quotes (reference,submission_key,name,phone,email,city,details,notes,consent_at) VALUES (?,?,?,?,?,?,?,\'\',NOW())',[$ref,$key,$name,$phone,$email,$city,$details]); $quoteId=db()->lastInsertId();
  foreach($resolved as $item) query('INSERT INTO quote_items (quote_id,item_type,item_id,name,quantity,unit_price) VALUES (?,?,?,?,?,?)',array_merge([$quoteId],$item));
  db()->commit(); out(quote_result($ref));
 }
 admin();
 if($action==='logout') { $_SESSION=[]; session_destroy(); out(['ok'=>true]); }
 if($action==='password') { $old=textval($input['current']??'',200,true); $new=textval($input['password']??'',200,true); if(mb_strlen($new)<12) fail('Usa al menos 12 caracteres.'); $hash=query('SELECT password_hash FROM admins WHERE id=?',[$_SESSION['admin']])->fetchColumn(); if(!password_verify($old,$hash)) fail('La contraseña actual no coincide.'); query('UPDATE admins SET password_hash=? WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$_SESSION['admin']]); session_regenerate_id(true); out(['ok'=>true]); }
 if($action==='upload') {
  $file=$_FILES['image']??null; if(!$file || $file['error']!==UPLOAD_ERR_OK || $file['size']>5000000) fail('Selecciona una imagen de hasta 5 MB.');
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
  if(!$ext || !getimagesize($file['tmp_name'])) fail('Solo se admiten JPG, PNG y WebP válidos.');
  $dir=__DIR__.'/assets/uploads'; if(!is_dir($dir)) mkdir($dir,0755,true); $path='assets/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
  if(!move_uploaded_file($file['tmp_name'],__DIR__.'/'.$path)) fail('No se pudo guardar la imagen.',500); out(['path'=>$path]);
 }
 if($action==='settings') {
  $allowed=['brand','tagline','hero_title','hero_text','about','phone','email','coverage','hours','footer_text','logo','hero_image','privacy_text'];
  db()->beginTransaction(); foreach($allowed as $key) { if(!array_key_exists($key,$input)) continue; $value=in_array($key,['logo','hero_image'])?media($input[$key]):textval($input[$key],in_array($key,['about','privacy_text'])?5000:600,true); if($key==='email' && !filter_var($value,FILTER_VALIDATE_EMAIL)) fail('Correo inválido.'); if($key==='phone') { if(!preg_match('/^\+?[0-9 ()-]+$/',$value)) fail('WhatsApp: escribe un número válido con código de país.'); $value=preg_replace('/[^0-9]/','',$value); if(!preg_match('/^[1-9][0-9]{7,14}$/',$value)) fail('WhatsApp: incluye el código de país, por ejemplo +593.'); } query('INSERT INTO settings (id,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$key,$value]); } db()->commit(); out(['ok'=>true]);
 }
 if($action==='quote_status') { $status=$input['status']??''; if(!in_array($status,['Nueva','En revisión','Cotizada','Aprobada','Cerrada'])) fail('Estado inválido.'); query('UPDATE quotes SET status=?,notes=? WHERE id=?',[$status,textval($input['notes']??'',5000),integer($input['id']??0,1)]); out(['ok'=>true]); }
 if($action==='save') {
  $entity=$input['entity']??''; $v=$input['record']??[]; if(!is_array($v)) fail('Registro inválido.');
  if(!in_array($entity,['categories','services','packages'])) fail('Tipo inválido.');
  $id=integer($v['id']??0); $fields=['name'=>textval($v['name']??'', $entity==='categories'?100:150,true),'description'=>textval($v['description']??'',3000,true),'active'=>integer($v['active']??0,0,1),'sort_order'=>integer($v['sort_order']??0,0,9999)];
  $fields['image']=media($v['image']??'');
  if($entity!=='categories') { $price=$v['price']??''; if($price!=='' && $price!==null && (!is_numeric($price) || (float)$price<0 || (float)$price>99999999.99)) fail('Precio inválido.'); $fields['price']=$price===''||$price===null?null:round((float)$price,2); }
  if($entity==='services') { $fields['category_id']=integer($v['category_id']??0,1); if(!query('SELECT id FROM categories WHERE id=?',[$fields['category_id']])->fetch()) fail('Categoría inexistente.'); $fields['includes']=textval($v['includes']??'',5000); $fields['featured']=integer($v['featured']??0,0,1); }
  if($entity==='packages') {
   $fields['terms']=textval($v['terms']??'',5000); $fields['badge']=textval($v['badge']??'',60);
   foreach(['start_date','end_date'] as $date) { $raw=$v[$date]??''; $d=$raw?DateTime::createFromFormat('!Y-m-d',(string)$raw):null; if($raw && (!$d || $d->format('Y-m-d')!==$raw)) fail('Fecha inválida.'); $fields[$date]=$raw?:null; }
   if($fields['start_date'] && $fields['end_date'] && $fields['start_date']>$fields['end_date']) fail('La fecha final debe ser posterior al inicio.');
   $members=$v['service_ids']??[]; if(!is_array($members) || count($members)>100) fail('Selección inválida.'); $members=array_unique(array_map(fn($x)=>integer($x,1),$members)); if($fields['active'] && !$members) fail('Selecciona al menos un servicio para publicar el paquete.');
   foreach($members as $sid) if(!query('SELECT id FROM services WHERE id=?',[$sid])->fetch()) fail('Servicio inexistente.');
  }
  db()->beginTransaction();
  if($id) { if(!query('SELECT id FROM '.$entity.' WHERE id=?',[$id])->fetch()) fail('Registro inexistente.',404); query('UPDATE '.$entity.' SET '.implode(',',array_map(fn($x)=>$x.'=?',array_keys($fields))).' WHERE id=?',array_merge(array_values($fields),[$id])); }
  else { query('INSERT INTO '.$entity.' ('.implode(',',array_keys($fields)).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_values($fields)); $id=(int)db()->lastInsertId(); }
  if($entity==='packages') { query('DELETE FROM package_services WHERE package_id=?',[$id]); foreach($members as $sid) query('INSERT INTO package_services (package_id,service_id) VALUES (?,?)',[$id,$sid]); }
  db()->commit(); out(['ok'=>true,'id'=>$id]);
 }
 if($action==='delete') {
  $entity=$input['entity']??''; if(!in_array($entity,['categories','services','packages'])) fail('Tipo inválido.'); $id=integer($input['id']??0,1);
  if($entity==='categories' && query('SELECT COUNT(*) FROM services WHERE category_id=?',[$id])->fetchColumn()) fail('Esta categoría tiene servicios. Reasígnalos o desactiva la categoría.');
  if($entity==='services' && query('SELECT COUNT(*) FROM package_services WHERE service_id=?',[$id])->fetchColumn()) fail('El servicio pertenece a un paquete. Retíralo del paquete o desactívalo.');
  query('DELETE FROM '.$entity.' WHERE id=?',[$id]); out(['ok'=>true]);
 }
 fail('Ruta no encontrada.',404);
} catch(Throwable $e) { try { if(db()->inTransaction()) db()->rollBack(); } catch(Throwable $ignored) {} error_log($e->getMessage()); fail('No se pudo completar la operación. Revisa la conexión y vuelve a intentar.',500); }
