<?php

namespace SoftlandERP\Models;

class Diario
{

    /**
     * @var string  
     */
    public $asiento;

    /**
     * @var int
     */
    public $consecutivo;

    /**
     * @var string|null
     */
    public $nit;

    /**
     * @var string|null
     */
    public $centroCosto;

    /**
     * @var string|null
     */
    public $cuentaContable;

    /**
     * @var double|null
     */
    public $debitoLocal;

    /**
     * @var double|null
     */
    public $creditoLocal;

    /**
     * @var double|null
     */
    public $baseLocal;

    /**
     * @var double|null
     */
    public $debitoDolar;

    /**
     * @var double|null
     */
    public $creditoDolar;

    /** 
     * @var double|null
     */
    public $baseDolar;

    /**
     * @var string|null
     */
    public $tipoCambio;

    /**
     * @var string|null
     */
    public $referencia;

    /**
     * @var string|null
     */
    public $fuente;
    /**
     * @var string|null
     */
    public $proyecto;

    /**
     * @var string|null
     */
    public $fase;

    /**
     * @var double|null
     */
    public $creditoUnidades;
    /**
     * @var double|null
     */
    public $debitoUnidades;
}
