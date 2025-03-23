<?php

namespace SoftlandERP\Models;


/**
 * 
 
        public string ASIENTO { get; set; }        
        [Column(Order = 1)]
        public int CONSECUTIVO { get; set; }
        public string NIT { get; set; }
        public string CENTRO_COSTO { get; set; }
        public string CUENTA_CONTABLE { get; set; }
        public decimal? DEBITO_LOCAL { get; set; }
        public decimal? CREDITO_LOCAL { get; set; }
        public decimal? BASE_LOCAL { get; set; }
        public decimal? DEBITO_DOLAR { get; set; }
        public decimal? CREDITO_DOLAR { get; set; }        
        public decimal? BASE_DOLAR { get; set; }
        public string TIPO_CAMBIO { get; set; }
        public string REFERENCIA { get; set; }
        public string FUENTE { get; set; } // "FA"
        public string PROYECTO { get; set; }
        public string FASE { get; set; }
        public decimal? CREDITO_UNIDADES { get; set; }
        public decimal? DEBITO_UNIDADES { get; set; }
 */

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
