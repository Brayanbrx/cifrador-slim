<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por grupos con inversión interna.
 * Requiere la extensión mbstring.
 */
final class Grupos
{
    private const PAD = 'X';

    /* ---------- API ---------- */
    public function cifrar(string $txt, int $g): string
    {
        return $this->procesar($txt, $g);
    }

    public function descifrar(string $txt, int $g): string
    {
        $out = $this->procesar($txt, $g);
        return rtrim($out, self::PAD);   // elimina todas las X finales
    }

    /* ---------- lógica común ---------- */
    private function procesar(string $txt, int $g): string
    {
        if ($g < 2) {
            throw new \InvalidArgumentException('Tamaño de grupo debe ser ≥ 2');
        }

        // Normalizar (A-Z + Ñ) y mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );

        // Relleno
        $len = mb_strlen($clean, 'UTF-8');
        $pad = ($g - ($len % $g)) % $g;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        // Invertir cada bloque
        $out = '';
        for ($i = 0; $i < mb_strlen($clean, 'UTF-8'); $i += $g) {
            $bloque = $this->mbSubstr($clean, $i, $g);
            $rev    = implode('', array_reverse($this->mbStrSplit($bloque)));
            $out   .= $rev;
        }
        return $out;
    }

    /* ---------- helpers multibyte ---------- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function mbSubstr(string $s, int $start, int $length): string
    {
        return mb_substr($s, $start, $length, 'UTF-8');
    }
}
