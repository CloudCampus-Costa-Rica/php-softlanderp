<?php

namespace SoftlandERP;

use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Impuesto;
use SoftlandERP\Models\Diario;


class ReciboHandler extends SoftlandHandler
{
    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * @param DocumentoCC $documento
     * @param Cliente $cliente
     * @param Impuesto|null $impuesto
     * @param string $asiento
     * @param PDO|null $pdo
     * @return void
     */
    public function insertarDiario($documento, $cliente, $impuesto, $asiento, $pdo = null)
    {
        $ln = ["p"];
        $lineas = [];
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
            $linea->consecutivo = $global++;
            $linea->nit = $cliente->nit;
            // se obtiene del subtipo
            if ($ln[$i] == "p") {
                $linea->centroCosto = $documento->centroCosto;
                $linea->cuentaContable = $documento->cuentaContable;
            }

            $linea->fuente = $documento->documento;
            $linea->referencia = $documento->documento;

            if ($ln[$i] == "p") // debito pago
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

            $linea->debitoUnidades = null;
            $linea->creditoUnidades = null;
            $linea->tipoCambio = $documento->tipoCambioDolar;
            $lineas[] = $linea;
        }

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
}