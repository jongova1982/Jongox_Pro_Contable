<?php
declare(strict_types=1);
session_start(); require __DIR__.'/config.php';
$pdo=db(); $a=$_POST['action']??'';
try {
  if($a==='productos') { $s=$pdo->prepare('INSERT INTO producto(codigo,descripcion,unidad_medida,nit_proveedor,costo,precio_venta,stock_minimo) VALUES(?,?,?,?,?,?,?)'); $s->execute([trim($_POST['codigo']),trim($_POST['descripcion']),$_POST['unidad'],$_POST['proveedor']?:null,$_POST['costo'],$_POST['precio'],$_POST['minimo']]); }
  elseif($a==='clientes') { $s=$pdo->prepare('INSERT INTO cliente(id_cedula,nombre,telefono,correo) VALUES(?,?,?,?)'); $s->execute([trim($_POST['id']),trim($_POST['nombre']),trim($_POST['telefono']),trim($_POST['correo'])]); }
  elseif($a==='proveedores') { $s=$pdo->prepare('INSERT INTO proveedor(nit,nombre,ciudad,telefono,direccion,correo_electronico) VALUES(?,?,?,?,?,?)'); $s->execute([trim($_POST['id']),trim($_POST['nombre']),trim($_POST['ciudad']),trim($_POST['telefono']),trim($_POST['direccion']),trim($_POST['correo'])]); }
  elseif($a==='compras'||$a==='ventas') {
    $pdo->beginTransaction(); $code=$_POST['producto']; $qty=(float)$_POST['cantidad']; $price=(float)$_POST['precio'];
    $p=$pdo->prepare('SELECT unidad_medida FROM producto WHERE codigo=? FOR UPDATE'); $p->execute([$code]); $product=$p->fetch(); if(!$product) throw new RuntimeException('El producto no existe.');
    if($a==='ventas') { $q=$pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='ENTRADA' THEN cantidad WHEN tipo='SALIDA' THEN -cantidad ELSE cantidad END),0) stock FROM movimientos_bodega WHERE codigo_producto=?"); $q->execute([$code]); if((float)$q->fetchColumn()<$qty) throw new RuntimeException('No hay existencias suficientes para esta venta.'); }
    if($a==='compras') { $s=$pdo->prepare('INSERT INTO compra(codigo_producto,cantidad,unidad_medida,nit_proveedor,costo_unitario,total) VALUES(?,?,?,?,?,?)'); $s->execute([$code,$qty,$product['unidad_medida'],$_POST['party'],$price,$qty*$price]); $id=$pdo->lastInsertId(); $kind='ENTRADA'; $ref='COMPRA-'.$id; $pdo->prepare('UPDATE producto SET costo=? WHERE codigo=?')->execute([$price,$code]); }
    else {
      $customer = ($_POST['tipo_cliente'] ?? 'registrado') === 'detal' ? 'CF' : ($_POST['party'] ?? '');
      if($customer === '') throw new RuntimeException('Seleccione un cliente registrado o elija venta al detal.');
      $s=$pdo->prepare('INSERT INTO ventas(codigo_producto,cantidad,unidad_medida,id_cedula,precio_unitario,total) VALUES(?,?,?,?,?,?)');
      $s->execute([$code,$qty,$product['unidad_medida'],$customer,$price,$qty*$price]);
      $id=$pdo->lastInsertId(); $kind='SALIDA'; $ref='VENTA-'.$id;
    }
    $pdo->prepare('INSERT INTO movimientos_bodega(codigo_producto,tipo,cantidad,referencia) VALUES(?,?,?,?)')->execute([$code,$kind,$qty,$ref]); $pdo->commit();
  } else throw new RuntimeException('Operación no válida.');
  $_SESSION['notice']='Registro guardado correctamente.';
} catch(Throwable $e) {
  if($pdo->inTransaction()) $pdo->rollBack();
  $message = $e instanceof PDOException
    ? 'Revise que el producto, cliente o proveedor seleccionado siga disponible.'
    : $e->getMessage();
  $_SESSION['notice']='No se pudo guardar: '.$message;
}
header('Location: index.php?seccion='.urlencode($a)); exit;
