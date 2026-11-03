<?php
// Small self-contained PDF writer: no Composer, internet or browser printing required.
class TicketImageException extends RuntimeException {}
class TicketPDF {
 private array $pages=[]; private string $stream=''; private float $y=0; private array $images=[]; private array $imageNames=[];
 public function page(): void {if($this->stream!==''){$this->pages[]=$this->stream;}$this->stream='';$this->y=795;$this->text('GUITARIXTA',22,true);$this->text('TU MÚSICA. TU PRÓXIMO SONIDO.',8);$this->line();}
 private function encoded(string $s): string {$s=preg_replace('/[\x00-\x1F\x7F]/u',' ',$s);return str_replace([chr(92),'(',')'],[chr(92).chr(92),chr(92).'(',chr(92).')'],iconv('UTF-8','Windows-1252//TRANSLIT',$s));}
 public function text(string $s,int $size=10,bool $bold=false): void {
  $width=$size>=18?40:88; $lines=explode("\n",wordwrap($s,$width,"\n",true));
  foreach($lines as $l){if($this->y<55){$this->page();}$this->stream.='BT /'.($bold?'F2':'F1').' '.$size.' Tf 1 0 0 1 42 '.$this->y.' Tm ('.$this->encoded($l).") Tj ET\n";$this->y-=$size+7;}
 }
 public function line(): void {$this->stream.='0.75 G 42 '.($this->y+3).' m 553 '.($this->y+3)." l S 0 G\n";$this->y-=14;}
 public function gap(int $n=7): void {$this->y-=$n;}
 private function imagenJpeg(string $data,string $mime): ?array {
  if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){throw new TicketImageException('El formato de imagen del producto no se puede incluir en el ticket.');}
  $info=@getimagesizefromstring($data);if(!$info || ($info['mime']??'')!==$mime){throw new TicketImageException('No se pudo leer la imagen de un producto del ticket.');}
  if($mime!=='image/jpeg'){
   if(!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')){throw new TicketImageException('Habilita la extensión GD de PHP para incluir imágenes PNG o WebP en el ticket.');}
   $source=@imagecreatefromstring($data);if(!$source){throw new TicketImageException('No se pudo convertir la imagen de un producto para el ticket.');}
   $sourceWidth=imagesx($source);$sourceHeight=imagesy($source);
   $scale=min(1,400/max($sourceWidth,$sourceHeight));$width=max(1,(int)round($sourceWidth*$scale));$height=max(1,(int)round($sourceHeight*$scale));
   $thumb=imagecreatetruecolor($width,$height);$white=imagecolorallocate($thumb,255,255,255);imagefill($thumb,0,0,$white);
   imagecopyresampled($thumb,$source,0,0,0,0,$width,$height,$sourceWidth,$sourceHeight);
   ob_start();$saved=imagejpeg($thumb,null,84);$jpeg=ob_get_clean();imagedestroy($thumb);imagedestroy($source);
   if(!$saved || !$jpeg){throw new TicketImageException('No se pudo convertir la imagen de un producto para el ticket.');}
   $data=$jpeg;$info=@getimagesizefromstring($data);if(!$info || ($info['mime']??'')!=='image/jpeg'){throw new TicketImageException('No se pudo convertir la imagen de un producto para el ticket.');}
  }
  return ['data'=>$data,'width'=>(int)$info[0],'height'=>(int)$info[1],'channels'=>(int)($info['channels']??3)];
 }
 private function image(string $data,string $mime,float $x,float $top,float $boxWidth,float $boxHeight): bool {
  $image=$this->imagenJpeg($data,$mime);if(!$image){return false;}
  $key=hash('sha256',$image['data']);
  if(!isset($this->imageNames[$key])){
   $name='Im'.(count($this->images)+1);$this->imageNames[$key]=$name;
   $this->images[$name]=$image;
  }
  $scale=min($boxWidth/$image['width'],$boxHeight/$image['height']);$width=$image['width']*$scale;$height=$image['height']*$scale;
  $x+=($boxWidth-$width)/2;$y=$top-($boxHeight-$height)/2-$height;
  $this->stream.='q '.$width.' 0 0 '.$height.' '.$x.' '.$y.' cm /'.$this->imageNames[$key]." Do Q\n";
  return true;
 }
 private function textAt(string $text,int $size,bool $bold,float $x,float $y,int $width): float {
  $lines=explode("\n",wordwrap($text,$width,"\n",true));
  foreach($lines as $line){$this->stream.='BT /'.($bold?'F2':'F1').' '.$size.' Tf 1 0 0 1 '.$x.' '.$y.' Tm ('.$this->encoded($line).") Tj ET\n";$y-=$size+7;}
  return $y;
 }
 public function product(array $item,?array $image): void {
  if($this->y<145){$this->page();}
  $top=$this->y;
  if($image && $this->image($image['imagen'],$image['imagen_tipo'],42,$top,82,64)){
   $cursor=$this->textAt($item['producto'],11,true,140,$top,62);
   $cursor=$this->textAt('Código: '.$item['codigo'],9,false,140,$cursor,65);
   $cursor=$this->textAt($item['cantidad'].' piezas x '.dinero((int)$item['precio_centavos']).'     Importe: '.dinero((int)$item['precio_centavos']*(int)$item['cantidad']),9,false,140,$cursor,65);
   $this->y=min($top-74,$cursor-4);
  }else{
   $this->text($item['producto'],11,true);$this->text('Código: '.$item['codigo'],9);
   $this->text($item['cantidad'].' piezas x '.dinero((int)$item['precio_centavos']).'     Importe: '.dinero((int)$item['precio_centavos']*(int)$item['cantidad']),10);
  }
  $this->gap(10);
 }
 public function barcode(string $code): void {
  if($this->y<105){$this->page();}
  $patterns=json_decode('{"0": "bsbSBsBsb", "1": "BsbSbsbsB", "2": "bsBSbsbsB", "3": "BsBSbsbsb", "4": "bsbSBsbsB", "5": "BsbSBsbsb", "6": "bsBSBsbsb", "7": "bsbSbsBsB", "8": "BsbSbsBsb", "9": "bsBSbsBsb", "A": "BsbsbSbsB", "B": "bsBsbSbsB", "C": "BsBsbSbsb", "D": "bsbsBSbsB", "E": "BsbsBSbsb", "F": "bsBsBSbsb", "G": "bsbsbSBsB", "H": "BsbsbSBsb", "I": "bsBsbSBsb", "J": "bsbsBSBsb", "K": "BsbsbsbSB", "L": "bsBsbsbSB", "M": "BsBsbsbSb", "N": "bsbsBsbSB", "O": "BsbsBsbSb", "P": "bsBsBsbSb", "Q": "bsbsbsBSB", "R": "BsbsbsBSb", "S": "bsBsbsBSb", "T": "bsbsBsBSb", "U": "BSbsbsbsB", "V": "bSBsbsbsB", "W": "BSBsbsbsb", "X": "bSbsBsbsB", "Y": "BSbsBsbsb", "Z": "bSBsBsbsb", "-": "bSbsbsBsB", ".": "BSbsbsBsb", " ": "bSBsbsBsb", "*": "bSbsBsBsb", "$": "bSbSbSbsb", "/": "bSbSbsbSb", "+": "bSbsbSbSb", "%": "bsbSbSbSb"}',true);$x=42;$scale=.8;
  foreach(str_split('*'.$code.'*') as $ch){if(!isset($patterns[$ch])){return;}foreach(str_split($patterns[$ch]) as $part){$w=(ctype_upper($part)?3:1)*$scale;if(strtolower($part)==='b'){$this->stream.=$x.' '.($this->y-32).' '.$w." 32 re f\n";}$x+=$w;}$x+=$scale;}
  $this->y-=48;$this->text($code,9);$this->gap();
 }
 public function output(): string {
  if($this->stream!==''){$this->pages[]=$this->stream;$this->stream='';}
  $objs=[1=>'<< /Type /Catalog /Pages 2 0 R >>'];$kids=[];
  $metrics=json_decode(file_get_contents(__DIR__.'/../assets/fonts/metrics.json'),true);
  foreach(['regular'=>3,'bold'=>4] as $kind=>$fontId){
   $m=$metrics[$kind];$descriptor=$fontId+2;$fileId=$fontId+4;$font=file_get_contents(__DIR__.'/../assets/fonts/'.$m['name'].'.ttf');$packed=gzcompress($font);
   $objs[$fontId]='<< /Type /Font /Subtype /TrueType /BaseFont /'.$m['name'].' /Encoding /WinAnsiEncoding /FirstChar 32 /LastChar 255 /Widths ['.implode(' ',$m['widths']).'] /FontDescriptor '.$descriptor.' 0 R >>';
   $objs[$descriptor]='<< /Type /FontDescriptor /FontName /'.$m['name'].' /Flags 32 /FontBBox ['.implode(' ',$m['bbox']).'] /ItalicAngle 0 /Ascent '.$m['ascent'].' /Descent '.$m['descent'].' /CapHeight 729 /StemV 80 /FontFile2 '.$fileId.' 0 R >>';
   $objs[$fileId]='<< /Length '.strlen($packed).' /Length1 '.strlen($font).' /Filter /FlateDecode >>'."\nstream\n".$packed."\nendstream";
  }
  $xobjects='';
  $imageIndex=0;
  foreach($this->images as $image){$id=9+$imageIndex;$colorSpace=$image['channels']===1?'/DeviceGray':($image['channels']===4?'/DeviceCMYK':'/DeviceRGB');$objs[$id]='<< /Type /XObject /Subtype /Image /Width '.$image['width'].' /Height '.$image['height'].' /ColorSpace '.$colorSpace.' /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($image['data']).">>\nstream\n".$image['data']."\nendstream";$xobjects.='/Im'.($imageIndex+1).' '.$id.' 0 R ';$imageIndex++;}
  $pageStart=9+count($this->images);
  foreach($this->pages as $i=>$s){$page=$pageStart+$i*2;$content=$page+1;$kids[]=$page.' 0 R';$s.='BT /F1 8 Tf 1 0 0 1 42 25 Tm (Guitarixta | '.($i+1).' / '.count($this->pages).") Tj ET\n";$objs[$page]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << '.$xobjects.'>> >> /Contents '.$content.' 0 R >>';$objs[$content]='<< /Length '.strlen($s).">>\nstream\n".$s."endstream";}
  $objs[2]='<< /Type /Pages /Count '.count($this->pages).' /Kids ['.implode(' ',$kids).'] >>';ksort($objs);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];
  foreach($objs as $i=>$obj){$offsets[$i]=strlen($pdf);$pdf.=$i." 0 obj\n".$obj."\nendobj\n";}
  $start=strlen($pdf);$pdf.='xref' ."\n0 ".(count($objs)+1)."\n0000000000 65535 f \n";
  for($i=1;$i<=count($objs);$i++){$pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);}
  return $pdf.'trailer << /Size '.(count($objs)+1).' /Root 1 0 R >>'."\nstartxref\n".$start."\n%%EOF\n";
 }
}
function construirTicket(array $p,array $items): string {
 $pdf=new TicketPDF();$pdf->page();$pdf->text('TICKET DE PEDIDO',15,true);$pdf->text('Folio: '.$p['folio'],11,true);$pdf->text('Fecha: '.$p['creado_en'].' (Ciudad de México)',9);
 if($p['stripe_modo']==='test'){$pdf->text('PRUEBA - SIN COBRO REAL',14,true);}
 $states=['pendiente_efectivo'=>'PENDIENTE DE PAGO EN EFECTIVO','pagado'=>'PAGADO','entregado'=>'PAGADO / ENTREGADO','cancelado'=>'CANCELADO','expirado'=>'PAGO EXPIRADO','pendiente_tarjeta'=>'PAGO CON TARJETA NO CONFIRMADO'];
 $pdf->text($states[$p['estado']]??$p['estado'],12,true);$pdf->gap();
 $pdf->text('Cliente: '.$p['nombre']);$pdf->text('Correo: '.$p['correo']);$pdf->text('Teléfono: '.$p['telefono']);$pdf->line();
 $productIds=array_values(array_unique(array_filter(array_map(static function($item){return (int)($item['producto_id']??0);},$items))));
 $images=[];
 if($productIds){$marks=implode(',',array_fill(0,count($productIds),'?'));$query=dbTienda()->prepare('SELECT id,imagen,imagen_tipo FROM productos WHERE id IN ('.$marks.')');$query->execute($productIds);foreach($query->fetchAll() as $row){$images[(int)$row['id']]=$row;}}
 foreach($items as $it){$pdf->product($it,$images[(int)($it['producto_id']??0)]??null);}
 $pdf->line();$pdf->text('Subtotal: '.dinero((int)$p['subtotal_centavos']),11);$pdf->text('IVA ('.(int)($p['iva_porcentaje']??0).'%): '.dinero((int)($p['iva_centavos']??0)),11);$pdf->text('Envío: '.dinero((int)$p['envio_centavos']),11);$pdf->text('TOTAL: '.dinero((int)$p['total_centavos']),15,true);$pdf->line();
 $pdf->text('Método de pago: '.($p['metodo']==='efectivo'?'Efectivo':'Tarjeta mediante Stripe'));
 if($p['estado']==='pendiente_efectivo'){$pdf->text('Paga el total al '.($p['entrega']==='domicilio'?'recibir tu pedido.':'recoger en tienda.'),10,true);}
 $pdf->text('Entrega: '.($p['entrega']==='domicilio'?'A domicilio':'Recoger en tienda'),11,true);
 $pdf->text($p['entrega']==='domicilio'?$p['direccion']:tiendaConfig()['tienda_direccion']);
 if($p['referencias']!==''){$pdf->text('Referencias: '.$p['referencias'],9);}
 if($p['latitud']!==null){$pdf->text('Ubicación: '.$p['latitud'].', '.$p['longitud'],9);}
 $pdf->gap(12);$pdf->barcode($p['folio']);$pdf->text('Conserva este ticket y presenta el folio al recoger.',9);$pdf->text('Este ticket no es una factura fiscal.',8);return $pdf->output();
}
