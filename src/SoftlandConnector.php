<?php

namespace SoftlandERP;

use SoftlandERP\Models\Cliente;
use SoftlandERP\Models\DocumentoCC;
use SoftlandERP\Models\Impuesto;
use SoftlandERP\Models\AuxiliarCC;
use SoftlandERP\Constantes;

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
        Constantes::loadEnv();
        $this->config = $config;
        $this->db = MSSQLDB::getInstance($config);
    }

    /**
     * @param Cliente $cliente
     */
    public function crear_cliente($cliente)
    {
        $clienteHandler = new ClienteHandler($this->config);
        $consecutivo = $clienteHandler->insertar($cliente);
        return $consecutivo;
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
        $step = "Inicio";
        try {
            $step = "Instanciar handlers";
            $facturaHandler = new FacturaHandler($this->config);
            $clienteHandler = new ClienteHandler($this->config);
            $cliente = null;
            
            if(isset($factura->cliente) && $factura->cliente != null)
            {
                $step = "Consultar cliente por codigo";
                $cliente = $clienteHandler->consultarCliente($factura->cliente);
            }

            if($cliente == null && $factura->nit != null)
            {
                $step = "Consultar cliente por nit";
                $cliente = $clienteHandler->consultarClienteNit($factura->nit);
            }

            if($cliente == null)
            {
                throw new \Exception("Cliente no encontrado");
            }

            $step = "Insertar documento cc";
            $factura->cliente = $cliente->codigo;
            $facturaHandler->insertarDocumentoCC($factura, $pdo);

            // crear asiento de notaCredito
            $step = "Obtener parametros paquete para asiento de diario";
            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $facturaHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $step = "Insertar asiento";
            $facturaHandler->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento, $pdo);
            $facturaHandler->insertarDiario($factura, $cliente, $impuestos, $asiento, $pdo);

            $step = "Asigar asiento a factura";
            //asigar asiento a factura
            $factura->asiento = $asiento;
            $facturaHandler->actualizarDocumentoCC($factura, $asiento, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar factura [$step]: " . $e->getMessage());
        }
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
    public function registrar_factura_reclasificacion($factura, $impuestos)
    {
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();
        $step = "Inicio";
        try {
            $step = "Instanciar handlers";
            $facturaHandler = new FacturaReclasifHandler($this->config);
            $clienteHandler = new ClienteHandler($this->config);
            $cliente = null;
            
            if(isset($factura->cliente) && $factura->cliente != null)
            {
                $step = "Consultar cliente por codigo";
                $cliente = $clienteHandler->consultarCliente($factura->cliente);
            }

            if($cliente == null && $factura->nit != null)
            {
                $step = "Consultar cliente por nit";
                $cliente = $clienteHandler->consultarClienteNit($factura->nit);
            }

            if($cliente == null)
            {
                throw new \Exception("Cliente no encontrado");
            }

            // crear asiento de factura reclasificacion
            $step = "Obtener parametros paquete para asiento de diario";
            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $facturaHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $step = "Insertar asiento";
            $facturaHandler->insertarAsientoDeDiario($factura, $asiento, $paquete, $tipoAsiento, $pdo);
            $facturaHandler->insertarDiario($factura, $cliente, $impuestos, $asiento, $pdo);

            $step = "Asigar asiento a factura";
            //asigar asiento a factura OJO no hay documento para asignar asiento xq el asiento es un ajuste nada mas
            //$factura->asiento = $asiento;
            //$facturaHandler->actualizarDocumentoCC($factura, $asiento, $pdo);
            
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar factura [$step]: " . $e->getMessage());
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
        $step = "Inicio";
        try {
            $step = "Instanciar handlers";
            $reciboHandler = new ReciboHandler($this->config);
            $clienteHandler = new ClienteHandler($this->config);
            $facturaHandler = new FacturaHandler($this->config);

            $cliente = null;
            $step = "Consultar documento aplicacion";            
            $factura = $facturaHandler->consultarDocumentoCC($recibo->documentoAplicacion);

            if($factura == null)
            {
                throw new \Exception("Factura [{$recibo->documentoAplicacion}] no encontrada");
            }

            if(isset($factura->cliente) && $factura->cliente != null)
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

            $recibo->cliente = $cliente->codigo;

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
                $auxiliar->cliente = $recibo->cliente;
                $auxiliar->fecha = $recibo->fecha;
                $auxiliarHandler->insertar($auxiliar, $pdo);
            }

            // crear asiento de recibo
            $paquete = "CC";
            $tipoAsiento = "CC";
            $asiento = $reciboHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $reciboHandler->insertarAsientoDeDiario($recibo, $asiento, $paquete, $tipoAsiento, $pdo);
            $reciboHandler->insertarDiario($recibo, $cliente, null, $asiento, $pdo);

            //asigar asiento a recibo
            $recibo->asiento = $asiento;
            $reciboHandler->actualizarDocumentoCC($recibo, $asiento, $pdo);

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
        $step = "Inicio";
        try {
            $notaCreditoHandler = new NotaCreditoHandler($this->config);
            $clienteHandler = new ClienteHandler($this->config);
            $facturaHandler = new FacturaHandler($this->config);            
            $cliente = null;
            $step = "Consultar documentao aplicacion";
            $factura = $facturaHandler->consultarDocumentoCC($notaCredito->documentoAplicacion);

            if($factura == null)
            {
                throw new \Exception("Factura [{$notaCredito->documentoAplicacion}] no encontrada");
            }

            if(isset($factura->cliente) && $factura->cliente != null)
            {
                $step = "Consultar cliente por codigo";
                $cliente = $clienteHandler->consultarCliente($factura->cliente);
            }

            if($cliente == null && $factura->nit != null)
            {
                $step = "Consultar cliente por nit";
                $cliente = $clienteHandler->consultarClienteNit($factura->nit);
            }

            if($cliente == null)
            {
                throw new \Exception("Cliente no encontrado");
            }

            $notaCredito->cliente = $cliente->codigo;

            $notaCreditoHandler = new NotaCreditoHandler($this->config);
            $step = "Insertar documento cc";
            $notaCreditoHandler->insertarDocumentoCC($notaCredito, $pdo);

            if($aplicar){
                // crear auxiliar de notaCredito
                $step = "Insertar auxiliar";
                $auxiliarHandler = new AuxiliarCCHandler($this->config);
                $auxiliar = new AuxiliarCC();
                $auxiliar->tipoCredito = $notaCredito->tipo;
                $auxiliar->tipoDebito = $factura->tipo;
                $auxiliar->docCredito = $notaCredito->documento;
                $auxiliar->docDebito = $factura->documento;
                $auxiliar->monto = $notaCredito->monto;
                $auxiliar->tipoCambioDolar = $notaCredito->tipoCambioDolar;
                $auxiliar->cliente = $notaCredito->cliente;
                $auxiliar->fecha = $notaCredito->fecha;
                $auxiliarHandler->insertar($auxiliar, $pdo);
            }

            $paquete = "CC";
            $tipoAsiento = "CC";
            $step = "Obtener parametros paquete para asiento de diario";
            $asiento = $notaCreditoHandler->obtenerConsecutivoPaquete("CC", $pdo);
            $step = "Insertar asiento";
            $notaCreditoHandler->insertarAsientoDeDiario($notaCredito, $asiento, $paquete, $tipoAsiento, $pdo);
            $step = "Insertar diario";
            $notaCreditoHandler->insertarDiario($notaCredito, $cliente, $impuestos, $asiento, $pdo);

            //asigar asiento a nota credito
            $notaCredito->asiento = $asiento;
            $step = "Actualizar documento cc";
            $notaCreditoHandler->actualizarDocumentoCC($notaCredito, $asiento, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar nota de crédito [{$step}]: " . $e->getMessage());
        }
    }

    /**
     * @param array $lineas Array de arrays con las líneas del diario, cada línea debe contener:
     *  - numeroAsiento
     *  - centroCosto
     *  - cuentaContable
     *  - fuente
     *  - referencia
     *  - monto
     *  - tipoMov: "debito" o "credito"
     *  - moneda
     *  - fecha (opcional)
     * @param float $tipoCambio Tipo de cambio
     * @param string $paquete Paquete para el asiento (ej: "CC")
     * @param string $tipoAsiento Tipo de asiento (ej: "CC")
     * @param string|null $notas Notas opcionales
     * @param string|null $nit NIT opcional
     * @param string|null $fecha Fecha opcional del asiento
     */
    public function registrar_asiento($lineas, $tipoCambio, $paquete, $tipoAsiento, $notas = null, $nit = null, $fecha = null)
    {
        $pdo = $this->db->getConnection();
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();
        $step = "Inicio";
        try {
            $step = "Instanciar handler";
            $asientoHandler = new AsientoContableHandler($this->config);
            
            $step = "Obtener consecutivo del paquete";
            $asiento = $asientoHandler->obtenerConsecutivoPaquete($paquete, $pdo);
            
            // Actualizar numeroAsiento en todas las líneas con el consecutivo obtenido
            foreach ($lineas as &$linea) {
                $linea['numeroAsiento'] = $asiento;
            }
            unset($linea); // Liberar referencia
            
            $step = "Generar asiento contable";
            $asientoHandler->generarAsientoContable($lineas, $tipoCambio, $notas, $nit, $paquete, $tipoAsiento, $fecha, $pdo);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Error al registrar asiento [$step]: " . $e->getMessage());
        }
    }
    
}