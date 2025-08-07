<?php

namespace SoftlandERP;

use Exception;
use SoftlandERP\Models\Cliente;

class ClienteHandler
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
        $this->db = MSSQLDB::getInstance($config);
    }


    /**
     * @param Cliente $cliente
     * @return string codigo de cliente
     */
    public function insertar($cliente)
    {
        $tipo = Utils::decodificarTipoIdentificacion($cliente->nit);

        if($tipo == "ND"){
            throw new \Exception("Tipo de identificación no válido: $tipo. Verifique el NIT del cliente: [$cliente->nit]");
        }

        $nitConMascara = $this->aplicarMascara($cliente->nit, $tipo);

        if($nitConMascara == null || $nitConMascara == ""){
            throw new \Exception("Error aplicando mascara para numero de identificacion. NIT no válido: [$cliente->nit] para el tipo de identificación [$tipo]");
        }

        if (!$this->existeNit($nitConMascara)) {
            $this->registrarNit($cliente, $tipo, $nitConMascara);            
        }
        $existente = $this->consultarCodigoClienteNit($nitConMascara);
        if($existente){
            return $existente;
        }else{
            return $this->insertarCliente($cliente, $nitConMascara);
        }
    }

    /**
     * @param string $nit
     * @return boolean
     */
    public function existeNit($nit)
    {
        $record =
            $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "NIT"))
            ->select("NIT,ACTIVO")
            ->where("NIT", "=", $nit)
            ->get()->first();

        return $record && $record->{"ACTIVO"} == "S";
    }

    /**
     * @param string $nit
     * @return string|null codigo cliente NIT
     */
    public function consultarCodigoClienteNit($nit)
    {
        $record =
            $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CLIENTE"))
            ->select("CLIENTE")
            ->where("CONTRIBUYENTE", $nit)
            ->get()->first();

        return $record ? $record->{"CLIENTE"} : null;
    }

    /**
     * @param Cliente $cliente
     * @param string|null $tipo
     * @param string|null $nitConMascara
     * @return string NIT con mascara
     */
    public function registrarNit($cliente, $tipo = null, $nitConMascara = null)
    {
        if ($tipo == null) {
            $tipo = Utils::decodificarTipoIdentificacion($cliente->nit);
        }

        if ($nitConMascara == null) {
            $nitConMascara = $this->aplicarMascara($cliente->nit, $tipo);
        }
        $nitParams = [
            "NIT" => $nitConMascara,
            "RAZON_SOCIAL" => $cliente->nombre,
            "ALIAS" => $cliente->nombre,
            "NOTAS" => $cliente->codigo,
            "TIPO" => $tipo
        ];

        // registrar NIT
        $this->db->insert(Utils::tableSchema($this->config->get("DB_SCHEMA"), "NIT"), $nitParams);
        return $nitConMascara;
    }

    /**
     * @param string $nit
     * @param string $tipo     
     * @return string nit con mascara aplicada
     */
    public function aplicarMascara($nit, $tipo)
    {
        $tipoNit = $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "TIPO_NIT"))
            ->select("MASCARA,ACTIVO")
            ->where("TIPO", $tipo)
            ->get()
            ->first();

        if ($tipoNit) {
            return Utils::aplicarMascara($nit, $tipoNit->{"MASCARA"});
        }
        return $nit;
    }

    /**
     * @param Cliente @cliente
     * @param string @nitConMascara
     * @return string consecutivo
     */
    private function insertarCliente($cliente, $nitConMascara)
    {
        $consecutivo = $this->obtenerConsecutivo();
        $params = $this->crearParametrosInsert($cliente, $nitConMascara, $consecutivo);
        $this->db->insert(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CLIENTE"),$params);
        return $consecutivo;
    }

    /**
     * @param Cliente $cliente
     * @param string $nit
     * @param string $consecutivo
     * @return array insert params array
     */
    private function crearParametrosInsert($cliente, $nitConMascara, $consecutivo)
    {
        $ahora = date('Y-m-d H:i:s');
        //$tipoNit = Utils::decodificarTipoIdentificacion($cliente->nit);
        return [
            "CLIENTE" => $consecutivo,
            "NOMBRE" => $cliente->nombre,
            "DETALLE_DIRECCION" => 0,
            "ALIAS" => $cliente->nombre,
            "CONTACTO" => "ND",
            "CARGO" => "ND",
            "DIRECCION" => $cliente->direccion,
            "DIR_EMB_DEFAULT" => "ND",
            "TELEFONO1" => $cliente->telefono,
            "TELEFONO2" => $cliente->telefono,
            "FAX" => "",
            "CONTRIBUYENTE" => $nitConMascara,
            "FECHA_INGRESO" => $ahora,
            "MULTIMONEDA" => "N",
            "MONEDA" => "CRC",
            "SALDO" => 0,
            "SALDO_LOCAL" => 0,
            "SALDO_DOLAR" => 0,
            "SALDO_CREDITO" => 0,
            "SALDO_NOCARGOS" => 0,
            "LIMITE_CREDITO" => 1,
            "EXCEDER_LIMITE" => "N",
            "TASA_INTERES" => 0,
            "TASA_INTERES_MORA" => 0,
            "FECHA_ULT_MORA" => "19800101",
            "FECHA_ULT_MOV" => "19800101",
            "CONDICION_PAGO" => "0",
            "NIVEL_PRECIO" => Constantes::CLIENTES_NIVEL_PRECIO,
            "DESCUENTO" => 0,
            "MONEDA_NIVEL" => "L",
            "ACEPTA_BACKORDER" => "N",
            "PAIS" => Constantes::CLIENTES_PAIS,
            "ZONA" => "ND",
            "RUTA" => "ND",
            "VENDEDOR" => "ND",
            "COBRADOR" => "ND",
            "ACEPTA_FRACCIONES" => "N",
            "ACTIVO" => "S",
            "EXENTO_IMPUESTOS" =>  "N",
            "EXENCION_IMP1" => 0,
            "EXENCION_IMP2" => 0,
            "COBRO_JUDICIAL" =>  "N",
            "CATEGORIA_CLIENTE" => Constantes::CLIENTES_CATEGORIA_CLIENTE,
            "CLASE_ABC" => "A",
            "DIAS_ABASTECIMIEN" => 0,
            "USA_TARJETA" => "N",
            "E_MAIL" => "ND",
            "REQUIERE_OC" => "N",
            "TIENE_CONVENIO" => "N",
            "NOTAS" => "[$ahora]: Generado desde CloudCampusPRO",
            "DIAS_PROMED_ATRASO" => 0,
            "USAR_DIREMB_CORP" => "N",
            "APLICAC_ABIERTAS" => "N",
            "VERIF_LIMCRED_CORP" => "N",
            "USAR_DESC_CORP" => "N",
            "DOC_A_GENERAR" => "F",
            "ASOCOBLIGCONTFACT" => "N",
            "ES_CORPORACION" => "N",
            "REGISTRARDOCSACORP" => "N",
            "USAR_PRECIOS_CORP" => "N",
            "USAR_EXENCIMP_CORP" => "N",
            "AJUSTE_FECHA_COBRO" => "A",
            "CLASE_DOCUMENTO" => "N",
            "LOCAL" => "L",
            "TIPO_CONTRIBUYENTE" => "F",
            "ACEPTA_DOC_ELECTRONICO" => "N",
            "CONFIRMA_DOC_ELECTRONICO" => "N",
            "ACEPTA_DOC_EDI" => "N",
            "NOTIFICAR_ERROR_EDI" => "N",
            "MOROSO" => "N",
            "MODIF_NOMB_EN_FAC" => "N",
            "SALDO_TRANS" => 0,
            "SALDO_TRANS_LOCAL" => 0,
            "SALDO_TRANS_DOLAR" => 0,
            "PERMITE_DOC_GP" => "N",
            "PARTICIPA_FLUJOCAJA" => "N",
            "USUARIO_CREACION" => "ERPADMIN",
            //"FECHA_HORA_CREACION" => null,
            "DETALLAR_KITS" => "N",
            "TIPO_IMPUESTO" => $cliente->tipoImpuesto,
            "TIPO_TARIFA" => $cliente->codigoTarifa,
            "PORC_TARIFA" => $cliente->tarifa,
            "EMAIL_DOC_ELECTRONICO" => $cliente->emailFE,
            "GLN" => $cliente->gln,
            "DIVISION_GEOGRAFICA1" => $cliente->divisionGeografica1,
            "DIVISION_GEOGRAFICA2" => $cliente->divisionGeografica2,
            "DIVISION_GEOGRAFICA3" => $cliente->divisionGeografica3,
            "DIVISION_GEOGRAFICA4" => $cliente->divisionGeografica4,
            "USA_API_RECEPCION" => "N",
            "TIPIFICACION_CLIENTE" => "05",
            "AFECTACION_IVA" => $cliente->afectacionIVA ?: "03",
            //"U_PROVINCIA" => null,
            //"U_CANTON" => null,
            //"U_DISTRITO" => null,
            //"FCH_HORA_ULT_MOD" => null,
        ];
    }

    /**
     * @return string siguiente consecutivo
     */
    public function obtenerConsecutivo()
    {
        try {
            print("BEGIN obtenerConsecutivo\n");
            $record =
                $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CONSECUTIVO"))
                ->select("ULTIMO_VALOR")
                ->where("CONSECUTIVO", Constantes::CLIENTES_CODIGO_CONSECUTIVO)
                ->get()
                ->first();

            if (!$record) {
                throw new \Exception(sprintf("No se encuentra el consecutivo para %s", Constantes::CLIENTES_CODIGO_CONSECUTIVO));
            }

            $siguiente = Utils::generarSiguienteConsecutivo($record->{"ULTIMO_VALOR"});

            $this->db->update(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CONSECUTIVO"), ["ULTIMO_VALOR" => $siguiente], [["CONSECUTIVO", Constantes::CLIENTES_CODIGO_CONSECUTIVO]]);
            return $siguiente;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            print("END obtenerConsecutivo\n");
        }
    }

    /**
     * @param string $codigo
     * @return Cliente|null objeto cliente
     */
    public function consultarCliente($codigoCliente)
    {
        /**
         * @var Cliente $cliente
         */
        $cliente = null;
        $record =
            $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CLIENTE"))
            ->select('CLIENTE, CONTRIBUYENTE, CATEGORIA_CLIENTE')
            ->where("CLIENTE", $codigoCliente)
            ->get()
            ->first();

        if($record) {
            $categoria =
                $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CATEGORIA_CLIENTE"))
                ->select('CTR_CXC, CTA_CXC')
                ->where("CATEGORIA_CLIENTE", $record->{"CATEGORIA_CLIENTE"})
                ->get()
                ->first();

            $cliente = new Cliente();
            $cliente->codigo = $record->{"CLIENTE"};
            $cliente->nit = $record->{"CONTRIBUYENTE"};
            $cliente->categoria = $record->{"CATEGORIA_CLIENTE"};
            $cliente->cuentaContable = $categoria->{"CTA_CXC"};
            $cliente->centroCosto = $categoria->{"CTR_CXC"};
        }
        return $cliente;
    }

    /**
     * @param string $nit
     * @return Cliente|null objeto cliente
     */
    public function consultarClienteNit($nit)
    {
        /**
         * @var Cliente $cliente
         */
        $cliente = null;
        $record =
            $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CLIENTE"))
            ->select('CLIENTE, CONTRIBUYENTE, CATEGORIA_CLIENTE')
            ->where("CONTRIBUYENTE", $nit)
            ->get()
            ->first();

            //CATEGORIA_CLIENTE.CTR_CXC, CATEGORIA_CLIENTE.CTA_CXC
        if($record) {

            $categoria =
                $this->db->table(Utils::tableSchema($this->config->get("DB_SCHEMA"), "CATEGORIA_CLIENTE"))
                ->select('CTR_CXC, CTA_CXC')
                ->where("CATEGORIA_CLIENTE", $record->{"CATEGORIA_CLIENTE"})
                ->get()
                ->first();

            $cliente = new Cliente();
            $cliente->codigo = $record->{"CLIENTE"};
            $cliente->nit = $record->{"CONTRIBUYENTE"};
            $cliente->categoria = $record->{"CATEGORIA_CLIENTE"};
            $cliente->cuentaContable = $categoria->{"CTA_CXC"};
            $cliente->centroCosto = $categoria->{"CTR_CXC"};
        }
        return $cliente;
    }
}
