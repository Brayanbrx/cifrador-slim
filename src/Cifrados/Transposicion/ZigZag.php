<?php
namespace App\Cifrados\Transposicion;

/**
 * Cifrado Rail-Fence (Zig-Zag).
 *  – r ≥ 2 rieles
 *  – relleno X en caso de ser necesario (solo en cifrado)
 */
final class ZigZag
{
    private const PAD = 'X';

    /* ------------- CIFRAR ------------- */
    public function cifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Rieles debe ser ≥ 2');
        }

        // Normalizar: quitar todo lo que no sea A-Z o Ñ, y pasar a mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );

        // Dividir en rieles (arrays) usando recorrido zig-zag
        $rails = array_fill(0, $r, []);
        $row = 0; $dir = 1;
        foreach ($this->mbStrSplit($clean) as $ch) {
            $rails[$row][] = $ch;
            $row += $dir;
            if ($row === $r - 1 || $row === 0) {
                $dir *= -1;               // cambiar dirección
            }
        }

        // Concatenar rail-por-rail
        $out = '';
        foreach ($rails as $rail) {
            $out .= implode('', $rail);
        }
        return $out;
    }

    /* ------------- DESCIFRAR ------------- */
    public function descifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Rieles debe ser ≥ 2');
        }

        $len   = mb_strlen($txt, 'UTF-8');
        $cycle = 2 * ($r - 1);

        // 1) ¿cuántos caracteres caen en cada rail?
        $railLen = array_fill(0, $r, 0);
        for ($i = 0; $i < $len; $i++) {
            $row = $this->rowAt($i, $r);
            $railLen[$row]++;
        }

        // 2) Cortar la cadena en trozos multibyte para cada rail
        $rails = [];
        $pos   = 0;
        for ($i = 0; $i < $r; $i++) {
            $rails[$i] = $this->mbSubstr($txt, $pos, $railLen[$i]);
            $rails[$i] = $this->mbStrSplit($rails[$i]);   // array de chars
            $pos      += $railLen[$i];
        }

        // 3) Reconstruir recorriendo zig-zag
        $idx = array_fill(0, $r, 0);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $row = $this->rowAt($i, $r);
            $out .= $rails[$row][$idx[$row]++];
        }
        return $out;
    }

    /* devuelve la fila (0-based) para la posición i */
    private function rowAt(int $i, int $r): int
    {
        $cycle = 2 * ($r - 1);
        $pos   = $i % $cycle;
        return ($pos < $r) ? $pos : $cycle - $pos;
    }

    /* ---------- helpers multibyte ---------- */

    /** Divide string UTF-8 en array de caracteres */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** mb_substr ‘safe’ que admite longitud multibyte */
    private function mbSubstr(string $s, int $start, int $length): string
    {
        return mb_substr($s, $start, $length, 'UTF-8');
    }
}
