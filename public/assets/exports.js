/* IBG reporting: browser-local generation, no customer data sent to third parties. */
(() => {
 'use strict';
 const navy='08263B',blue='146B90',yellow='FFD224',muted='647D8C',pale='F1F6F8';
 const text=v=>String(v??''), safe=v=>/^[\s]*[=+@-]/.test(text(v))?"'"+text(v):text(v);
 const stamp=()=>new Intl.DateTimeFormat('es-EC',{timeZone:'America/Guayaquil',dateStyle:'medium',timeStyle:'short'}).format(new Date());
 const filename=ext=>'IBG-cotizaciones-'+new Intl.DateTimeFormat('en-CA',{timeZone:'America/Guayaquil',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date())+'.'+ext;
 const matching=(q,items)=>items.filter(i=>Number(i.quote_id)===Number(q.id));
 const scope=o=>`${o.quotes.length} solicitudes · ${o.filter||'Todos los estados'}${o.search?' · Búsqueda: '+o.search:''}`;
 function csv(o){
  const rows=[['Referencia','Fecha de solicitud','Cliente','Teléfono / WhatsApp','Correo electrónico','Ciudad / sector','Estado','Servicios y cantidades','Detalle del proyecto','Notas internas'],...o.quotes.map(q=>[q.reference,q.created_at,q.name,q.phone,q.email,q.city,q.status,matching(q,o.items).map(i=>`${i.name} (cantidad: ${i.quantity})`).join(' | ')||'Consulta general',q.details,q.notes])];
  // sep= makes the delimiter explicit when opened directly in Excel; UTF-8 BOM preserves accents.
  return '\uFEFFsep=;\r\n'+rows.map(row=>row.map(v=>'"'+safe(v).replace(/"/g,'""')+'"').join(';')).join('\r\n');
 }
 async function logoImage(path){
  if(!path)return null;
  try{const r=await fetch(path);if(!r.ok)return null;const blob=await r.blob();const source=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(reader.result);reader.onerror=reject;reader.readAsDataURL(blob);});const img=await new Promise((resolve,reject)=>{const im=new Image();im.onload=()=>resolve(im);im.onerror=reject;im.src=source;});const canvas=document.createElement('canvas');canvas.width=600;canvas.height=Math.max(1,Math.round(600*img.height/img.width));const context=canvas.getContext('2d');context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);context.drawImage(img,0,0,canvas.width,canvas.height);return {base64:canvas.toDataURL('image/png'),extension:'png',width:canvas.width,height:canvas.height};}catch{return null;}
 }
 function decorateSheet(book,sheet,title,subtitle,widths,logo){
  const end=widths.length;
  sheet.properties.defaultRowHeight=22;sheet.views=[{state:'frozen',ySplit:7,showGridLines:false}];
  sheet.columns=widths.map(width=>({width}));
  for(let row=1;row<=5;row++){sheet.getRow(row).height=row===1?24:20;for(let col=1;col<=end;col++){sheet.getCell(row,col).fill={type:'pattern',pattern:'solid',fgColor:{argb:'FF'+navy}};}}
  sheet.mergeCells(1,2,2,end);sheet.getCell(1,2).value=title;sheet.getCell(1,2).font={name:'Aptos Display',size:21,bold:true,color:{argb:'FFFFFFFF'}};sheet.getCell(1,2).alignment={vertical:'middle'};
  sheet.mergeCells(3,2,3,end);sheet.getCell(3,2).value=subtitle;sheet.getCell(3,2).font={name:'Aptos',size:11,color:{argb:'FFCBDFE9'}};
  sheet.mergeCells(4,2,4,end);sheet.getCell(4,2).value='Generado: '+stamp()+' · Uso interno';sheet.getCell(4,2).font={name:'Aptos',size:10,color:{argb:'FFCBDFE9'}};
  sheet.getRow(5).height=5;for(let c=1;c<=end;c++)sheet.getCell(5,c).fill={type:'pattern',pattern:'solid',fgColor:{argb:'FF'+yellow}};
  if(logo){const id=book.addImage({base64:logo.base64,extension:logo.extension});const maxW=145,maxH=70,factor=Math.min(maxW/logo.width,maxH/logo.height);sheet.addImage(id,{tl:{col:.12,row:.2},ext:{width:logo.width*factor,height:logo.height*factor},editAs:'oneCell'});}
  else {sheet.getCell('A1').value='IBG';sheet.getCell('A1').font={name:'Aptos Display',size:24,bold:true,color:{argb:'FF'+yellow}};}
  sheet.getRow(6).height=12;sheet.pageSetup={paperSize:9,orientation:'landscape',fitToPage:true,fitToWidth:1,fitToHeight:0,printTitlesRow:'1:7',margins:{left:.3,right:.3,top:.4,bottom:.4,header:.15,footer:.15}};
  sheet.headerFooter={oddFooter:'&LIBG · Uso interno&C&P de &N&R'+new Date().getFullYear()};
 }
 function dataTable(sheet,headers,rows){
  const heading=sheet.getRow(7);heading.values=headers;heading.height=28;
  heading.eachCell(c=>{c.font={name:'Aptos',size:11,bold:true,color:{argb:'FFFFFFFF'}};c.fill={type:'pattern',pattern:'solid',fgColor:{argb:'FF'+blue}};c.alignment={vertical:'middle',wrapText:true};});
  rows.forEach((values,index)=>{const row=sheet.addRow(values);let lines=1;row.eachCell((c,col)=>{c.font={name:'Aptos',size:11,color:{argb:'FF'+navy}};c.alignment={vertical:'top',wrapText:true};c.fill={type:'pattern',pattern:'solid',fgColor:{argb:index%2?'FFF1F6F8':'FFFFFFFF'}};c.border={bottom:{style:'hair',color:{argb:'FFDCE7ED'}}};const str=text(c.value);lines=Math.max(lines,str.split('\n').reduce((sum,line)=>sum+Math.max(1,Math.ceil(line.length/Math.max(12,sheet.getColumn(col).width-5))),0));});row.height=Math.max(36,Math.min(390,lines*15+12));});
  sheet.autoFilter={from:{row:7,column:1},to:{row:Math.max(7,sheet.rowCount),column:headers.length}};
  sheet.pageSetup.printArea=`A1:${sheet.getColumn(headers.length).letter}${sheet.rowCount}`;
 }
 async function workbook(o){
  const book=new ExcelJS.Workbook();book.creator=o.settings.brand||'IBG';book.created=new Date();book.subject='Solicitudes de cotización';book.title='IBG · Reporte de cotizaciones';
  const main=book.addWorksheet('Cotizaciones',{properties:{tabColor:{argb:'FF'+yellow}}});
  decorateSheet(book,main,(o.settings.brand||'IBG')+' · Cotizaciones',scope(o),[26,21,28,36,23,18,52,65],o.logo);
  dataTable(main,['Referencia','Fecha','Cliente','Teléfono y correo','Ciudad / sector','Estado','Servicios solicitados','Proyecto y seguimiento'],o.quotes.map(q=>[text(q.reference),text(q.created_at),text(q.name),text(q.phone)+(q.email?'\n'+q.email:''),text(q.city),text(q.status),matching(q,o.items).map(i=>`${i.name} × ${i.quantity}`).join('\n')||'Consulta general',text(q.details)+(q.notes?'\n\nNOTAS INTERNAS\n'+q.notes:'')]));
  for(let row=8;row<=main.rowCount;row++){const cell=main.getCell(row,6);const colors={Nueva:'FFF1C7',Cotizada:'E0EEF9',Aprobada:'DDF2E6',Cerrada:'E8ECEF','En revisión':'E9E4F8'};cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:'FF'+(colors[cell.value]||pale)}};cell.font={name:'Aptos',size:11,bold:true,color:{argb:'FF'+navy}};}
  const lines=book.addWorksheet('Servicios',{properties:{tabColor:{argb:'FF'+blue}}});
  decorateSheet(book,lines,'Detalle de servicios','Valores referenciales en USD · No constituyen un presupuesto final',[26,62,14,23,24,19],o.logo);
  const rows=[];o.quotes.forEach(q=>matching(q,o.items).forEach(i=>rows.push([text(q.reference),text(i.name),Number(i.quantity),i.unit_price===null?null:Number(i.unit_price),null,i.item_type==='package'?'Paquete':'Servicio'])));
  dataTable(lines,['Referencia','Servicio / paquete','Cantidad','Precio unitario USD','Subtotal referencial USD','Tipo'],rows);
  for(let row=8;row<=lines.rowCount;row++){lines.getCell(row,3).numFmt='0';lines.getCell(row,4).numFmt='"$"#,##0.00';lines.getCell(row,5).numFmt='"$"#,##0.00';if(lines.getCell(row,4).value!==null){const qty=Number(lines.getCell(row,3).value),price=Number(lines.getCell(row,4).value);lines.getCell(row,5).value={formula:`C${row}*D${row}`,result:qty*price};}else lines.getCell(row,5).value='A cotizar';}
  lines.getCell(lines.rowCount+2,1).value='Los campos de precio vacíos requieren valoración. Los subtotales son referenciales y no incluyen impuestos confirmados.';
  return book;
 }
 function pdf(o){
  const {jsPDF}=jspdf;const doc=new jsPDF({orientation:'landscape',unit:'mm',format:'a4',compress:true});
  doc.setProperties({title:'IBG · Reporte de cotizaciones',subject:'Solicitudes de cotización',author:o.settings.brand||'IBG'});
  const header=(section)=>{
   const w=doc.internal.pageSize.getWidth();doc.setFillColor(8,38,59);doc.rect(0,0,w,38,'F');doc.setFillColor(255,210,36);doc.rect(0,38,w,1.5,'F');
   doc.setFont('helvetica','bold');doc.setFontSize(18);doc.setTextColor(255,255,255);doc.text(section,15,15);doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(196,217,229);doc.text(doc.splitTextToSize((o.settings.brand||'IBG')+' · '+(o.settings.tagline||'Soluciones eléctricas integrales'),210),15,22);doc.text('Generado: '+stamp()+' · Uso interno',15,31);
   if(o.logo){const width=39,height=Math.min(27,width*o.logo.height/o.logo.width);doc.setFillColor(255,255,255);doc.roundedRect(w-57,5,42,29,2,2,'F');doc.addImage(o.logo.base64,o.logo.extension==='jpeg'?'JPEG':'PNG',w-55.5,6+(27-height)/2,width,height);}
  };
  const footer=()=>{const count=doc.getNumberOfPages();for(let p=1;p<=count;p++){doc.setPage(p);const w=doc.internal.pageSize.getWidth(),h=doc.internal.pageSize.getHeight();doc.setDrawColor(220,231,237);doc.line(15,h-12,w-15,h-12);doc.setFont('helvetica','normal');doc.setFontSize(7);doc.setTextColor(100,125,140);doc.text(text(o.settings.email)+' · WhatsApp +'+text(o.settings.phone),15,h-7);doc.text(`${p} / ${count}`,w-15,h-7,{align:'right'});}};
  header('Reporte de cotizaciones');doc.setFontSize(10);doc.setTextColor(8,38,59);doc.text(doc.splitTextToSize(scope(o),265),15,48);
  const body=o.quotes.map(q=>[q.reference,q.created_at,q.name,q.phone+(q.email?'\n'+q.email:''),q.city,q.status,matching(q,o.items).map(i=>`${i.name} x ${i.quantity}`).join('\n')||'Consulta general']);
  doc.autoTable({startY:55,margin:{left:15,right:15,top:47,bottom:20},head:[['Referencia','Fecha','Cliente','Contacto','Ubicación','Estado','Servicios']],body,theme:'striped',styles:{font:'helvetica',fontSize:8,cellPadding:3.5,overflow:'linebreak',textColor:[8,38,59],lineColor:[225,234,239],lineWidth:.1},headStyles:{fillColor:[20,107,144],textColor:[255,255,255],fontSize:8,fontStyle:'bold'},alternateRowStyles:{fillColor:[241,246,248]},columnStyles:{0:{cellWidth:34},1:{cellWidth:27},2:{cellWidth:35},3:{cellWidth:46},4:{cellWidth:27},5:{cellWidth:24},6:{cellWidth:74}},willDrawPage:()=>header('Reporte de cotizaciones')});
  for(const q of o.quotes){
   doc.addPage();header('Detalle de solicitud');doc.setFont('helvetica','bold');doc.setFontSize(14);doc.setTextColor(8,38,59);doc.text(q.reference,15,49);doc.setFontSize(9);doc.setFont('helvetica','normal');doc.text('Estado: '+q.status+' · Recibida: '+q.created_at,15,56);
   const detail=[['Cliente',q.name],['Contacto',q.phone+(q.email?' · '+q.email:'')],['Ciudad / sector',q.city],['Servicios solicitados',matching(q,o.items).map(i=>`${i.name} x ${i.quantity}${i.unit_price!==null?' · USD '+Number(i.unit_price).toFixed(2)+' por unidad (referencial)':' · A cotizar'}`).join('\n')||'Consulta general'],['Detalles del proyecto',q.details],['Notas internas',q.notes||'Sin notas registradas.']];
   doc.autoTable({startY:63,margin:{left:15,right:15,top:47,bottom:20},body:detail,theme:'plain',styles:{font:'helvetica',fontSize:10,cellPadding:4.5,overflow:'linebreak',textColor:[8,38,59],lineColor:[224,234,240],lineWidth:.15},columnStyles:{0:{cellWidth:48,fontStyle:'bold',fillColor:[241,246,248]},1:{cellWidth:219}},willDrawPage:()=>header('Detalle de solicitud')});
  }
  footer();return doc;
 }
 function save(blob,name){const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);}
 async function download(format,o){if(format==='csv'){save(new Blob([csv(o)],{type:'text/csv;charset=utf-8'}),filename('csv'));return;}o={...o,logo:await logoImage(o.settings.logo)};if(format==='xlsx'){const book=await workbook(o);save(new Blob([await book.xlsx.writeBuffer()],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}),filename('xlsx'));}else if(format==='pdf'){save(pdf(o).output('blob'),filename('pdf'));}else throw Error('Formato no válido.');}
 globalThis.IBGExports={download,csv,workbook,pdf};
})();
