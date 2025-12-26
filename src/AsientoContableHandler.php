<?php

namespace SoftlandERP;

use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Diario;

class AsientoContableHandler extends SoftlandHandler
{
    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * @param array $lineas Array de arrays, donde cada elemento es una línea del diario
     * @param float $tipoCambio Tipo de cambio (aplica a todas las líneas)
     * @param string|null $notas Notas opcionales
     * @param string|null $nit NIT opcional (puede ser null)
     * @param string|null $paquete Paquete para ASIENTO_DE_DIARIO (opcional, si se proporciona se inserta la cabecera)
     * @param string|null $tipoAsiento Tipo de asiento para ASIENTO_DE_DIARIO (opcional)
     * @param string|null $fecha Fecha del asiento (opcional, se toma de la primera línea si no se proporciona)
     * @param \PDO|null $pdo Conexión PDO opcional para transacciones
     * @return void
     */
    public function generarAsientoContable($lineas, $tipoCambio, $notas = null, $nit = null, $paquete = null, $tipoAsiento = null, $fecha = null, $pdo = null)
    {
        // Validar que el array de líneas no esté vacío
        if (empty($lineas)) {
            throw new \InvalidArgumentException("El array de líneas no puede estar vacío");
        }

        $diarioLineas = [];
        $consecutivo = 1;
        $numeroAsiento = null;
        $monedaPrincipal = null;

        // Iterar sobre cada línea del array
        foreach ($lineas as $lineaData) {
            // Validar campos requeridos
            if (!isset($lineaData['numeroAsiento']) || 
                !isset($lineaData['centroCosto']) || 
                !isset($lineaData['cuentaContable']) || 
                !isset($lineaData['fuente']) || 
                !isset($lineaData['referencia']) || 
                !isset($lineaData['monto']) || 
                !isset($lineaData['tipoMov']) ||
                !isset($lineaData['moneda'])) {
                throw new \InvalidArgumentException("Faltan campos requeridos en la línea del diario");
            }

            // Obtener número de asiento y fecha de la primera línea
            if ($numeroAsiento === null) {
                $numeroAsiento = $lineaData['numeroAsiento'];
                $monedaPrincipal = strtoupper($lineaData['moneda']);
                if ($fecha === null && isset($lineaData['fecha'])) {
                    $fecha = $lineaData['fecha'];
                }
            }

            // Crear un objeto Diario
            $linea = new Diario();
            $linea->asiento = $lineaData['numeroAsiento'];
            $linea->consecutivo = $consecutivo++;
            $linea->nit = $nit;
            $linea->centroCosto = $lineaData['centroCosto'];
            $linea->cuentaContable = $lineaData['cuentaContable'];
            $linea->fuente = $lineaData['fuente'];
            $linea->referencia = $lineaData['referencia'];
            $linea->tipoCambio = $tipoCambio;

            // Asignar campos opcionales como null
            $linea->baseLocal = null;
            $linea->baseDolar = null;
            $linea->debitoUnidades = null;
            $linea->creditoUnidades = null;

            $monto = floatval($lineaData['monto']);
            $moneda = strtoupper($lineaData['moneda']);
            $tipoMov = strtolower($lineaData['tipoMov']);

            // Determinar si es débito o crédito según tipo_mov
            if ($tipoMov == "debito") {
                // Asignar débito, crédito = null
                if ($moneda == "CRC") {
                    $linea->debitoLocal = $monto;
                    $linea->debitoDolar = round($monto / $tipoCambio, 2);
                } else {
                    $linea->debitoDolar = $monto;
                    $linea->debitoLocal = round($monto * $tipoCambio, 2);
                }
                $linea->creditoLocal = null;
                $linea->creditoDolar = null;
            } elseif ($tipoMov == "credito") {
                // Asignar crédito, débito = null
                if ($moneda == "CRC") {
                    $linea->creditoLocal = $monto;
                    $linea->creditoDolar = round($monto / $tipoCambio, 2);
                } else {
                    $linea->creditoDolar = $monto;
                    $linea->creditoLocal = round($monto * $tipoCambio, 2);
                }
                $linea->debitoLocal = null;
                $linea->debitoDolar = null;
            } else {
                throw new \InvalidArgumentException("tipo_mov debe ser 'debito' o 'credito', se recibió: " . $tipoMov);
            }

            $diarioLineas[] = $linea;
        }

        // Si se proporciona paquete y tipoAsiento, insertar también ASIENTO_DE_DIARIO usando el método heredado
        if ($paquete !== null && $tipoAsiento !== null) {
            // Calcular totales desde las líneas
            $totalDebitoLocal = 0;
            $totalDebitoDolar = 0;
            $totalCreditoLocal = 0;
            $totalCreditoDolar = 0;

            foreach ($diarioLineas as $linea) {
                if ($linea->debitoLocal !== null) {
                    $totalDebitoLocal += $linea->debitoLocal;
                }
                if ($linea->debitoDolar !== null) {
                    $totalDebitoDolar += $linea->debitoDolar;
                }
                if ($linea->creditoLocal !== null) {
                    $totalCreditoLocal += $linea->creditoLocal;
                }
                if ($linea->creditoDolar !== null) {
                    $totalCreditoDolar += $linea->creditoDolar;
                }
            }

            // Redondear totales
            $totalDebitoLocal = round($totalDebitoLocal, 2);
            $totalDebitoDolar = round($totalDebitoDolar, 2);
            $totalCreditoLocal = round($totalCreditoLocal, 2);
            $totalCreditoDolar = round($totalCreditoDolar, 2);

            // Calcular monto para DocumentoCC temporal (usar el mayor entre débito y crédito)
            // El método heredado insertarAsientoDeDiario establece TOTAL_DEBITO = TOTAL_CREDITO = TOTAL_CONTROL = monto
            if ($monedaPrincipal == "CRC") {
                $montoControl = max($totalDebitoLocal, $totalCreditoLocal);
            } else {
                $montoControl = max($totalDebitoDolar, $totalCreditoDolar);
            }

            // Si no se proporciona fecha, usar fecha actual
            if ($fecha === null) {
                $fecha = date('Y-m-d H:i:s');
            }

            // Crear DocumentoCC temporal para usar con el método heredado
            $documentoTemporal = new DocumentoCC();
            $documentoTemporal->monto = $montoControl;
            $documentoTemporal->moneda = $monedaPrincipal;
            $documentoTemporal->fecha = $fecha;
            $documentoTemporal->tipoCambioDolar = $tipoCambio;

            // Llamar al método heredado insertarAsientoDeDiario
            $this->insertarAsientoDeDiario($documentoTemporal, $numeroAsiento, $paquete, $tipoAsiento, $pdo, $notas);
        }

        // Insertar todas las líneas en la tabla DIARIO
        $this->insertarLineasDiario($diarioLineas, $pdo);
    }

    /**
     * Inserta las líneas del diario en la base de datos
     * @param array $lineas Array de objetos Diario
     * @param \PDO|null $pdo Conexión PDO opcional
     * @return void
     */
    private function insertarLineasDiario($lineas, $pdo = null)
    {
        $esquema = $this->config->get('DB_SCHEMA');
        $sql = " INSERT INTO {$esquema}.DIARIO (ASIENTO, CONSECUTIVO, NIT, CENTRO_COSTO, CUENTA_CONTABLE, FUENTE, REFERENCIA,
               DEBITO_LOCAL, DEBITO_DOLAR, CREDITO_LOCAL, CREDITO_DOLAR, DEBITO_UNIDADES, CREDITO_UNIDADES,
               TIPO_CAMBIO, BASE_LOCAL, BASE_DOLAR) VALUES(:ASIENTO, :CONSECUTIVO, :NIT, :CENTRO_COSTO, :CUENTA_CONTABLE, :FUENTE, :REFERENCIA,
               :DEBITO_LOCAL, :DEBITO_DOLAR, :CREDITO_LOCAL, :CREDITO_DOLAR, :DEBITO_UNIDADES, :CREDITO_UNIDADES,
               :TIPO_CAMBIO, :BASE_LOCAL, :BASE_DOLAR);";

        // Use provided PDO connection or get a new one
        $usePdo = $pdo ?: $this->db->getConnection();

        // Remove transaction handling if PDO was provided
        $newTransaction = !$pdo;
        if ($newTransaction) {
            $usePdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
            $usePdo->beginTransaction();
        }

        try {
            foreach ($lineas as $linea) {
                $stmt = $usePdo->prepare($sql);
                $stmt->bindParam(':ASIENTO', $linea->asiento, \PDO::PARAM_STR);
                $stmt->bindParam(':CONSECUTIVO', $linea->consecutivo);
                $stmt->bindParam(':NIT', $linea->nit, \PDO::PARAM_STR);
                $stmt->bindParam(':CENTRO_COSTO', $linea->centroCosto, \PDO::PARAM_STR);
                $stmt->bindParam(':CUENTA_CONTABLE', $linea->cuentaContable, \PDO::PARAM_STR);
                $stmt->bindParam(':FUENTE', $linea->fuente, \PDO::PARAM_STR);
                $stmt->bindParam(':REFERENCIA', $linea->referencia, \PDO::PARAM_STR);
                $stmt->bindParam(':DEBITO_LOCAL', $linea->debitoLocal);
                $stmt->bindParam(':DEBITO_DOLAR', $linea->debitoDolar);
                $stmt->bindParam(':CREDITO_LOCAL', $linea->creditoLocal);
                $stmt->bindParam(':CREDITO_DOLAR', $linea->creditoDolar);
                $stmt->bindParam(':DEBITO_UNIDADES', $linea->debitoUnidades);
                $stmt->bindParam(':CREDITO_UNIDADES', $linea->creditoUnidades);
                $stmt->bindParam(':TIPO_CAMBIO', $linea->tipoCambio);
                $stmt->bindParam(':BASE_LOCAL', $linea->baseLocal);
                $stmt->bindParam(':BASE_DOLAR', $linea->baseDolar);
                $stmt->execute();
            }

            if ($newTransaction) {
                $usePdo->commit();
            }
        } catch (\PDOException $e) {
            if ($newTransaction) {
                $usePdo->rollBack();
            }
            throw new \RuntimeException("Error executing insert statement: " . $e->getMessage());
        }
    }

    /**
     * Implementación del método abstracto insertarDiario
     * Este método es requerido por la clase padre pero no se usa en este handler
     * Se implementa como stub que lanza una excepción indicando que se debe usar generarAsientoContable
     * 
     * @param DocumentoCC $documento
     * @param Cliente $cliente
     * @param array $impuestos
     * @param string $asiento
     * @param \PDO|null $pdo
     * @return void
     */
    public function insertarDiario($documento, $cliente, $impuestos, $asiento, $pdo = null)
    {
        throw new \BadMethodCallException(
            "AsientoContableHandler no utiliza insertarDiario. " .
            "Use generarAsientoContable() en su lugar para generar asientos contables desde un array de líneas."
        );
    }
}

