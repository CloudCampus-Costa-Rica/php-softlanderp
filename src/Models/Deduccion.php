<?php

namespace SoftlandERP\Models;

/**
 * Representa un registro de decuccion para plantilla de asiento recibo
 * 
 */
class Deduccion
{
    
    /**
     * @var string
     */
    public $centroCosto;
    /**
     * @var string
     */
    public $cuentaContable;

    /**
     * @var double
     */
    public $factor;

    /**
     * @var string
     */
    public $descripcion; 

}
