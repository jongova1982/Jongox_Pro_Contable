<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

try {
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(500);
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Error de conexión</title>
    <style>body{font-family:system-ui, sans-serif;background:#f8f8f8;color:#111;padding:3rem}main{max-width:720px;margin:0 auto;background:#fff;padding:2rem;border:1px solid #ddd;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{margin-top:0;color:#c00}code{background:#f2f2f2;padding:.2rem .4rem;border-radius:.25rem;display:inline-block;}</style>
</head>
<body>
<main>
    <h1>Error de conexión a la base de datos</h1>
    <p>{$message}</p>
    <p>Verifique que MySQL esté iniciado y que <code>config.php</code> coincida con la configuración local de XAMPP.</p>
</main>
</body>
</html>
HTML;
    exit;
}

$section = $_GET['seccion'] ?? 'resumen';
$allowed = ['resumen','productos','clientes','proveedores','compras','ventas','bodega'];
if (!in_array($section, $allowed, true)) $section = 'resumen';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$notice = $_SESSION['notice'] ?? null; unset($_SESSION['notice']);

function rows(string $sql, array $params=[]): array { $s=db()->prepare($sql); $s->execute($params); return $s->fetchAll(); }
function one(string $sql): array { return db()->query($sql)->fetch() ?: []; }

$inventoryQuery = <<<SQL
SELECT p.codigo, p.descripcion, p.unidad_medida, p.costo, p.precio_venta, p.stock_minimo,
       COALESCE(SUM(CASE WHEN m.tipo='ENTRADA' THEN m.cantidad WHEN m.tipo='SALIDA' THEN -m.cantidad ELSE m.cantidad END),0) AS existencia,
       COALESCE(SUM(CASE WHEN m.tipo='ENTRADA' THEN m.cantidad WHEN m.tipo='SALIDA' THEN -m.cantidad ELSE m.cantidad END),0) * p.costo AS valor_inventario
FROM producto p
LEFT JOIN movimientos_bodega m ON m.codigo_producto=p.codigo
GROUP BY p.codigo, p.descripcion, p.unidad_medida, p.costo, p.precio_venta, p.stock_minimo
SQL;

$stats = one("SELECT (SELECT COUNT(*) FROM producto WHERE activo=1) productos,(SELECT COUNT(*) FROM cliente) clientes,(SELECT COALESCE(SUM(valor_inventario),0) FROM (" . $inventoryQuery . ") AS inv) inventario,(SELECT COALESCE(SUM(total),0) FROM ventas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())) ventas_mes");
$inventory = rows($inventoryQuery . ' ORDER BY descripcion');
$products = rows('SELECT p.*, pr.nombre proveedor FROM producto p LEFT JOIN proveedor pr ON pr.nit=p.nit_proveedor ORDER BY p.descripcion');
$clients = rows('SELECT * FROM cliente ORDER BY nombre');
$providers = rows('SELECT * FROM proveedor ORDER BY nombre');
$purchases = rows('SELECT c.*,p.descripcion,pr.nombre proveedor FROM compra c JOIN producto p ON p.codigo=c.codigo_producto JOIN proveedor pr ON pr.nit=c.nit_proveedor ORDER BY c.fecha DESC LIMIT 50');
$sales = rows('SELECT v.*,p.descripcion,c.nombre cliente FROM ventas v JOIN producto p ON p.codigo=v.codigo_producto JOIN cliente c ON c.id_cedula=v.id_cedula ORDER BY v.fecha DESC LIMIT 50');
$title = ['resumen'=>'Resumen contable','productos'=>'Catálogo de productos','clientes'=>'Clientes','proveedores'=>'Proveedores','compras'=>'Registro de compras','ventas'=>'Facturación de ventas','bodega'=>'Inventario de bodega'][$section];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#07162b"><title>Jongox Pro · <?=htmlspecialchars($title)?></title><link rel="stylesheet" href="assets/style.css?v=<?=filemtime(__DIR__.'/assets/style.css')?>"><link rel="stylesheet" href="assets/sales.css?v=<?=filemtime(__DIR__.'/assets/sales.css')?>"><link rel="stylesheet" href="assets/modern.css?v=<?=filemtime(__DIR__.'/assets/modern.css')?>"><link rel="stylesheet" href="assets/devices.css?v=<?=filemtime(__DIR__.'/assets/devices.css')?>"><link rel="stylesheet" href="assets/background.css?v=<?=filemtime(__DIR__.'/assets/background.css')?>"><link rel="stylesheet" href="assets/status.css?v=<?=filemtime(__DIR__.'/assets/status.css')?>"><link rel="stylesheet" href="assets/sidebar-global.css?v=<?=filemtime(__DIR__.'/assets/sidebar-global.css')?>"><link rel="stylesheet" href="assets/antenna.css?v=<?=filemtime(__DIR__.'/assets/antenna.css')?>"></head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<aside class="sidebar" id="sidebar"><a class="brand" href="?seccion=resumen" aria-label="Jongox Pro, inicio"><img src="assets/brand/jongox-pro-logo.png" alt="Jongox Pro Contable"></a><nav aria-label="Navegación principal">
<?php foreach(['resumen'=>['▦','Resumen'],'productos'=>['◇','Productos'],'clientes'=>['♙','Clientes'],'proveedores'=>['⌂','Proveedores'],'compras'=>['↓','Compras'],'ventas'=>['↑','Ventas'],'bodega'=>['▤','Bodega']] as $key=>$nav): ?>
<a class="<?=$section===$key?'active':''?>" href="?seccion=<?=$key?>" <?=$section===$key?'aria-current="page"':''?>><i aria-hidden="true"><?=$nav[0]?></i><?=$nav[1]?></a><?php endforeach; ?></nav><div class="sidebar-foot antenna-status online" id="antenna-status" role="status" aria-label="Jongox Contable conectado"><div class="antenna-stage"><span class="antenna-wave wave-one" aria-hidden="true"></span><span class="antenna-wave wave-two" aria-hidden="true"></span><img class="antenna-part antenna-dish" src="assets/brand/jongox-antenna.png" alt="Antena de conexión Jongox"><img class="antenna-part antenna-mast" src="assets/brand/jongox-antenna.png" alt="" aria-hidden="true"></div><div class="antenna-caption"><span class="antenna-light" aria-hidden="true"></span><small>Jongox Contable v1.0</small></div></div></aside>
<button class="nav-overlay" type="button" aria-label="Cerrar menú" tabindex="-1"></button>
<main id="contenido" tabindex="-1"><header><div><button class="menu" type="button" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">☰</button><p>JONGOX PRO&nbsp;&nbsp;/&nbsp;&nbsp;<?=strtoupper($section)?></p><h1><?=$title?></h1></div><div class="header-actions"><div class="connection-status online" id="connection-status" role="status" aria-live="polite"><span class="signal" aria-hidden="true"></span><span class="connection-text">Operación en línea</span><span class="tech-line" aria-hidden="true"></span></div><div class="user"><span class="avatar" aria-hidden="true"><img src="assets/brand/jongox-pro-logo.png" alt=""></span><div><b>JX Admin</b><small><?=date('d/m/Y')?></small></div></div></div></header>
<?php if($notice): ?><div class="notice"><?=htmlspecialchars($notice)?></div><?php endif; ?>

<?php if($section==='resumen'): ?>
<section class="cards"><article><div class="card-icon blue">▤</div><div><small>VALOR DEL INVENTARIO</small><strong><?=money($stats['inventario'])?></strong><em>Existencias valorizadas</em></div></article><article><div class="card-icon green">↗</div><div><small>VENTAS DEL MES</small><strong><?=money($stats['ventas_mes'])?></strong><em>Facturación acumulada</em></div></article><article><div class="card-icon amber">◇</div><div><small>PRODUCTOS ACTIVOS</small><strong><?=$stats['productos']?></strong><em>En el catálogo</em></div></article><article><div class="card-icon violet">♙</div><div><small>CLIENTES</small><strong><?=$stats['clientes']?></strong><em>Registrados</em></div></article></section>
<section class="grid"><article class="panel"><div class="panel-head"><div><h2>Estado de inventario</h2><p>Productos con menor disponibilidad</p></div><a href="?seccion=bodega">Ver bodega →</a></div><?php table_inventory(array_slice($inventory,0,6)); ?></article><article class="panel quick"><div class="panel-head"><div><h2>Acciones rápidas</h2><p>Operaciones frecuentes</p></div></div><a href="?seccion=compras"><b>↓</b><span><strong>Nueva compra</strong><small>Registrar entrada a bodega</small></span>›</a><a href="?seccion=ventas"><b>↑</b><span><strong>Nueva venta</strong><small>Facturar y descontar inventario</small></span>›</a><a href="?seccion=productos"><b>◇</b><span><strong>Crear producto</strong><small>Agregar al catálogo</small></span>›</a></article></section>
<?php elseif(in_array($section,['productos','clientes','proveedores'],true)): ?>
<?php $masterData = ['productos'=>$products, 'clientes'=>$clients, 'proveedores'=>$providers][$section]; render_master($section, $masterData); ?>
<?php elseif($section==='compras' || $section==='ventas'): render_transaction($section, $section==='compras'?$purchases:$sales, $products, $section==='compras'?$providers:$clients); ?>
<?php else: ?><section class="panel"><div class="panel-head"><div><h2>Existencias actuales</h2><p>Entradas por compras y salidas por ventas</p></div><span class="value">Total: <?=money($stats['inventario'])?></span></div><?php table_inventory($inventory); ?></section><?php endif; ?>
</main><script src="assets/app.js?v=<?=filemtime(__DIR__.'/assets/app.js')?>"></script></body></html>
<?php
function table_inventory(array $data): void { ?><div class="table-wrap"><table><thead><tr><th>Código</th><th>Producto</th><th>Existencia</th><th>Costo</th><th>Valor</th><th>Estado</th></tr></thead><tbody><?php if(!$data):?><tr><td colspan="6" class="empty">Aún no hay productos en bodega.</td></tr><?php endif; foreach($data as $r): $low=(float)$r['existencia']<=(float)$r['stock_minimo'];?><tr><td><code><?=htmlspecialchars($r['codigo'])?></code></td><td><b><?=htmlspecialchars($r['descripcion'])?></b><small><?=$r['unidad_medida']?></small></td><td><?=number_format((float)$r['existencia'],3,',','.')?></td><td><?=money($r['costo'])?></td><td><?=money($r['valor_inventario'])?></td><td><span class="status <?=$low?'low':'ok'?>"><?=$low?'Stock bajo':'Disponible'?></span></td></tr><?php endforeach;?></tbody></table></div><?php }
function render_master(string $type,array $data): void { $labels=['productos'=>'producto','clientes'=>'cliente','proveedores'=>'proveedor']; ?><section class="split"><article class="panel form-panel"><div class="panel-head"><div><h2>Nuevo <?=$labels[$type]?></h2><p>Complete los datos requeridos</p></div></div><form method="post" action="action.php"><input type="hidden" name="action" value="<?=$type?>"><?php if($type==='productos'):?><label>Código<input name="codigo" maxlength="30" required placeholder="Ej. PRD-001"></label><label>Descripción<input name="descripcion" required></label><div class="fields"><label>Unidad<select name="unidad"><option>UND</option><option>PESO</option><option>MT</option></select></label><label>Proveedor<select name="proveedor"><option value="">Sin asignar</option><?php foreach(rows('SELECT nit,nombre FROM proveedor ORDER BY nombre') as $p):?><option value="<?=$p['nit']?>"><?=htmlspecialchars($p['nombre'])?></option><?php endforeach;?></select></label></div><div class="fields"><label>Costo<input type="number" name="costo" min="0" step="0.01" required></label><label>Precio de venta<input type="number" name="precio" min="0" step="0.01" required></label></div><label>Stock mínimo<input type="number" name="minimo" min="0" step="0.001" value="5"></label><?php elseif($type==='clientes'):?><label>Cédula / NIT<input name="id" required></label><label>Nombre completo<input name="nombre" required></label><label>Teléfono<input name="telefono"></label><label>Correo<input type="email" name="correo"></label><?php else:?><label>NIT<input name="id" required></label><label>Razón social<input name="nombre" required></label><div class="fields"><label>Ciudad<input name="ciudad"></label><label>Teléfono<input name="telefono"></label></div><label>Dirección<input name="direccion"></label><label>Correo<input type="email" name="correo"></label><?php endif;?><button class="primary">Guardar <?=$labels[$type]?></button></form></article><article class="panel"><div class="panel-head"><div><h2>Registros</h2><p><?=count($data)?> elementos encontrados</p></div></div><div class="table-wrap"><table><thead><tr><?php foreach(array_keys($data[0]??[]) as $h):?><th><?=str_replace('_',' ',ucfirst($h))?></th><?php endforeach;?></tr></thead><tbody><?php if(!$data):?><tr><td class="empty">No hay registros todavía.</td></tr><?php endif; foreach($data as $r):?><tr><?php foreach($r as $v):?><td><?=htmlspecialchars((string)($v??'—'))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div></article></section><?php }
function render_transaction(string $type,array $data,array $products,array $parties): void { $buy=$type==='compras';?><section class="split"><article class="panel form-panel"><div class="panel-head"><div><h2><?=$buy?'Nueva compra':'Nueva venta'?></h2><p><?=$buy?'La cantidad ingresará a bodega':'Se validará la existencia disponible'?></p></div></div><form method="post" action="action.php"><input type="hidden" name="action" value="<?=$type?>"><label>Producto<select name="producto" id="product" required><option value="">Seleccione…</option><?php foreach($products as $p):?><option value="<?=$p['codigo']?>" data-price="<?=$buy?$p['costo']:$p['precio_venta']?>" data-unit="<?=$p['unidad_medida']?>"><?=htmlspecialchars($p['codigo'].' · '.$p['descripcion'])?></option><?php endforeach;?></select></label><?php if(!$buy):?><fieldset class="sale-type"><legend>Tipo de cliente</legend><label><input type="radio" name="tipo_cliente" value="detal" checked> Venta al detal</label><label><input type="radio" name="tipo_cliente" value="registrado"> Cliente registrado</label></fieldset><?php endif;?><label id="party-field" class="<?=$buy?'':'hidden'?>"><?=$buy?'Proveedor':'Cliente registrado'?><select name="party" <?=$buy?'required':''?>><option value="">Seleccione…</option><?php foreach($parties as $p): if(!$buy && $p['id_cedula']==='CF') continue;?><option value="<?=$buy?$p['nit']:$p['id_cedula']?>"><?=htmlspecialchars($p['nombre'])?></option><?php endforeach;?></select><?php if(!$buy):?><small>¿No aparece? <a href="?seccion=clientes">Registrar nuevo cliente</a></small><?php endif;?></label><div class="fields"><label>Cantidad<input type="number" name="cantidad" id="qty" min="0.001" step="0.001" required></label><label><?=$buy?'Costo':'Precio'?> unitario<input type="number" name="precio" id="price" min="0" step="0.01" required></label></div><div class="total-preview"><span>Total estimado</span><strong id="total">$ 0</strong></div><button class="primary">Registrar <?=$buy?'compra':'venta'?></button></form></article><article class="panel"><div class="panel-head"><div><h2>Últimas <?=$type?></h2><p>Movimientos registrados</p></div></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Producto</th><th><?=$buy?'Proveedor':'Cliente'?></th><th>Cantidad</th><th>Total</th></tr></thead><tbody><?php if(!$data):?><tr><td colspan="5" class="empty">No hay movimientos todavía.</td></tr><?php endif; foreach($data as $r):?><tr><td><?=date('d/m/Y H:i',strtotime($r['fecha']))?></td><td><b><?=htmlspecialchars($r['descripcion'])?></b><small><?=$r['codigo_producto']?></small></td><td><?=htmlspecialchars($r[$buy?'proveedor':'cliente'])?></td><td><?=$r['cantidad'].' '.$r['unidad_medida']?></td><td><b><?=money($r['total'])?></b></td></tr><?php endforeach;?></tbody></table></div></article></section><?php }
