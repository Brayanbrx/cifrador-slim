<?php
namespace App\Cifrados\Sustitucion;

/**
 * Cifrado MonoAlfabético con alfabeto español (A–Z).
 */
final class MonoAlfabetica
{
    /** Alfabeto de 27 letras*/
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    public function cifrar(string $txt, string $clave): string
    {
        [$plain, $cipher] = $this->buildAlphabets($clave);
        return $this->translate($txt, $plain, $cipher);
    }

    public function descifrar(string $txt, string $clave): string
    {
        [$plain, $cipher] = $this->buildAlphabets($clave);
        return $this->translate($txt, $cipher, $plain);
    }

    /* ---------- helpers ---------- */

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
    private function buildAlphabets(string $clave): array
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $clave)) {
            throw new \InvalidArgumentException('La clave solo puede contener letras A-Z y Ñ');
        }
        $clave = mb_strtoupper($clave, 'UTF-8');

        /* quitar duplicados conservando orden (multibyte-safe) */
        $unique = '';
        foreach ($this->mbStrSplit($clave) as $ch) {
            if (mb_strpos($unique, $ch, 0, 'UTF-8') === false) {
                $unique .= $ch;
            }
        }

        /* alfabeto cifrado */
        $rest   = str_replace($this->mbStrSplit($unique), '', self::ABC);
        $cipher = $unique . $rest;                 // longitud 27 garantizada

        return [self::ABC, $cipher];
    }

    /** split multibyte string into array of chars (PHP >= 7.4) */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
