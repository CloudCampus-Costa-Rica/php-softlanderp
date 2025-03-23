<?php

namespace SoftlandERP;

class Client
{

    /**
     * 
     */
    private $db;

    /**
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->db = DB::getInstance($config);
    }


    public function getDocumentoCP()
    {
        return $this->db->table("UFAM.DOCUMENTOS_CP")
            ->select("DOCUMENTO,TIPO,PROVEEDOR")
            ->orderBy("DOCUMENTO")
            ->get()->first();
    }

}