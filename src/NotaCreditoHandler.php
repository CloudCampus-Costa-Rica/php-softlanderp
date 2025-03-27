<?php

namespace SoftlandERP;
use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\Diario;
use SoftlandERP\Models\Impuesto;

class NotaCreditoHandler extends SoftlandHandler
{

    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * @param DocumentoCC $documento
     * @param Cliente $cliente
     * @param array $impuestos
     * @param string $asiento
     * @return void
     */
    public function insertarDiario($documento, $cliente, $impuestos, $asiento)
    {
        $ln = ["p", "d"];
        $lineas = [];
        if ($documento->impuesto > 0) {
            $ln[] = "i";
        }
        $global = 1;
        // insertar primera linea debito
        $linea = new Diario();
        $linea->asiento = $asiento;
        $linea->consecutivo = $global++;
        $linea->nit = $cliente->nit;
        $linea->centroCosto = $cliente->centroCosto;
        $linea->cuentaContable = $cliente->cuentaContable;
        $linea->fuente = $documento->documento;
        $linea->referencia = $documento->documento;

        if ($documento->moneda == "CRC") {
            $linea->creditoLocal = $documento->monto;
            $linea->creditoDolar = round($documento->monto / $documento->tipoCambioDolar, 2);
        } else {
            $linea->creditoLocal = round($documento->monto * $documento->tipoCambioDolar, 2);
            $linea->creditoDolar = $documento->monto;
        }
        $linea->debitoLocal = null;
        $linea->debitoDolar = null;
        $linea->baseLocal = null;
        $linea->baseDolar = null;
        $linea->debitoUnidades = null;
        $linea->creditoUnidades = null;
        $linea->tipoCambio = $documento->tipoCambioDolar;
        $lineas[] = $linea;


        for ($i = 0; $i < count($ln); $i++) {
            $linea = new Diario();
            $linea->asiento = $asiento;
            $linea->consecutivo = $global;
            $linea->nit = $cliente->nit;
            // se obtiene del subtipo
            if ($ln[$i] == "p") {
                $linea->centroCosto = $documento->centroCosto;
                $linea->cuentaContable = $documento->cuentaContable;
            }
            if ($ln[$i] == "i" && $documento->centroCostoImpuesto && $documento->cuentaContableImpuesto) {

                $linea->centroCosto = $documento->centroCostoImpuesto;
                $linea->cuentaContable = $documento->cuentaContableImpuesto;
            }

            $linea->fuente = $documento->documento;
            $linea->referencia = $documento->documento;

            if ($ln[$i] == "p") // credito producto
            {
                if ($documento->moneda == "CRC") {
                    $linea->debitoLocal = $documento->subtotal;
                    $linea->debitoDolar = round($documento->subtotal / $documento->tipoCambioDolar, 2);
                } else {
                    $linea->debitoLocal = round($documento->subtotal * $documento->tipoCambioDolar, 2);
                    $linea->debitoDolar = $documento->subtotal;
                }
                $linea->creditoLocal = null;
                $linea->creditoDolar = null;
                $linea->baseLocal = null;
                $linea->baseDolar = null;
            }

            if ($ln[$i] == "d") // debito descuento
            {
                if ($documento->descuento == 0) {
                    continue;
                }

                if ($documento->moneda == "CRC") {
                    $linea->creditoLocal = $documento->descuento;
                    $linea->creditoDolar = round($documento->descuento / $documento->tipoCambioDolar, 2);
                } else {
                    $linea->creditoLocal = round($documento->descuento * $documento->tipoCambioDolar, 2);
                    $linea->creditoDolar = $documento->descuento;
                }

                $linea->debitoLocal = null;
                $linea->debitoDolar = null;
                $linea->baseLocal = null;
                $linea->baseDolar = null;
            }

            if ($ln[$i] == "i") // CREDITO IVA
            {

                if ($documento->impuesto == 0) {
                    continue;
                }

                $linea->creditoLocal = null;
                $linea->creditoDolar = null;

                if ($documento->moneda == "CRC") {
                    $linea->debitoLocal = $documento->impuesto;
                    $linea->debitoDolar = round($documento->impuesto / $documento->tipoCambioDolar, 2);

                    $linea->baseLocal = $documento->subtotal;
                    $linea->baseDolar = round($documento->subtotal / $documento->tipoCambioDolar, 2);
                } else {
                    $linea->debitoLocal = round($documento->impuesto * $documento->tipoCambioDolar, 2);
                    $linea->debitoDolar = $documento->impuesto;

                    $linea->baseLocal = round($documento->subtotal * $documento->tipoCambioDolar, 2);
                    $linea->baseDolar = $documento->subtotal;
                }
            }
            $linea->debitoUnidades = null;
            $linea->creditoUnidades = null;
            $linea->tipoCambio = $documento->tipoCambioDolar;
            $lineas[] = $linea;
            $global++;
        }

        $esquema = $this->config->get('DB_SCHEMA');
        $sql = " INSERT INTO {$esquema}.DIARIO (ASIENTO, CONSECUTIVO, NIT, CENTRO_COSTO, CUENTA_CONTABLE, FUENTE, REFERENCIA,
               DEBITO_LOCAL, DEBITO_DOLAR, CREDITO_LOCAL, CREDITO_DOLAR, DEBITO_UNIDADES, CREDITO_UNIDADES,
               TIPO_CAMBIO, BASE_LOCAL, BASE_DOLAR) VALUES(:ASIENTO, :CONSECUTIVO, :NIT, :CENTRO_COSTO, :CUENTA_CONTABLE, :FUENTE, :REFERENCIA,
               :DEBITO_LOCAL, :DEBITO_DOLAR, :CREDITO_LOCAL, :CREDITO_DOLAR, :DEBITO_UNIDADES, :CREDITO_UNIDADES,
               :TIPO_CAMBIO, :BASE_LOCAL, :BASE_DOLAR);";

        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();
        try {
            foreach ($lineas as $linea) {
                $stmt = $pdo->prepare($sql);
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
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }
}
