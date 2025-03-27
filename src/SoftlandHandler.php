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
        $esquema = $this->config->get('DB_SCHEMA');
        $usuario = $this->config->get('DB_USERNAME');
        
        // Prepare the SQL statement with named parameters
        $sql = "INSERT INTO [{$esquema}].DOCUMENTOS_CC (
                    DOCUMENTO,
                    TIPO,
                    APLICACION,
                    FECHA_DOCUMENTO,
                    FECHA,
                    MONTO,
                    SALDO,
                    MONTO_LOCAL,
                    SALDO_LOCAL,
                    MONTO_DOLAR,
                    SALDO_DOLAR,
                    MONTO_CLIENTE,
                    SALDO_CLIENTE,
                    TIPO_CAMBIO_MONEDA,
                    TIPO_CAMBIO_DOLAR,
                    TIPO_CAMBIO_CLIENT,
                    TIPO_CAMB_ACT_LOC,
                    TIPO_CAMB_ACT_DOL,
                    TIPO_CAMB_ACT_CLI,
                    SUBTOTAL,
                    DESCUENTO,
                    IMPUESTO1,
                    IMPUESTO2,
                    RUBRO1,
                    RUBRO2,
                    MONTO_RETENCION,
                    SALDO_RETENCION,
                    BASE_IMPUESTO1,
                    DEPENDIENTE,
                    FECHA_ULT_CREDITO,
                    CARGADO_DE_FACT,
                    APROBADO,
                    ASIENTO_PENDIENTE,
                    FECHA_ULT_MOD,
                    NOTAS,
                    CLASE_DOCUMENTO,
                    FECHA_VENCE,
                    NUM_PARCIALIDADES,
                    USUARIO_ULT_MOD,
                    CONDICION_PAGO,
                    MONEDA,
                    CLIENTE_REPORTE,
                    CLIENTE_ORIGEN,
                    CLIENTE,
                    SUBTIPO,
                    PORC_INTCTE,
                    NUM_DOC_CB,
                    USUARIO_APROBACION,
                    FECHA_APROBACION,
                    ANULADO,
                    CODIGO_IMPUESTO,
                    ACTIVIDAD_COMERCIAL
                ) VALUES (
                    :documento,
                    :tipo,
                    :aplicacion,
                    :fecha,
                    :fecha2,
                    :monto,
                    :saldo,
                    :monto_local,
                    :saldo_local,
                    :monto_dolar,
                    :saldo_dolar,
                    :monto_cliente,
                    :saldo_cliente,
                    :tipo_cambio_moneda,
                    :tipo_cambio_dolar,
                    :tipo_cambio_cliente,
                    :tipo_cambio_act_loc,
                    :tipo_cambio_act_dol,
                    :tipo_cambio_act_cli,
                    :subtotal,
                    0,
                    :impuesto1,
                    0,
                    0,
                    0,
                    0,
                    0,
                    :impuesto2,
                    'N',
                    '1980-01-01 00:00:00',
                    'N',
                    'S',
                    'N',
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)),
                    'Documento creado desde el sistema de Pagares para cancelar Facturas Modificadas',
                    'N',
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)),
                    0,
                    (CASE WHEN UPPER(:usuario1) like 'X%' then SUBSTRING(:usuario2, 2, LEN(:usuario3)) else :usuario4 end),
                    0,
                    :moneda,
                    :cliente1,
                    :cliente2,
                    :cliente3,
                    :subtipo,
                    0,
                    0,
                    (CASE WHEN UPPER(:usuario5) like 'X%' then SUBSTRING(:usuario6, 2, LEN(:usuario7)) else :usuario8 end),
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)),
                    'N',
                    '1',
                    '630401'
                )";

        // Prepare the statement
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            $stmt->bindParam(':documento', $documento->documento, \PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $documento->tipo, \PDO::PARAM_STR);
            $stmt->bindParam(':aplicacion', $documento->aplicacion, \PDO::PARAM_STR);
            $stmt->bindParam(':fecha', $documento->fecha, \PDO::PARAM_STR);
            $stmt->bindParam(':fecha2', $documento->fecha, \PDO::PARAM_STR);
            $stmt->bindParam(':monto', $documento->monto);
            $stmt->bindParam(':saldo', $documento->saldo);
            $stmt->bindParam(':monto_local', $documento->monto);
            $stmt->bindParam(':saldo_local', $documento->saldo);
            $stmt->bindValue(':monto_dolar', $documento->monto * $documento->tipoCambioDolar);
            $stmt->bindValue(':saldo_dolar', $documento->saldo * $documento->tipoCambioDolar);
            $stmt->bindParam(':monto_cliente', $documento->monto);
            $stmt->bindParam(':saldo_cliente', $documento->saldo);
            $stmt->bindParam(':tipo_cambio_moneda', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_cliente', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_act_loc', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_act_dol', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_act_cli', $documento->tipoCambioDolar);
            $stmt->bindParam(':subtotal', $documento->subtotal);
            $stmt->bindParam(':impuesto1', $documento->impuesto);
            $stmt->bindParam(':impuesto2', $documento->impuesto);
            $stmt->bindParam(':moneda', $documento->moneda, \PDO::PARAM_STR);
            $stmt->bindParam(':cliente1', $documento->cliente, \PDO::PARAM_STR);
            $stmt->bindParam(':cliente2', $documento->cliente, \PDO::PARAM_STR);
            $stmt->bindParam(':cliente3', $documento->cliente, \PDO::PARAM_STR);
            $stmt->bindParam(':subtipo', $documento->subtipo, \PDO::PARAM_INT);
            $stmt->bindParam(':usuario1', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario2', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario3', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario4', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario5', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario6', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario7', $usuario, \PDO::PARAM_STR);
            $stmt->bindParam(':usuario8', $usuario, \PDO::PARAM_STR);

            $stmt->execute();
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error executing insert statement: " . $e->getMessage());
        }
    }

    public function insertarDocumentoCCOld($documento)
    {
        $esquema = $this->config->get('DB_DATABASE');
        $usuario = $this->config->get('DB_USERNAME');
        
        $sql = "INSERT INTO [{$esquema}].DOCUMENTOS_CC (
                    DOCUMENTO,
                    TIPO,
                    APLICACION,
                    FECHA_DOCUMENTO,
                    FECHA,
                    MONTO,
                    SALDO,
                    MONTO_LOCAL,
                    SALDO_LOCAL,
                    MONTO_DOLAR,
                    SALDO_DOLAR,
                    MONTO_CLIENTE,
                    SALDO_CLIENTE,
                    TIPO_CAMBIO_MONEDA,
                    TIPO_CAMBIO_DOLAR,
                    TIPO_CAMBIO_CLIENT,
                    TIPO_CAMB_ACT_LOC,
                    TIPO_CAMB_ACT_DOL,
                    TIPO_CAMB_ACT_CLI,
                    SUBTOTAL,
                    DESCUENTO,
                    IMPUESTO1,
                    IMPUESTO2,
                    RUBRO1,
                    RUBRO2,
                    MONTO_RETENCION,
                    SALDO_RETENCION,
                    BASE_IMPUESTO1,
                    DEPENDIENTE,
                    FECHA_ULT_CREDITO,
                    CARGADO_DE_FACT,
                    APROBADO,
                    ASIENTO_PENDIENTE,
                    FECHA_ULT_MOD,
                    NOTAS,
                    CLASE_DOCUMENTO,
                    FECHA_VENCE,
                    NUM_PARCIALIDADES,
                    USUARIO_ULT_MOD,
                    CONDICION_PAGO,
                    MONEDA,
                    CLIENTE_REPORTE,
                    CLIENTE_ORIGEN,
                    CLIENTE,
                    SUBTIPO,
                    PORC_INTCTE,
                    NUM_DOC_CB,
                    USUARIO_APROBACION,
                    U_REFERENCIA_TOURPLAN,
                    FECHA_APROBACION,
                    ANULADO,
                    CODIGO_IMPUESTO,
                    ACTIVIDAD_COMERCIAL
                ) VALUES (
                    :documento,
                    :tipo,
                    :aplicacion,
                    :fecha,
                    :fecha2,
                    :monto,
                    :saldo,
                    (:monto * :tipo_cambio_dolar1),
                    (:saldo * :tipo_cambio_dolar2),
                    :monto,
                    :saldo,
                    :monto,
                    :saldo,
                    :tipo_cambio_dolar3,
                    :tipo_cambio_dolar4,
                    :tipo_cambio_dolar5,
                    :tipo_cambio_dolar6,
                    :tipo_cambio_dolar7,
                    :tipo_cambio_dolar8,
                    :subtotal,
                    0,
                    :impuesto1,
                    0,
                    0,
                    0,
                    0,
                    0,
                    :impuesto2,
                    'N',
                    '1980-01-01 00:00:00',
                    'N',
                    'S',
                    'N',
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0),
                    'Documento creado desde el sistema de Pagares para cancelar Facturas Modificadas',
                    'N',
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0),
                    0,
                    (CASE WHEN UPPER(:usuario1) LIKE 'X%' THEN SUBSTRING(:usuario2, 2, LEN(:usuario3)) ELSE :usuario4 END),
                    0,
                    :moneda,
                    :cliente1,
                    :cliente2,
                    :cliente3,
                    :subtipo,
                    0,
                    0,
                    (CASE WHEN UPPER(:usuario5) LIKE 'X%' THEN SUBSTRING(:usuario6, 2, LEN(:usuario7)) ELSE :usuario8 END),
                    :referencia,
                    CONVERT(VARCHAR, DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0),
                    'N',
                    '1',
                    '630401'
                )";

        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            $stmt->bindParam(':documento', $documento->documento);
            $stmt->bindParam(':tipo', $documento->tipo);
            $stmt->bindParam(':aplicacion', $documento->aplicacion);
            $stmt->bindParam(':fecha', $documento->fecha);
            $stmt->bindParam(':fecha2', $documento->fecha);
            $stmt->bindParam(':monto', $documento->monto);
            $stmt->bindParam(':saldo', $documento->saldo);
            $stmt->bindParam(':tipo_cambio_dolar1', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar2', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar3', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar4', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar5', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar6', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar7', $documento->tipoCambioDolar);
            $stmt->bindParam(':tipo_cambio_dolar8', $documento->tipoCambioDolar);
            $stmt->bindParam(':subtotal', $documento->subtotal);
            $stmt->bindParam(':impuesto1', $documento->impuesto);
            $stmt->bindParam(':impuesto2', $documento->impuesto);
            $stmt->bindParam(':moneda', $documento->moneda);
            $stmt->bindParam(':cliente1', $documento->cliente);
            $stmt->bindParam(':cliente2', $documento->cliente);
            $stmt->bindParam(':cliente3', $documento->cliente);
            $stmt->bindParam(':subtipo', $documento->subtipo);
            $stmt->bindParam(':usuario1', $usuario);
            $stmt->bindParam(':usuario2', $usuario);
            $stmt->bindParam(':usuario3', $usuario);
            $stmt->bindParam(':usuario4', $usuario);
            $stmt->bindParam(':usuario5', $usuario);
            $stmt->bindParam(':usuario6', $usuario);
            $stmt->bindParam(':usuario7', $usuario);
            $stmt->bindParam(':usuario8', $usuario);
            $stmt->bindParam(':referencia', $documento->referencia);

            $stmt->execute();
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error executing insert statement: " . $e->getMessage());
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
}