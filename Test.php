<?php
 
 // Include Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use SoftlandERP\Config;
use SoftlandERP\Client;
use SoftlandERP\ClienteHandler;
use SoftlandERP\FacturaHandler;
use SoftlandERP\ReciboHandler;
use SoftlandERP\AuxiliarCCHandler;
use SoftlandERP\NotaCreditoHandler;
use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Impuesto;
use SoftlandERP\Models\AuxiliarCC;
use SoftlandERP\Utils;

$options = [
    "DB_HOST"=> "192.168.0.3",
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


$codigoCliente = $handler->insertar($cliente);

$cliente->codigo = $codigoCliente;

echo "codigo cliente: " . $codigoCliente . "\n";


// insertar factura
//$tipo = Utils::decodificarTipoIdentificacion($cliente->nit);
//$nitConMascara = $handler->aplicarMascara($cliente->nit, $tipo);
//$codigoCliente = $handler->consultarCodigoClienteNit($nitConMascara);
// registrar factura
$facturas = new FacturaHandler($config);
$recibos = new ReciboHandler($config);
$notasCredito = new NotaCreditoHandler($config);
//$facturas->insertarCC("FAC-0002", $codigoCliente, 10000, 10000, 1);
//$facturas->insertarCC("FAC", "FAC-0004", $codigoCliente, 10000, 10000, 1);

$numFactura = "FAC-000104";
$numNotaCredito = "NC-000102";

$factura = new DocumentoCC();
$factura->documento = $numFactura;
$factura->tipoCambioDolar = 500;
$factura->moneda = "CRC";
$factura->subtotal = 1000;
$factura->centroCosto = "00-00-00";
$factura->cuentaContable = "1-06-04-000-000";
$factura->impuesto = 130;
$factura->fecha = "2025-03-24 08:00:00";
$factura->descuento = 0;
$factura->monto = 1130;
$factura->tipo = "FAC";
$factura->subtipo = 0;
$factura->cliente = $codigoCliente;
$factura->centroCostoImpuesto = "00-00-00";
$factura->cuentaContableImpuesto = "1-01-05-001-002";

$facturas->insertarDocumentoCC($factura);

//$impuesto = new Impuesto();
//$impuesto->centroCosto = "00-00-00";
//$impuesto->cuentaContable = "1-01-05-001-002";
$paquete = "CC";
$tipoAsiento = "CC";
$asiento =  $facturas->obtenerConsecutivoPaquete("CC");
echo "asiento factura: " . $asiento . "\n";
$facturas->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento);
$facturas->insertarDiario($factura, $cliente, null, $asiento);

$recibo = new DocumentoCC();
$recibo->documento = "REC-0002";
$recibo->tipo = "REC";
$recibo->suptipo = 0;
$recibo->tipoCambioDolar = 500;
$recibo->moneda = "CRC";
$recibo->subtotal = 1000;
$recibo->impuesto = 0;
$recibo->descuento = 0;
$recibo->centroCosto = "00-00-00";
$recibo->cuentaContable = "1-06-04-000-000";
$recibo->monto = 1000;
$recibo->fecha = "2025-03-24 09:53:00";
$recibo->aplicacion = "FAC-000101";
$recibo->referencia = "BNCR-0001";
$recibo->documentoAplicacion = "FAC-000101";
$recibo->cliente = $codigoCliente;

//$recibos->insertarDocumentoCC($recibo);

// crear auxiliar de recibo
/*$auxiliarHandler = new AuxiliarCCHandler($config);
$auxiliar = new AuxiliarCC();
$auxiliar->tipoCredito = $recibo->tipo;
$auxiliar->tipoDebito = $factura->tipo;
$auxiliar->docCredito = $recibo->documento;
$auxiliar->docDebito = $factura->documento;
$auxiliar->monto = $recibo->monto;
$auxiliar->tipoCambioDolar = $recibo->tipoCambioDolar;
$auxiliar->fecha = $recibo->fecha;
$auxiliarHandler->insertar($auxiliar);*/

//$asiento =  $recibos->obtenerConsecutivoPaquete("CC");
//echo "asiento recibo: " . $asiento . "\n";

//$recibos->insertarAsientoDeDiario($recibo, $asiento, $paquete, $tipoAsiento);
//$recibos->insertarDiario($recibo, $cliente, null, $asiento);

$notaCredito = new DocumentoCC();
$notaCredito->documento = $numNotaCredito;
$notaCredito->tipoCambioDolar = 500;
$notaCredito->moneda = "CRC";
$notaCredito->subtotal = 1000;
$notaCredito->centroCosto = "00-00-00";
$notaCredito->cuentaContable = "1-06-04-000-000";
$notaCredito->impuesto = 130;
$notaCredito->fecha = "2025-03-24 08:00:00";
$notaCredito->descuento = 0;
$notaCredito->monto = 1130;
$notaCredito->tipo = "FAC";
$notaCredito->subtipo = 0;
$notaCredito->cliente = $codigoCliente;
$notaCredito->centroCostoImpuesto = "00-00-00";
$notaCredito->cuentaContableImpuesto = "1-01-05-001-002";
$notaCredito->documentoAplicacion = $numFactura;
$notaCredito->referencia = $numFactura;
$notaCredito->aplicacion = $numFactura;

$notasCredito->insertarDocumentoCC($notaCredito);

//$impuesto = new Impuesto();
//$impuesto->centroCosto = "00-00-00";
//$impuesto->cuentaContable = "1-01-05-001-002";
$paquete = "CC";
$tipoAsiento = "CC";
$asiento =  $notasCredito->obtenerConsecutivoPaquete("CC");
echo "asiento nota credito: " . $asiento . "\n";
$notasCredito->insertarAsientoDeDiario($notaCredito, $asiento, $paquete, $tipoAsiento);
$notasCredito->insertarDiario($notaCredito, $cliente, null, $asiento);



