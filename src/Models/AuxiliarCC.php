<?php

namespace SoftlandERP\Models;

/**
 * Representa un registro de Auxiliar de Credito y Debito tabla AUXILIAR_CC
 */
class AuxiliarCC
{
    /**
     * @var string
     */
    public $tipoCredito;
    /**
     * @var string
     */
    public $tipoDebito;
    /**
     * @var string
     */
    public $docCredito;
    /**
     * @var string
     */
    public $docDebito;
    /**
     * @var double
     */
    public $monto;
    /**
     * @var double
     */
    public $tipoCambioDolar;
    /**
     * @var string
     */
    public $cliente;
    /**
     * @var string
     */
    public $fecha;
}