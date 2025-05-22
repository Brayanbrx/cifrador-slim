<?php
namespace App\Cifrados\Sustitucion;

/**
 * Cifrado MonoAlfabético con alfabeto español (A–Z).
 */
final class MonoAlfabetica
{
    /** Alfabeto de 27 letras*/
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    /* ---------- API pública ---------- */
    /**
     * Cifra el texto usando la clave.
     * @param string $txt   Texto a cifrar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string Texto cifrado
     */
    public function cifrar(string $txt, string $clave): string
    {
        [$plain, $cipher] = $this->buildAlphabets($clave);      // plain = ABC, cipher = clave
        return $this->translate($txt, $plain, $cipher);         // mapeamos plain → cipher
    }

    /**
     * Descifra el texto usando la clave.
     * @param string $txt   Texto a descifrar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string Texto descifrado
     */
    public function descifrar(string $txt, string $clave): string
    {
        [$plain, $cipher] = $this->buildAlphabets($clave);
        return $this->translate($txt, $cipher, $plain);         // mapeamos cipher → plain
    }

    /* ---------- helpers ---------- */
    /**
     * Traduce el texto usando los alfabetos de origen y destino.
     * @param string $txt Texto a traducir
     * @param string $src Alfabeto de origen
     * @param string $dst Alfabeto de destino
     * @return string Texto traducido
     */
    private function translate(string $txt, string $src, string $dst): string
    {
        $map = array_combine(
            $this->mbStrSplit($src),
            $this->mbStrSplit($dst)
        );

        $out = '';
        foreach ($this->mbStrSplit(mb_strtoupper($txt, 'UTF-8')) as $ch) {
            $out .= $map[$ch] ?? '';               // ignora caracteres fuera del alfa
        }
        return $out;
    }

    /** Devuelve [$plain,$cipher] */
    /**
     * Construye los alfabetos de cifrado y plano a partir de la clave.
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return array [$plain, $cipher]
     */
    private function buildAlphabets(string $clave): array
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $clave)) {
            throw new \InvalidArgumentException('La clave solo puede contener letras A-Z y Ñ');
        }
        $clave = mb_strtoupper($clave, 'UTF-8');       // normalizar clave
        /* quitar duplicados conservando orden (multibyte-safe) */
        $unique = '';
        foreach ($this->mbStrSplit($clave) as $ch) {     // letra por letra
            if (mb_strpos($unique, $ch, 0, 'UTF-8') === false) {
                $unique .= $ch;
            }
        }

        /* alfabeto cifrado */
        $rest   = str_replace($this->mbStrSplit($unique), '', self::ABC);   // resto del alfabeto
        $cipher = $unique . $rest;                                          // longitud 27 garantizada

        return [self::ABC, $cipher];
    }

    /** split multibyte string into array of chars (PHP >= 7.4) */
    /**
     * Divide una cadena UTF-8 en un array de caracteres.
     * @param string $s Cadena a dividir
     * @return array Array de caracteres
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
