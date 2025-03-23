<?php

namespace SoftlandERP;

class Utils
{

    /**
     * @param string $nit
     * @return string tipo identificacion
     */
    public static function decodificarTipoIdentificacion($nit)
    {
        if (self::validarTipoIdentificacion($nit, Constantes::GENERAL_TIPO_ID_FISICO)) {
            return Constantes::GENERAL_TIPO_STR_FISICO;
        } elseif (self::validarTipoIdentificacion($nit, Constantes::GENERAL_TIPO_ID_JURIDICO)) {
            return Constantes::GENERAL_TIPO_STR_JURIDICO;
            /*}elseif (self::validarTipoIdentificacion(Constantes::GENERAL_TIPO_ID_DIMEX, $nit)) {
            return Constantes::GENERAL_TIPO_ID_DIMEX;
        }elseif (self::validarTipoIdentificacion(Constantes::GENERAL_TIPO_ID_NITE, $nit)) {
            return Constantes::GENERAL_TIPO_ID_NITE;*/
        } else {
            return Constantes::GENERAL_TIPO_STR_ND;
        }
    }

    /**
     * @param string @nit
     * @param string @tipo     
     * @return boolean
     */
    private static function validarTipoIdentificacion($nit, $tipo)
    {
        switch ($tipo) {
            case Constantes::GENERAL_TIPO_ID_FISICO:
                return self::validarIdentificacionRegex($nit, "/^([1-9]{1})([0-9]{4})([0-9]{4})$/");
            case Constantes::GENERAL_TIPO_ID_JURIDICO:
                return self::validarIdentificacionRegex($nit, "/^([2,3,4,5]{1})([0-9]{3})([0-9]{6})$/");
            default:
                return false;
        }
    }

    /**
     * @param string @nit
     * @param string @regex
     * @return boolean
     */
    private static function validarIdentificacionRegex($nit, $regex)
    {
        return preg_match($regex, $nit);
    }

    public static function aplicarMascara($string, $mask)
    {
        $result = '';
        $stringIndex = 0;
        $maskLength = strlen($mask);

        if ($maskLength == 0) {
            return $string;
        }

        for ($i = 0; $i < $maskLength; $i++) {
            if ($stringIndex >= strlen($string)) {
                break;
            }

            if ($mask[$i] === '9') {
                $result .= $string[$stringIndex];
                $stringIndex++;
            } else {
                $result .= $mask[$i];
            }
        }

        return $result;
    }

    /**
     * @param string @string
     * @return int
     */
    public static function extraerNumero($string)
    {
        // Extract number and increment
        $number =  preg_replace("/^\D+/", "", $string);
        return $number ? (int)$number : 0;
    }

    /**
     * @param string @string
     * @return string siguiente consecutivo
     */
    public static function generarSiguienteConsecutivo($string)
    {
        // Extract prefix using regex
        preg_match("/^\D+/", $string, $matches);
        $prefix = $matches[0];

        // Extract number and increment
        $number = preg_replace("/^\D+/", "", $string);
        $numberPlusOne = (int)$number + 1;

        // Pad the incremented number with leading zeros
        $padLength = strlen($number);
        $numberPadded = str_pad($numberPlusOne, $padLength, "0", STR_PAD_LEFT);

        // Concatenate prefix and padded number
        $result = $prefix . $numberPadded;

        // Output result
        return $result;
    }

    /**
     * @param string @schemaName
     * @param string @tableName
     * @return string contact schema+table
     */
    public static function tableSchema($schemaName, $tableName)
    {
        return "$schemaName.$tableName";
    }
}
