<?php

namespace SoftlandERP\Models;

/**
 * Representa un registro de Documento de cuentas por cobrar tabla DOCUMENTOS_CC
 */
class DocumentoCC
{
    /**
     * @var string
     */
    public $tipo;
    /**
     * @var int
     */
    public $subtipo;
    /**
     * @var string
     */
    public $documento;
    /**
     * @var string
     */
    public $moneda;
    /**
     * @var string
     */
    public $fecha;
    /**
     * @var double
     */
    public $subtotal;
    /**
     * @var double
     */
    public $descuento;
    /**
     * @var double
     */
    public $impuesto;
    /**
     * @var double
     */
    public $monto;
    /**
     * @var double
     */
    public $saldo;
    /**
     * @var double
     */
    public $tipoCambioDolar;
    /**
     * @var string
     */
    public $aplicacion;
    /**
     * @var string|null
     */
    public $cliente;    
    /**
     * @var string
     */
    public $referencia;
    /**
     * @var string
     */
    public $centroCosto;
    /**
     * @var string
     */
    public $cuentaContable;

    /**
     * @var string
     */
    public $documentoAplicacion;

    /**
     * @var string|null
     */
    public $nit;

    /**
     * @var string|null
     */
    public $centroCostoImpuesto;
    /**
     * @var string|null
     */
    public $cuentaContableImpuesto;

    /**
     * @var string|null
     */
    public $asiento;

    /**
     * @var array|null
     */
    public $deducciones;


}
