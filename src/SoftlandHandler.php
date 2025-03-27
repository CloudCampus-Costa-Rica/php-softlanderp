<?php

namespace SoftlandERP;

abstract class SoftlandHandler
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var DB
     */
    protected $db;

    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->db = DB::getInstance($config);
    }

    /**
     * @param DocumentoCC $documento
     * @return void
     */
    public function insertarDocumentoCC($documento)
    {
        // Prepare the SQL statement with placeholders for input parameters
        $sql = "EXEC dbo.SP_Crea_DOC_CC 
                @DOCUMENTO            = :DOCUMENTO,
                @TIPO                 = :TIPO,
                @SUBTIPO              = :SUBTIPO,
                @MONEDA               = :MONEDA,
                @FECHA                = :FECHA,
                @SUBTOTAL             = :SUBTOTAL,
                @IMPUESTO             = :IMPUESTO,
                @MONTO                = :MONTO,
                @SALDO                = :SALDO,
                @TIPO_CAMBIO_DOLAR    = :TIPO_CAMBIO_DOLAR,
                @APLICACION           = :APLICACION,
                @CLIENTE              = :CLIENTE,   
                @REFERENCIA			 = :REFERENCIA,
                @ESQUEMA              = :ESQUEMA,
                @USUARIO				 = :USUARIO";

        // Prepare the statement
        $pdo = $this->db->getConnection();

        // Set the transaction isolation level
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");

        // Begin a transaction
        $pdo->beginTransaction();

        $stmt = $pdo->prepare($sql);

        // Bind the input parameters to the placeholders
        $usuario = $this->config->get('DB_USERNAME');
        $esquema = $this->config->get('DB_DATABASE');
        $stmt->bindParam(':DOCUMENTO', $documento->documento, \PDO::PARAM_STR);
        $stmt->bindParam(':TIPO', $documento->tipo, \PDO::PARAM_STR);
        $stmt->bindParam(':SUBTIPO', $documento->subtipo, \PDO::PARAM_INT);
        $stmt->bindParam(':MONEDA', $documento->moneda, \PDO::PARAM_STR);
        $stmt->bindParam(':FECHA', $documento->fecha, \PDO::PARAM_STR);
        $stmt->bindParam(':SUBTOTAL', $documento->subtotal);
        $stmt->bindParam(':IMPUESTO', $documento->impuesto);
        $stmt->bindParam(':MONTO', $documento->monto);
        $stmt->bindParam(':SALDO', $documento->saldo);
        $stmt->bindParam(':TIPO_CAMBIO_DOLAR', $documento->tipoCambioDolar);
        $stmt->bindParam(':APLICACION', $documento->aplicacion, \PDO::PARAM_STR);
        $stmt->bindParam(':CLIENTE', $documento->cliente, \PDO::PARAM_STR);
        $stmt->bindParam(':REFERENCIA', $documento->referencia, \PDO::PARAM_STR);
        $stmt->bindParam(':ESQUEMA', $esquema, \PDO::PARAM_STR);
        $stmt->bindParam(':USUARIO', $usuario, \PDO::PARAM_STR);

        // Execute the statement
        try {
            $stmt->execute();
            // Commit the transaction
            $pdo->commit();
        } catch (\PDOException $e) {
            // Rollback the transaction if an error occurs
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }

    /**
     * @param string $paquete
     * @return string|null
     */
    public function obtenerConsecutivoPaquete($paquete)
    {
        // obtener consecutivo paquete
        // invocar stored procedude [FUNDEPOS].[GenerarSiguienteConsecutivoPaquete], el resultado se obtiene de un select que se ejcuta dentro del procedure
        $usuario = $this->config->get('DB_USERNAME');
        $esquema = $this->config->get('DB_SCHEMA');
        $sql = "EXEC [{$esquema}].[GenerarSiguienteConsecutivoPaquete] @PAQUETE = :PAQUETE";
        // Prepare the statement
        $pdo = $this->db->getConnection();

        // Set the transaction isolation level
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");

        // Begin a transaction
        $pdo->beginTransaction();
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':PAQUETE', $paquete, \PDO::PARAM_STR);

        // Execute the statement
        try {
            $stmt->execute();
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $pdo->commit();
            return $resultado['VALOR_CONSECUTIVO'];
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }


    /**
     * @param string $numeroDocumento
     * @return DocumentoCC|null
     */
    public function consultarDocumentoCC($numeroDocumento)
    {
        /** @var DocumentoCC $documento */
        $documento = null;
        /*$esquema = $this->config->get('DB_SCHEMA');
        $sql = "SELECT DOCUMENTO, TIPO, SUBTIPO, MONEDA, MONTO, SALDO, SUBTOTAL, IMPUESTO1, DESCUENTO, TIPO_CAMBIO_DOLAR, FECHA, APLICACION, CLIENTE, REFERENCIA FROM {$esquema}.DOCUMENTO_CC WHERE DOCUMENTO = :DOCUMENTO";
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':DOCUMENTO', $documento, \PDO::PARAM_STR);
        $stmt->execute();
        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
        if($resultado)
        {
            $documento = new DocumentoCC();
            $documento->documento = $resultado['DOCUMENTO'];
            $documento->tipo = $resultado['TIPO'];
            $documento->subtipo = $resultado['SUBTIPO'];
            $documento->moneda = $resultado['MONEDA'];
            $documento->monto = $resultado['MONTO'];
            $documento->saldo = $resultado['SALDO'];
            $documento->subtotal = $resultado['SUBTOTAL'];
            $documento->impuesto = $resultado['IMPUESTO1'];
            $documento->descuento = $resultado['DESCUENTO'];
            $documento->tipoCambioDolar = $resultado['TIPO_CAMBIO_DOLAR'];
            $documento->fecha = $resultado['FECHA'];
            $documento->aplicacion = $resultado['APLICACION'];
            $documento->cliente = $resultado['CLIENTE'];
            $documento->referencia = $resultado['REFERENCIA'];
        }*/
        $record =
            $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "DOCUMENTOS_CC"))
            ->select("DOCUMENTO, TIPO, SUBTIPO, MONEDA, MONTO, SALDO, SUBTOTAL, IMPUESTO1, DESCUENTO, TIPO_CAMBIO_DOLAR, FECHA, APLICACION, CLIENTE, REFERENCIA")
            ->where("DOCUMENTO", $numeroDocumento)
            ->get()->first();

        if($record)
        {
            $documento = new DocumentoCC();
            $documento->documento = $record->{"DOCUMENTO"};
            $documento->tipo = $record->{"TIPO"};
            $documento->subtipo = $record->{"SUBTIPO"};
            $documento->moneda = $record->{"MONEDA"};
            $documento->monto = $record->{"MONTO"};
            $documento->saldo = $record->{"SALDO"};
            $documento->subtotal = $record->{"SUBTOTAL"};
            $documento->impuesto = $record->{"IMPUESTO1"};
            $documento->descuento = $record->{"DESCUENTO"};
            $documento->tipoCambioDolar = $record->{"TIPO_CAMBIO_DOLAR"};
            $documento->fecha = $record->{"FECHA"};
            $documento->aplicacion = $record->{"APLICACION"};
            $documento->cliente = $record->{"CLIENTE"};
            $documento->referencia = $record->{"REFERENCIA"};
        }
        return $documento;
    }
    

    /**
     * @param DocumentoCC $documento
     * @param string $asiento
     * @param string $paquete
     * @param string $tipoAsiento
     * @return void
     */
    public function insertarAsientoDeDiario($documento, $asiento, $paquete, $tipoAsiento)
    {
        echo "insertarAsientoDeDiario\n";
        $esquema = $this->config->get('DB_SCHEMA');
        $usuario = $this->config->get('DB_USERNAME');
        $sql = " INSERT INTO {$esquema}.ASIENTO_DE_DIARIO (ASIENTO, PAQUETE, TIPO_ASIENTO, FECHA, CONTABILIDAD, ORIGEN, CLASE_ASIENTO, 
        TOTAL_DEBITO_LOC, TOTAL_DEBITO_DOL, TOTAL_CREDITO_LOC, TOTAL_CREDITO_DOL, ULTIMO_USUARIO, 
        FECHA_ULT_MODIF, MARCADO, NOTAS, TOTAL_CONTROL_LOC, TOTAL_CONTROL_DOL, USUARIO_CREACION, 
        FECHA_CREACION) VALUES( :ASIENTO, :PAQUETE, :TIPO_ASIENTO, :FECHA, :CONTABILIDAD, :ORIGEN, :CLASE_ASIENTO, 
        :TOTAL_DEBITO_LOC, :TOTAL_DEBITO_DOL, :TOTAL_CREDITO_LOC, :TOTAL_CREDITO_DOL, :ULTIMO_USUARIO, 
        GETDATE(), :MARCADO, :NOTAS, :TOTAL_CONTROL_LOC, :TOTAL_CONTROL_DOL, :USUARIO_CREACION, 
        GETDATE() );";

        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':ASIENTO', $asiento, \PDO::PARAM_STR);
        $stmt->bindParam(':PAQUETE', $paquete, \PDO::PARAM_STR);
        $stmt->bindParam(':TIPO_ASIENTO', $tipoAsiento, \PDO::PARAM_STR);
        $stmt->bindParam(':FECHA', $documento->fecha, \PDO::PARAM_STR);
        $stmt->bindValue(':CONTABILIDAD', "F", \PDO::PARAM_STR);
        $stmt->bindValue(':ORIGEN', "CC", \PDO::PARAM_STR);
        $stmt->bindValue(':CLASE_ASIENTO', "N", \PDO::PARAM_STR);
        $stmt->bindValue(':MARCADO', "N", \PDO::PARAM_STR);
        $stmt->bindValue(':NOTAS', "", \PDO::PARAM_STR);
        $stmt->bindValue(':ULTIMO_USUARIO', $usuario, \PDO::PARAM_STR);
        $stmt->bindValue(':USUARIO_CREACION', $usuario, \PDO::PARAM_STR);

        if ($documento->moneda == "CRC")
        {
            $stmt->bindParam(':TOTAL_CONTROL_LOC', $documento->monto);
            $stmt->bindValue(':TOTAL_CONTROL_DOL', round($documento->monto / $documento->tipoCambioDolar, 2));            
            $stmt->bindParam(':TOTAL_DEBITO_LOC', $documento->monto);
            $stmt->bindValue(':TOTAL_DEBITO_DOL', round($documento->monto / $documento->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CREDITO_LOC', $documento->monto);
            $stmt->bindValue(':TOTAL_CREDITO_DOL', round($documento->monto / $documento->tipoCambioDolar, 2));
        }
        else
        {
            $stmt->bindValue(':TOTAL_CONTROL_LOC', round($documento->monto * $documento->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CONTROL_DOL', $documento->monto);
            $stmt->bindValue(':TOTAL_DEBITO_LOC', round($documento->monto * $documento->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_DEBITO_DOL', $documento->monto);
            $stmt->bindValue(':TOTAL_CREDITO_LOC', round($documento->monto * $documento->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CREDITO_DOL', $documento->monto); 
        }
        
        try {
            $stmt->execute();
            $pdo->commit();            
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }

    /**
     * @param DocumentoCC $documento
     * @param Cliente $cliente
     * @param array $impuestos
     * @param string $asiento
     * @return void
     */
    public abstract function insertarDiario($documento, $cliente, $impuestos, $asiento);
    //{
    //    echo "insertarDiario\n";
    //    $ln = ["p", "d"];
    //    $lineas = [];
    //    if ($documento->impuesto > 0) {
    //        $ln[] = "i";
    //    }
    //    $global = 1;
    //    // insertar primera linea debito
    //    $linea = new Diario();
    //    $linea->asiento = $asiento;
    //    $linea->consecutivo = $global++;
    //    $linea->nit = $cliente->nit;
    //    $linea->centroCosto = $cliente->centroCosto;
    //    $linea->cuentaContable = $cliente->cuentaContable;
    //    $linea->fuente = $documento->documento;
    //    $linea->referencia = $documento->documento;

    //    if ($documento->moneda == "CRC") {
    //        $linea->debitoLocal = $documento->monto;
    //        $linea->debitoDolar = round($documento->monto / $documento->tipoCambioDolar, 2);
    //    } else {
    //        $linea->debitoLocal = round($documento->monto * $documento->tipoCambioDolar, 2);
    //        $linea->debitoDolar = $documento->monto;
    //    }
    //    $linea->creditoLocal = null;
    //    $linea->creditoDolar = null;
    //    $linea->baseLocal = null;
    //    $linea->baseDolar = null;
    //    $linea->debitoUnidades = null;
    //    $linea->creditoUnidades = null;
    //    $linea->tipoCambio = $documento->tipoCambioDolar;
    //    $lineas[] = $linea;


    //    for ($i = 0; $i < count($ln); $i++) {
    //        $linea = new Diario();
    //        $linea->asiento = $asiento;
    //        $linea->consecutivo = $global++;
    //        $linea->nit = $cliente->nit;
    //        // se obtiene del subtipo
    //        if ($ln[$i] == "p") {
    //            $linea->centroCosto = $documento->centroCosto;
    //            $linea->cuentaContable = $documento->cuentaContable;
    //        }
    //        if ($ln[$i] == "i" && $impuesto != null) {
    //            $linea->centroCosto = $impuesto->centroCosto;
    //            $linea->cuentaContable = $impuesto->cuentaContable;
    //        }

    //        $linea->fuente = $documento->documento;
    //        $linea->referencia = $documento->documento;

    //        if ($ln[$i] == "p") // credito producto
    //        {
    //            if ($documento->moneda == "CRC") {
    //                $linea->creditoLocal = $documento->subtotal;
    //                $linea->creditoDolar = round($documento->subtotal / $documento->tipoCambioDolar, 2);
    //            } else {
    //                $linea->creditoLocal = round($documento->subtotal * $documento->tipoCambioDolar, 2);
    //                $linea->creditoDolar = $documento->subtotal;
    //            }
    //            $linea->debitoLocal = null;
    //            $linea->debitoDolar = null;
    //            $linea->baseLocal = null;
    //            $linea->baseDolar = null;
    //        }

    //        if ($ln[$i] == "d") // debito descuento
    //        {
    //            if ($documento->descuento == 0) {
    //                continue;
    //            }

    //            if ($documento->moneda == "CRC") {
    //                $linea->debitoLocal = $documento->descuento;
    //                $linea->debitoDolar = round($documento->descuento / $documento->tipoCambioDolar, 2);
    //            } else {
    //                $linea->debitoLocal = round($documento->descuento * $documento->tipoCambioDolar, 2);
    //                $linea->debitoDolar = $documento->descuento;
    //            }

    //            $linea->creditoLocal = null;
    //            $linea->creditoDolar = null;
    //            $linea->baseLocal = null;
    //            $linea->baseDolar = null;
    //        }

    //        if ($ln[$i] == "i") // CREDITO IVA
    //        {

    //            if ($documento->impuesto == 0) {
    //                continue;
    //            }

    //            echo "impuesto: " . $documento->impuesto . "\n";

    //            $linea->debitoLocal = null;
    //            $linea->debitoDolar = null;

    //            if ($documento->moneda == "CRC") {
    //                $linea->creditoLocal = $documento->impuesto;
    //                $linea->creditoDolar = round($documento->impuesto / $documento->tipoCambioDolar, 2);

    //            $linea->baseLocal = $documento->subtotal;
    //            $linea->baseDolar = round($documento->subtotal / $documento->tipoCambioDolar, 2);
    //        } else {
    //            $linea->creditoLocal = round($documento->impuesto * $documento->tipoCambioDolar, 2);
    //            $linea->creditoDolar = $documento->impuesto;

    //            $linea->baseLocal = round($documento->subtotal * $documento->tipoCambioDolar, 2);
    //            $linea->baseDolar = $documento->subtotal;
    //        }
    //        $linea->debitoUnidades = null;
    //        $linea->creditoUnidades = null;
    //        $linea->tipoCambio = $documento->tipoCambioDolar;
    //        $lineas[] = $linea;
    //    }

    //    $esquema = $this->config->get('DB_SCHEMA');
    //    $sql = " INSERT INTO {$esquema}.DIARIO (ASIENTO, CONSECUTIVO, NIT, CENTRO_COSTO, CUENTA_CONTABLE, FUENTE, REFERENCIA,
    //           DEBITO_LOCAL, DEBITO_DOLAR, CREDITO_LOCAL, CREDITO_DOLAR, DEBITO_UNIDADES, CREDITO_UNIDADES,
    //           TIPO_CAMBIO, BASE_LOCAL, BASE_DOLAR) VALUES(:ASIENTO, :CONSECUTIVO, :NIT, :CENTRO_COSTO, :CUENTA_CONTABLE, :FUENTE, :REFERENCIA,
    //           :DEBITO_LOCAL, :DEBITO_DOLAR, :CREDITO_LOCAL, :CREDITO_DOLAR, :DEBITO_UNIDADES, :CREDITO_UNIDADES,
    //           :TIPO_CAMBIO, :BASE_LOCAL, :BASE_DOLAR);";

    //    $pdo = $this->db->getConnection();
    //    $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
    //    $pdo->beginTransaction();
    //    try {
    //        foreach ($lineas as $linea) {
    //            $stmt = $pdo->prepare($sql);
    //            $stmt->bindParam(':ASIENTO', $linea->asiento, \PDO::PARAM_STR);
    //            $stmt->bindParam(':CONSECUTIVO', $linea->consecutivo);
    //            $stmt->bindParam(':NIT', $linea->nit, \PDO::PARAM_STR);
    //            $stmt->bindParam(':CENTRO_COSTO', $linea->centroCosto, \PDO::PARAM_STR);
    //            $stmt->bindParam(':CUENTA_CONTABLE', $linea->cuentaContable, \PDO::PARAM_STR);
    //            $stmt->bindParam(':FUENTE', $linea->fuente, \PDO::PARAM_STR);
    //            $stmt->bindParam(':REFERENCIA', $linea->referencia, \PDO::PARAM_STR);
    //            $stmt->bindParam(':DEBITO_LOCAL', $linea->debitoLocal);
    //            $stmt->bindParam(':DEBITO_DOLAR', $linea->debitoDolar);
    //            $stmt->bindParam(':CREDITO_LOCAL', $linea->creditoLocal);
    //            $stmt->bindParam(':CREDITO_DOLAR', $linea->creditoDolar);
    //            $stmt->bindParam(':DEBITO_UNIDADES', $linea->debitoUnidades);
    //            $stmt->bindParam(':CREDITO_UNIDADES', $linea->creditoUnidades);
    //            $stmt->bindParam(':TIPO_CAMBIO', $linea->tipoCambio);
    //            $stmt->bindParam(':BASE_LOCAL', $linea->baseLocal);
    //            $stmt->bindParam(':BASE_DOLAR', $linea->baseDolar);
    //            $stmt->execute();
    //        }
    //        $pdo->commit();
    //        echo "Stored procedure executed successfully.\n";
    //    } catch (\PDOException $e) {
    //        $pdo->rollBack();
    //        die("Error executing stored procedure: " . $e->getMessage());
    //    }
    //}
}