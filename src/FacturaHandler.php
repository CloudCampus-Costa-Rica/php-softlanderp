<?php

namespace SoftlandERP;

use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Diario;

class FacturaHandler
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DB
     */
    private $db;

    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->db = DB::getInstance($config);
    }


    /**
     * @param DocumentoCC $factura
     */
    public function insertarCC($factura)
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
        $stmt->bindParam(':DOCUMENTO', $factura->documento, \PDO::PARAM_STR);
        $stmt->bindParam(':TIPO', $factura->tipo, \PDO::PARAM_STR);
        $stmt->bindParam(':SUBTIPO', $factura->subtipo, \PDO::PARAM_INT);
        $stmt->bindParam(':MONEDA', $factura->moneda, \PDO::PARAM_STR);
        $stmt->bindParam(':FECHA', $factura->fecha, \PDO::PARAM_STR);
        $stmt->bindParam(':SUBTOTAL', $factura->subtotal);
        $stmt->bindParam(':IMPUESTO', $factura->impuesto);
        $stmt->bindParam(':MONTO', $factura->monto);
        $stmt->bindParam(':SALDO', $factura->saldo);
        $stmt->bindParam(':TIPO_CAMBIO_DOLAR', $factura->tipoCambioDolar);
        $stmt->bindParam(':APLICACION', $factura->aplicacion, \PDO::PARAM_STR);
        $stmt->bindParam(':CLIENTE', $factura->cliente, \PDO::PARAM_STR);
        $stmt->bindParam(':REFERENCIA', $factura->referencia, \PDO::PARAM_STR);
        $stmt->bindParam(':ESQUEMA', $esquema, \PDO::PARAM_STR);
        $stmt->bindParam(':USUARIO', $usuario, \PDO::PARAM_STR);

        // Execute the statement
        try {
            $stmt->execute();
            // Commit the transaction
            $pdo->commit();
            echo "Stored procedure executed successfully.\n";
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
            echo "Stored procedure executed successfully.\n";
            return $resultado['VALOR_CONSECUTIVO'];
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }

    public function insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento)
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
        $stmt->bindParam(':FECHA', $factura->fecha, \PDO::PARAM_STR);
        $stmt->bindValue(':CONTABILIDAD', "F", \PDO::PARAM_STR);
        $stmt->bindValue(':ORIGEN', "CC", \PDO::PARAM_STR);
        $stmt->bindValue(':CLASE_ASIENTO', "N", \PDO::PARAM_STR);
        $stmt->bindValue(':MARCADO', "N", \PDO::PARAM_STR);
        $stmt->bindValue(':NOTAS', "", \PDO::PARAM_STR);
        $stmt->bindValue(':ULTIMO_USUARIO', $usuario, \PDO::PARAM_STR);
        $stmt->bindValue(':USUARIO_CREACION', $usuario, \PDO::PARAM_STR);

        if ($factura->moneda == "CRC")
        {
            $stmt->bindParam(':TOTAL_CONTROL_LOC', $factura->monto);
            $stmt->bindValue(':TOTAL_CONTROL_DOL', round($factura->monto / $factura->tipoCambioDolar, 2));            
            $stmt->bindParam(':TOTAL_DEBITO_LOC', $factura->monto);
            $stmt->bindValue(':TOTAL_DEBITO_DOL', round($factura->monto / $factura->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CREDITO_LOC', $factura->monto);
            $stmt->bindValue(':TOTAL_CREDITO_DOL', round($factura->monto / $factura->tipoCambioDolar, 2));
        }
        else
        {
            $stmt->bindValue(':TOTAL_CONTROL_LOC', round($factura->monto * $factura->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CONTROL_DOL', $factura->monto);
            $stmt->bindValue(':TOTAL_DEBITO_LOC', round($factura->monto * $factura->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_DEBITO_DOL', $factura->monto);
            $stmt->bindValue(':TOTAL_CREDITO_LOC', round($factura->monto * $factura->tipoCambioDolar, 2));
            $stmt->bindParam(':TOTAL_CREDITO_DOL', $factura->monto); 
        }
        
        try {
            $stmt->execute();
            $pdo->commit();
            echo "Stored procedure executed successfully.\n";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }

    /**
     * @param DocumentoCC $factura
     * @param Cliente $cliente
     * @param Impuesto|null $impuesto
     * @param string $asiento
     */
    public function insertarDiario($factura, $cliente, $impuesto, $asiento)
    {
        echo "insertarDiario\n";
        $ln = ["p", "d"];
        $lineas = [];
        if ($factura->impuesto > 0) {
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
        $linea->fuente = $factura->documento;
        $linea->referencia = $factura->documento;

        if ($factura->moneda == "CRC") {
            $linea->debitoLocal = $factura->monto;
            $linea->debitoDolar = round($factura->monto / $factura->tipoCambioDolar, 2);
        } else {
            $linea->debitoLocal = round($factura->monto * $factura->tipoCambioDolar, 2);
            $linea->debitoDolar = $factura->monto;
        }
        $linea->creditoLocal = null;
        $linea->creditoDolar = null;
        $linea->baseLocal = null;
        $linea->baseDolar = null;
        $linea->debitoUnidades = null;
        $linea->creditoUnidades = null;
        $linea->tipoCambio = $factura->tipoCambioDolar;
        $lineas[] = $linea;


        for ($i = 0; $i < count($ln); $i++) {
            $linea = new Diario();
            $linea->asiento = $asiento;
            $linea->consecutivo = $global++;
            $linea->nit = $cliente->nit;
            // se obtiene del subtipo
            if ($ln[$i] == "p") {
                $linea->centroCosto = $factura->centroCosto;
                $linea->cuentaContable = $factura->cuentaContable;
            }
            if ($ln[$i] == "i" && $impuesto != null) {
                $linea->centroCosto = $impuesto->centroCosto;
                $linea->cuentaContable = $impuesto->cuentaContable;
            }

            $linea->fuente = $factura->documento;
            $linea->referencia = $factura->documento;

            if ($ln[$i] == "p") // credito producto
            {
                if ($factura->moneda == "CRC") {
                    $linea->creditoLocal = $factura->subtotal;
                    $linea->creditoDolar = round($factura->subtotal / $factura->tipoCambioDolar, 2);
                } else {
                    $linea->creditoLocal = round($factura->subtotal * $factura->tipoCambioDolar, 2);
                    $linea->creditoDolar = $factura->subtotal;
                }
                $linea->debitoLocal = null;
                $linea->debitoDolar = null;
                $linea->baseLocal = null;
                $linea->baseDolar = null;
            }

            if ($ln[$i] == "d") // debito descuento
            {
                if ($factura->descuento == 0) {
                    continue;
                }

                if ($factura->moneda == "CRC") {
                    $linea->debitoLocal = $factura->descuento;
                    $linea->debitoDolar = round($factura->descuento / $factura->tipoCambioDolar, 2);
                } else {
                    $linea->debitoLocal = round($factura->descuento * $factura->tipoCambioDolar, 2);
                    $linea->debitoDolar = $factura->descuento;
                }

                $linea->creditoLocal = null;
                $linea->creditoDolar = null;
                $linea->baseLocal = null;
                $linea->baseDolar = null;
            }

            if ($ln[$i] == "i") // CREDITO IVA
            {

                if ($factura->impuesto == 0) {
                    continue;
                }

                echo "impuesto: " . $factura->impuesto . "\n";

                $linea->debitoLocal = null;
                $linea->debitoDolar = null;

                if ($factura->moneda == "CRC") {
                    $linea->creditoLocal = $factura->impuesto;
                    $linea->creditoDolar = round($factura->impuesto / $factura->tipoCambioDolar, 2);

                    $linea->baseLocal = $factura->subtotal;
                    $linea->baseDolar = round($factura->subtotal / $factura->tipoCambioDolar, 2);
                } else {
                    $linea->creditoLocal = round($factura->impuesto * $factura->tipoCambioDolar, 2);
                    $linea->creditoDolar = $factura->impuesto;

                    $linea->baseLocal = round($factura->subtotal * $factura->tipoCambioDolar, 2);
                    $linea->baseDolar = $factura->subtotal;
                }
            }
            $linea->debitoUnidades = null;
            $linea->creditoUnidades = null;
            $linea->tipoCambio = $factura->tipoCambioDolar;
            $lineas[] = $linea;
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
            echo "Stored procedure executed successfully.\n";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }
}
