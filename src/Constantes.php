<?php

namespace SoftlandERP;

class Constantes
{
    const GENERAL_TIPO_ID_ND =  "00";
    const GENERAL_TIPO_ID_FISICO =  "01";
    const GENERAL_TIPO_ID_JURIDICO =  "02";
    const GENERAL_TIPO_ID_DIMEX =  "03";
    const GENERAL_TIPO_ID_NITE =  "04";
    const GENERAL_TIPO_ID_OTROS =  "ND";

    const GENERAL_TIPO_STR_FISICO =  "FISICAS";
    const GENERAL_TIPO_STR_JURIDICO =  "JURIDICO";
    const GENERAL_TIPO_STR_ND =  "ND";

    const CLIENTES_CODIGO_CONSECUTIVO = "CLI-L";
    const CLIENTES_NIVEL_PRECIO = "ND-LOCAL";
    const CLIENTES_PAIS = "CRI";
    const CLIENTES_CATEGORIA_CLIENTE = "LOC";

    /**
     * Load environment variables from .env file
     */
    public static function loadEnv($envPath = null)
    {
        if ($envPath === null) {
            // Try to find .env file in common locations
            $possiblePaths = [
                dirname(__DIR__) . '/.env',
                dirname(dirname(__DIR__)) . '/.env',
                getcwd() . '/.env'
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envPath = $path;
                    break;
                }
            }
        }

        if ($envPath && file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    }

    /**
     * Get environment variable with fallback
     */
    public static function getEnv($key, $fallback = null)
    {
        $value = isset($_ENV[$key]) ? $_ENV[$key] : getenv($key);
        return $value !== false ? $value : $fallback;
    }

    /**
     * Get CLIENTES_CATEGORIA_CLIENTE from environment or fallback to default
     */
    public static function getClientesCategoriaCliente()
    {
        return self::getEnv('CLIENTES_CATEGORIA_CLIENTE', Constantes::CLIENTES_CATEGORIA_CLIENTE);
    }

    /**
     * Get CLIENTES_NIVEL_PRECIO from environment or fallback to default
     */
    public static function getClientesNivelPrecio()
    {
        return self::getEnv('CLIENTES_NIVEL_PRECIO', Constantes::CLIENTES_NIVEL_PRECIO);
    }

    /**
     * Get CLIENTES_CODIGO_CONSECUTIVO from environment or fallback to default
     */
    public static function getClientesCodigoConsecutivo()
    {
        return self::getEnv('CLIENTES_CODIGO_CONSECUTIVO', Constantes::CLIENTES_CODIGO_CONSECUTIVO);
    }

    /**
     * Get CLIENTES_PAIS from environment or fallback to default
     */
    public static function getClientesPais()
    {
        return self::getEnv('CLIENTES_PAIS', Constantes::CLIENTES_PAIS);
    }

    /**
     * Get GENERAL_TIPO_STR_FISICO from environment or fallback to default
     */
    public static function getGeneralTipoStrFisico()
    {
        return self::getEnv('GENERAL_TIPO_STR_FISICO', Constantes::GENERAL_TIPO_STR_FISICO);
    }

    /**
     * Get GENERAL_TIPO_STR_JURIDICO from environment or fallback to default
     */
    public static function getGeneralTipoStrJuridico()
    {
        return self::getEnv('GENERAL_TIPO_STR_JURIDICO', Constantes::GENERAL_TIPO_STR_JURIDICO);
    }
}
