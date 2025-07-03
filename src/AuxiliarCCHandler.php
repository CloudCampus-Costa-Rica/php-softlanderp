<?php

namespace SoftlandERP;

class AuxiliarCCHandler
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MSSQLDB
     */
    private $db;

    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->db = MSSQLDB::getInstance($config);
    }

    /**
     * @param AuxiliarCC $auxiliarCC
     * @param PDO|null $pdo
     * @return void
     */
    public function insertar($auxiliarCC, $pdo = null)
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

        // Use provided PDO connection or get a new one
        $usePdo = $pdo ?: $this->db->getConnection();
        
        // Remove transaction handling if PDO was provided
        $newTransaction = !$pdo;
        if ($newTransaction) {
            $usePdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
            $usePdo->beginTransaction();
        }

        $stmt = $usePdo->prepare($sql);

        // Bind the input parameters to the placeholders
        $usuario = $this->config->get('DB_USERNAME');
        $esquema = $this->config->get('DB_SCHEMA');
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

        try {
            $stmt->execute();
            
            if ($newTransaction) {
                $usePdo->commit();
            }
        } catch (\PDOException $e) {
            if ($newTransaction) {
                $usePdo->rollBack();
            }
            throw new \RuntimeException("Error executing stored procedure: " . $e->getMessage());
        }
    }
}