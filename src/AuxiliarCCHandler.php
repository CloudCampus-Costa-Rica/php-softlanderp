<?php

namespace SoftlandERP;

class AuxiliarCCHandler
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
     * @param AuxiliarCC $auxiliarCC
     */
    public function insertar($auxiliarCC)
    {
        // Prepare the SQL statement with placeholders for input parameters
        $sql = "EXEC dbo.SP_CREAR_AUXILIAR_CC 
                    @TIPOCREDITO              = :TIPOCREDITO,
                    @TIPODEBITO               = :TIPODEBITO,
                    @DOCCREDITO              = :DOCCREDITO,
                    @DOCDEBITO               = :DOCDEBITO,
                    @MONTO                   = :MONTO,
                    @TIPO_CAMBIO_DOLAR       = :TIPO_CAMBIO_DOLAR,
                    @CLIENTE                 = :CLIENTE,
                    @FECHA                   = :FECHA,
                    @ESQUEMA                 = :ESQUEMA,
                    @USUARIO                = :USUARIO";

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
        $stmt->bindParam(':TIPOCREDITO', $auxiliarCC->tipoCredito, \PDO::PARAM_STR);
        $stmt->bindParam(':TIPODEBITO', $auxiliarCC->tipoDebito, \PDO::PARAM_STR);
        $stmt->bindParam(':DOCCREDITO', $auxiliarCC->docCredito, \PDO::PARAM_STR);
        $stmt->bindParam(':DOCDEBITO', $auxiliarCC->docDebito, \PDO::PARAM_STR);
        $stmt->bindParam(':MONTO', $auxiliarCC->monto);
        $stmt->bindParam(':TIPO_CAMBIO_DOLAR', $auxiliarCC->tipoCambioDolar);
        $stmt->bindParam(':CLIENTE', $auxiliarCC->cliente, \PDO::PARAM_STR);
        $stmt->bindParam(':FECHA', $auxiliarCC->fecha, \PDO::PARAM_STR);
        $stmt->bindParam(':ESQUEMA', $esquema, \PDO::PARAM_STR);
        $stmt->bindParam(':USUARIO', $usuario, \PDO::PARAM_STR);

        // Execute the statement
        try {
            $stmt->execute();
            // Commit the transaction
            //$resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            //print_r($resultado);
            $pdo->commit();

            echo "Stored procedure executed successfully.\n";
        } catch (\PDOException $e) {
            // Rollback the transaction if an error occurs
            $pdo->rollBack();
            die("Error executing stored procedure: " . $e->getMessage());
        }
    }
}