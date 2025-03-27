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

        $facturaHandler->insertarDocumentoCC($factura);

        // crear asiento de notaCredito
        $paquete = "CC";
        $tipoAsiento = "CC";
        $asiento =  $facturaHandler->obtenerConsecutivoPaquete("CC");
        $facturaHandler->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento);
        $facturaHandler->insertarDiario($factura, $cliente, $impuestos, $asiento);
        
    }

    /**
     * @param DocumentoCC $recibo
     * @param boolean $aplicar indica si se aplica el recibo a la factura
     */
    public function registrar_recibo($recibo, $aplicar= false)
    {

        $softlandHandler = new SoftlandHandler($this->config);
        $factura = $softlandHandler->consultarDocumentoCC($recibo->documentoAplicacion);

        if($factura == null)
        {
            throw new \Exception("Factura [{$recibo->documentoAplicacion}] no encontrada");
        }

        $clienteHandler = new ClienteHandler($this->config);
        $cliente = $clienteHandler->consultarCliente($factura->cliente);

        $reciboHandler = new ReciboHandler($this->config);
        $reciboHandler->insertarDocumentoCC($recibo);

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
            $auxiliarHandler->insertar($auxiliar);
        }

        // crear asiento de recibo
        $paquete = "CC";
        $tipoAsiento = "CC";
        $asiento =  $reciboHandler->obtenerConsecutivoPaquete("CC");
        $reciboHandler->insertarAsientoDeDiario($recibo, $asiento, $paquete, $tipoAsiento);
        // validar si hay que extraer las deducciones del recibo de acuerdo al tipo de pago
        $reciboHandler->insertarDiario($recibo, $cliente, null, $asiento);
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
    public function registrar_notaCredito($notaCredito, $impuestos, $aplicar= false)
    {
        $softlandHandler = new SoftlandHandler($this->config);
        $factura = $softlandHandler->consultarDocumentoCC($notaCredito->documentoAplicacion);

        if($factura == null)
        {
            throw new \Exception("Factura [{$notaCredito->documentoAplicacion}] no encontrada");
        }

        $clienteHandler = new ClienteHandler($this->config);
        $cliente = $clienteHandler->consultarCliente($factura->cliente);

        $notaCreditoHandler = new NotaCreditoHandler($this->config);
        $notaCreditoHandler->insertarDocumentoCC($notaCredito);

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
            $auxiliarHandler->insertar($auxiliar);
        }

        // crear asiento de notaCredito
        $paquete = "CC";
        $tipoAsiento = "CC";
        $asiento =  $notaCreditoHandler->obtenerConsecutivoPaquete("CC");
        $notaCreditoHandler->insertarAsientoDeDiario($notaCredito, $asiento, $paquete, $tipoAsiento);
        $notaCreditoHandler->insertarDiario($notaCredito, $cliente, $impuestos, $asiento);
    }
    
}