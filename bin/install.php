<?php
// Ejecutar desde consola. Nunca se expone dentro del directorio public.
if(PHP_SAPI!=='cli') exit;
$config=require __DIR__.'/../app/config.local.php';
$db=new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
if((int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn()===0) {
 $categories=[['Servicios eléctricos','Instalaciones, mantenimiento y diagnóstico para hogares, comercios e industrias.','assets/electricidad.jpeg'],['Redes de distribución','Infraestructura aérea y subterránea para conectar tu proyecto.','assets/redes.jpeg'],['Climatización','Instalación y cuidado de aires acondicionados para cada espacio.','assets/climatizacion.jpeg']];
 $s=$db->prepare('INSERT INTO categories (name,description,image,sort_order) VALUES (?,?,?,?)'); foreach($categories as $i=>$c) $s->execute([...$c,$i+1]);
 $services=[
 [1,'Instalaciones eléctricas','Soluciones para instalaciones residenciales, comerciales e industriales.','Evaluación de necesidades\nDefinición del alcance y materiales\nInstalación según presupuesto aprobado',1],
 [1,'Tableros eléctricos','Instalación y mantenimiento de tableros para tu instalación.','Inspección del tablero\nRevisión de componentes\nMantenimiento o instalación según diagnóstico',1],
 [1,'Diagnóstico y reparación de fallas','Identificación de averías y mediciones eléctricas para definir una solución.','Inspección técnica\nMediciones eléctricas\nPropuesta de reparación',1],
 [1,'Iluminación y tomacorrientes','Instalación de puntos de iluminación y tomacorrientes.','Revisión de ubicación y necesidades\nInstalación de puntos acordados',0],
 [1,'Canalización y cableado','Canalización, cableado y tendido de conductores.','Revisión del recorrido\nDefinición de canalización y conductores',0],
 [1,'Fusibles y protecciones','Instalación y cambio de fusibles y protecciones eléctricas.','Revisión de protecciones\nIdentificación de componentes a sustituir',0],
 [1,'Celdas eléctricas','Montaje y mantenimiento de celdas eléctricas.','Evaluación técnica\nAlcance de montaje o mantenimiento a medida',0],
 [1,'Trabajos en baja y media tensión','Servicios eléctricos sujetos a evaluación técnica del proyecto.','Revisión de requerimientos\nPlanificación de la intervención',0],
 [1,'Montaje y desmontaje de equipos','Servicios complementarios de montaje, desmontaje y trabajos en altura.','Evaluación del acceso y los equipos\nPlan de trabajo según el proyecto',0],
 [2,'Redes aéreas','Construcción y mantenimiento de redes de distribución aéreas.','Evaluación del proyecto\nConstrucción o mantenimiento según alcance',1],
 [2,'Redes subterráneas','Instalación y mantenimiento de redes de distribución subterráneas.','Evaluación del trazado\nPlanificación de instalación y mantenimiento',0],
 [2,'Transformadores de distribución','Montaje, conexión y mantenimiento de transformadores.','Revisión de necesidades\nDefinición de trabajos y componentes',1],
 [2,'Inspección de redes','Inspección y diagnóstico técnico de redes de distribución.','Inspección técnica\nIdentificación de necesidades de mantenimiento',0],
 [2,'Asesoría para proyectos eléctricos','Acompañamiento técnico para definir las necesidades de tu proyecto.','Revisión de requerimientos\nOrientación sobre el alcance de la solución',0],
 [3,'Instalación de aire acondicionado','Instalación de equipos de aire acondicionado tipo split.','Revisión del espacio\nDefinición de materiales y ubicación\nPuesta en marcha',1],
 [3,'Mantenimiento de aire acondicionado','Mantenimiento preventivo y correctivo para tus equipos.','Revisión del equipo\nIdentificación de necesidades\nMantenimiento según alcance aprobado',1],
 [3,'Limpieza y desinfección','Limpieza y desinfección de equipos de climatización.','Evaluación del equipo\nLimpieza y desinfección según su condición',0],
 [3,'Reparación de averías','Diagnóstico y reparación de fallas de climatización.','Diagnóstico técnico\nPropuesta de reparación\nComprobación de funcionamiento',0],
 [3,'Revisión eléctrica de equipos','Revisión de los componentes eléctricos del sistema de climatización.','Inspección de componentes eléctricos\nDiagnóstico de fallas',0],
 [3,'Cambio de componentes','Instalación y sustitución de componentes de climatización.','Identificación de componentes\nSustitución según presupuesto',0],
 [3,'Detección de fugas','Detección de fugas y revisión del sistema.','Revisión técnica\nIdentificación de posibles fugas',0],
 [3,'Puesta en marcha','Puesta en marcha y comprobación de funcionamiento.','Revisión previa\nComprobación del funcionamiento del equipo',0]
 ];
 $s=$db->prepare('INSERT INTO services (category_id,name,description,includes,featured,sort_order) VALUES (?,?,?,?,?,?)'); foreach($services as $i=>$r) $s->execute([...$r,$i]);
 $p=$db->prepare('INSERT INTO packages (name,description,terms,badge,sort_order) VALUES (?,?,?,?,?)');
 foreach([['Hogar seguro','Revisión y diagnóstico eléctrico para tu hogar.','Borrador: definir alcance, materiales, cobertura y condiciones antes de publicar.','Paquete',1],['Clima al día','Mantenimiento y limpieza de aire acondicionado.','Borrador: definir cantidad y tipo de equipos, alcance y exclusiones.','Mantenimiento',2],['Negocio en marcha','Atención eléctrica y de climatización para tu negocio.','Borrador: definir alcance, visita técnica y condiciones del servicio.','Para negocios',3]] as $r) $p->execute($r);
 foreach([[1,3],[1,6],[2,16],[2,17],[3,2],[3,16]] as $r) $db->prepare('INSERT INTO package_services VALUES (?,?)')->execute($r);
}
$defaults=['brand'=>'IBG','tagline'=>'Soluciones eléctricas integrales','hero_title'=>'Tu proyecto, nuestra energía.','hero_text'=>'Electricidad, redes y climatización. Encuentra la solución que necesitas y cuéntanos cómo podemos ayudarte.','about'=>'Integramos servicios eléctricos, redes de distribución y climatización para hogares, comercios e industrias. Escuchamos las necesidades de tu proyecto para preparar una solución a tu medida.','phone'=>'593962689533','email'=>'brambilla43@hotmail.com','coverage'=>'Atención en Ecuador','hours'=>'Consulta disponibilidad y coordinación de visitas por WhatsApp.','footer_text'=>'Energía · Redes · Climatización','logo'=>'assets/logo.jpeg','hero_image'=>'assets/hero.jpeg','privacy_text'=>'Usaremos los datos de este formulario para responder tu consulta y gestionar la cotización solicitada. Puedes solicitar la actualización o eliminación de tus datos escribiendo al correo de contacto de IBG. No introduzcas información sensible en tu mensaje.'];
$s=$db->prepare('INSERT IGNORE INTO settings (id,value) VALUES (?,?)'); foreach($defaults as $k=>$v) $s->execute([$k,$v]);
if((int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn()===0) {
 $email=getenv('IBG_ADMIN_EMAIL'); $pass=getenv('IBG_ADMIN_PASSWORD');
 if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$pass || strlen($pass)<12) exit("Contenido instalado. Define IBG_ADMIN_EMAIL y IBG_ADMIN_PASSWORD (mínimo 12 caracteres) y ejecuta de nuevo para crear el administrador.\n");
 $db->prepare('INSERT INTO admins(email,password_hash) VALUES (?,?)')->execute([$email,password_hash($pass,PASSWORD_DEFAULT)]);
}
echo "Instalación completada.\n";
