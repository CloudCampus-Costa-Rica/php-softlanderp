<?php
 
 // Include Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use SoftlandERP\Config;
use SoftlandERP\Client;
use SoftlandERP\ClienteHandler;
use SoftlandERP\FacturaHandler;
use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Impuesto;
use SoftlandERP\Utils;

$options = [
    "DB_HOST"=> "192.168.0.4",
    "DB_DATABASE" => "SOFTLAND",
    "DB_USERNAME" => "sa",
    "DB_PASSWORD"=> "Admin123",
    "DB_SCHEMA"=> "FUNDEPOS",
];
$config = new Config($options);

$handler = new ClienteHandler($config);

$cliente = new Cliente();
$cliente->nombre = "DIEGO VARELA ESPINOZA";
$cliente->nit = "1-1356-0858";
$cliente->telefono = "86678968";
$cliente->email = "dvarelae858@gmail.com";
$cliente->emailFE = "dvarelae858@gmail.com";
$cliente->direccion = "Urb la Ponderosa, primera entrada, casa #22";
$cliente->tipoImpuesto = "01";
$cliente->codigoTarifa = "08";
$cliente->tarifa = 13;
$cliente->gln = "ND";
$cliente->centroCosto = "00-00-00";
$cliente->cuentaContable = "2-01-13-000-000";


//$handler->insertar($cliente);


// insertar factura
//$tipo = Utils::decodificarTipoIdentificacion($cliente->nit);
//$nitConMascara = $handler->aplicarMascara($cliente->nit, $tipo);
//$codigoCliente = $handler->consultarCodigoClienteNit($nitConMascara);
// registrar factura
$facturas = new FacturaHandler($config);
//$facturas->insertarCC("FAC-0002", $codigoCliente, 10000, 10000, 1);
//$facturas->insertarCC("FAC", "FAC-0004", $codigoCliente, 10000, 10000, 1);
$paquete = "CC";
$tipoAsiento = "CC";
$asiento =  $facturas->obtenerConsecutivoPaquete("CC");
echo "asiento: " . $asiento . "\n";
$factura = new DocumentoCC();
$factura->documento = "FAC-0005";
$factura->tipoCambioDolar = 500;
$factura->moneda = "CRC";
$factura->subtotal = 1000;
$factura->centroCosto = "00-00-00";
$factura->cuentaContable = "1-06-04-000-000";
$factura->impuesto = 130;
$factura->fecha = "2025-03-20 13:55:00";
$factura->descuento = 0;
$factura->monto = 1130;

$impuesto = new Impuesto();
$impuesto->centroCosto = "00-00-00";
$impuesto->cuentaContable = "1-01-05-001-002";
$facturas->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento);
$facturas->insertarDiario($factura, $cliente, $impuesto, $asiento);
