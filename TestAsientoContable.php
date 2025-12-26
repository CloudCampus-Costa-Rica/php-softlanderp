<?php
 
// Include Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use SoftlandERP\Config;
use SoftlandERP\SoftlandConnector;

$options = [
    "DB_HOST"=> "192.168.100.8",
    "DB_DATABASE" => "SOFTLAND",
    "DB_USERNAME" => "sa",
    "DB_PASSWORD"=> "Admin123",
    "DB_SCHEMA"=> "FUNDEPOS",
];
$config = new Config($options);

$connector = new SoftlandConnector($config);

// Crear array de líneas del diario con estructura camelCase
// Ejemplo: Asiento contable con débito y crédito
$lineas = [
    [
        'numeroAsiento' => '', // Se actualizará automáticamente con el consecutivo
        'centroCosto' => '00-00-00',
        'cuentaContable' => '1-06-04-000-000', // Cuenta de activo (débito)
        'fuente' => 'TEST-ASIENTO-001',
        'referencia' => 'REF-ASIENTO-001',
        'monto' => 1000,
        'tipoMov' => 'debito',
        'moneda' => 'CRC',
        'fecha' => '2025-03-24 10:00:00'
    ],
    [
        'numeroAsiento' => '', // Se actualizará automáticamente con el consecutivo
        'centroCosto' => '00-00-00',
        'cuentaContable' => '2-01-13-000-000', // Cuenta de pasivo o ingresos (crédito)
        'fuente' => 'TEST-ASIENTO-001',
        'referencia' => 'REF-ASIENTO-001',
        'monto' => 1000,
        'tipoMov' => 'credito',
        'moneda' => 'CRC',
        'fecha' => '2025-03-24 10:00:00'
    ]
];

$tipoCambio = 500;
$paquete = "CC";
$tipoAsiento = "CC";
$nit = NULL;//"1-1356-0858"; // NIT opcional
$fecha = date('Y-m-d H:i:s'); // Fecha opcional

echo "=== Prueba de registrar_asiento ===\n";
echo "Tipo de cambio: " . $tipoCambio . "\n";
echo "Paquete: " . $paquete . "\n";
echo "Tipo de asiento: " . $tipoAsiento . "\n";
echo "Número de líneas: " . count($lineas) . "\n";
echo "\n";

$notas = "Recibo No.27303 por concepto de Abonos a Letra| Letra No.463189";

try {
    echo "Registrando asiento contable...\n";
    $connector->registrar_asiento($lineas, $tipoCambio, $paquete, $tipoAsiento, $notas, $nit, $fecha);
    echo "✓ Asiento contable registrado exitosamente\n";
    echo "El número de asiento se asignó automáticamente a todas las líneas\n";
} catch (\Exception $e) {
    echo "✗ Error al registrar asiento: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

