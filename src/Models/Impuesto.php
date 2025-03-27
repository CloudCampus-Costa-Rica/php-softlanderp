<?php

namespace SoftlandERP\Models;

/**
 * Informacion de centro costos y cuenta contable de impuestos
 */
class Impuesto
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
    public $porcentaje;

    /**
     * @var string
     */
    public $codigo;

    /**
     * @var string
     */
    public $nombre;
}

