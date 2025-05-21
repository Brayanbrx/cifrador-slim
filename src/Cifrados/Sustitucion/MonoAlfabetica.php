<?php
namespace App\Cifrados\Sustitucion;

/**
 * Cifrado MonoAlfabético por alfabeto keyado.
 */
final class MonoAlfabetica
{
    private const ABC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function cifrar(string $txt, string $clave): string
    {
        [$plain,$cipher] = $this->buildAlphabets($clave);
        return strtr(strtoupper(preg_replace('/[^A-Z]/','',$txt)), $plain, $cipher);
    }

    public function descifrar(string $txt, string $clave): string
    {
        [$plain,$cipher] = $this->buildAlphabets($clave);
        return strtr(strtoupper(preg_replace('/[^A-Z]/','',$txt)), $cipher, $plain);
    }

    /* ------------ helpers ------------ */
    private function buildAlphabets(string $clave): array
    {
        if (!preg_match('/^[A-Za-z]+$/',$clave)) {
            throw new \InvalidArgumentException('La clave debe contener solo letras A-Z');
        }
        $clave = strtoupper($clave);

        /* quitar duplicados manteniendo orden */
        $unique = '';
        foreach (str_split($clave) as $ch) {
            if (strpos($unique,$ch)===false) $unique .= $ch;
        }

        /* alfabeto cifrado */
        $rest = str_replace(str_split($unique),'',self::ABC);
        $cipher = $unique.$rest;       // longitud 26 garantizada

        return [self::ABC, $cipher];
    }
}
