<?php

namespace SoftlandERP;

use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Impuesto;
use SoftlandERP\Models\AuxiliarCC;

class SoftlandConnector
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
     * @param Cliente $cliente
     */
    public function crear_cliente($cliente)
    {
        $clienteHandler = new ClienteHandler($this->config);
        $clienteHandler->insertar($cliente);
    }

    /**
     * @param DocumentoCC $factura
     * @param array $impuestos array de impuestos con los siguientes atributos:
     *  - centroCosto
     *  - cuentaContable
     *  - porcentaje
     *  - codigo
     *  - nombre
     */
    public function registrar_factura($factura, $impuestos)
    {
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        try {
            $facturaHandler = new FacturaHandler($this->config);
            $clienteHandler = new ClienteHandler($this->config);
            
            if($factura->cliente == null)
            {
                $cliente = $clienteHandler->consultarCliente($factura->cliente);
            }

            if($cliente == null && $factura->nit != null)
            {
                $cliente = $clienteHandler->consultarClienteNit($factura->nit);
            }

            if($cliente == null)
            {
                throw new \Exception("Cliente no encontrado");
            }

            $factura->cliente = $cliente->codigo;
            $facturaHandler->insertarDocumentoCC($factura, $pdo);

            // crear asiento de notaCredito
            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $facturaHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $facturaHandler->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento, $pdo);
            $facturaHandler->insertarDiario($factura, $cliente, $impuestos, $asiento, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar factura: " . $e->getMessage());
        }
    }

    /**
     * @param DocumentoCC $recibo
     * @param boolean $aplicar indica si se aplica el recibo a la factura
     */
    public function registrar_recibo($recibo, $aplicar = false)
    {
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        try {
            $softlandHandler = new SoftlandHandler($this->config);
            $factura = $softlandHandler->consultarDocumentoCC($recibo->documentoAplicacion);

            if($factura == null)
            {
                throw new \Exception("Factura [{$recibo->documentoAplicacion}] no encontrada");
            }

            $clienteHandler = new ClienteHandler($this->config);
            $cliente = $clienteHandler->consultarCliente($factura->cliente);

            $reciboHandler = new ReciboHandler($this->config);
            $reciboHandler->insertarDocumentoCC($recibo, $pdo);

            if($aplicar){
                // crear auxiliar de recibo
                $auxiliarHandler = new AuxiliarCCHandler($this->config);
                $auxiliar = new AuxiliarCC();
                $auxiliar->tipoCredito = $recibo->tipo;
                $auxiliar->tipoDebito = $factura->tipo;
                $auxiliar->docCredito = $recibo->documento;
                $auxiliar->docDebito = $factura->documento;
                $auxiliar->monto = $recibo->monto;
                $auxiliar->tipoCambioDolar = $recibo->tipoCambioDolar;
                $auxiliarHandler->insertar($auxiliar, $pdo);
            }

            // crear asiento de recibo
            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $reciboHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $reciboHandler->insertarAsientoDeDiario($recibo, $asiento, $paquete, $tipoAsiento, $pdo);
            $reciboHandler->insertarDiario($recibo, $cliente, null, $asiento, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar recibo: " . $e->getMessage());
        }
    }

    /**
     * @param DocumentoCC $notaCredito
     * @param array $impuestos array de impuestos con los siguientes atributos:
     *  - centroCosto
     *  - cuentaContable
     *  - porcentaje
     *  - codigo
     *  - nombre
     * @param boolean $aplicar indica si se aplica la notaCredito a la factura
     */
    public function registrar_notaCredito($notaCredito, $impuestos, $aplicar = false)
    {
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();

        try {
            $notaCreditoHandler = new NotaCreditoHandler($this->config);
            $factura = $notaCreditoHandler->consultarDocumentoCC($notaCredito->documentoAplicacion);

            if($factura == null)
            {
                throw new \Exception("Factura [{$notaCredito->documentoAplicacion}] no encontrada");
            }

            $clienteHandler = new ClienteHandler($this->config);
            $cliente = $clienteHandler->consultarCliente($factura->cliente);

            $notaCreditoHandler = new NotaCreditoHandler($this->config);
            $notaCreditoHandler->insertarDocumentoCC($notaCredito, $pdo);

            if($aplicar){
                // crear auxiliar de notaCredito
                $auxiliarHandler = new AuxiliarCCHandler($this->config);
                $auxiliar = new AuxiliarCC();
                $auxiliar->tipoCredito = $notaCredito->tipo;
                $auxiliar->tipoDebito = $factura->tipo;
                $auxiliar->docCredito = $notaCredito->documento;
                $auxiliar->docDebito = $factura->documento;
                $auxiliar->monto = $notaCredito->monto;
                $auxiliar->tipoCambioDolar = $notaCredito->tipoCambioDolar;
                $auxiliarHandler->insertar($auxiliar, $pdo);
            }

            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $notaCreditoHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $notaCreditoHandler->insertarAsientoDeDiario($notaCredito, $asiento, $paquete, $tipoAsiento, $pdo);
            $notaCreditoHandler->insertarDiario($notaCredito, $cliente, $impuestos, $asiento, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar nota de crédito: " . $e->getMessage());
        }
    }
    
}