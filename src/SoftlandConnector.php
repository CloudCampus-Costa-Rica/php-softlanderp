<?php

namespace SoftlandERP;

use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\DocumentoCC;
class SoftlandConnector
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
     * @param Cliente $cliente
     */
    public function crear_cliente($cliente)
    {
        $clienteHandler = new ClienteHandler($this->config);
        $clienteHandler->insertar($cliente);
    }

    /**
     * @param DocumentoCC $factura
     */
    public function crear_factura($factura)
    {
        $facturaHandler = new FacturaHandler($this->config);
        $facturaHandler->insertarCC($factura);
    }
}