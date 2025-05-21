<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Filas
 * - Cifrar: se escribe por columnas (top-down) en una matriz r×c
 *           y se lee por filas (left-right).
 * - Descifrar: proceso inverso.
 */
final class Filas
{
    private const PAD = 'X';

    public function cifrar(string $txt, int $r): string
    {
        if ($r < 2) throw new \InvalidArgumentException('Filas debe ser ≥ 2');

        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $len   = strlen($clean);

        /* calc columnas y relleno */
        $c     = (int)ceil($len / $r);
        $pad   = $r * $c - $len;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        /* escribir column-major & leer row-major */
        $rows = array_fill(0, $r, '');
        for ($i = 0; $i < strlen($clean); $i++) {
            $row = $i % $r;
            $rows[$row] .= $clean[$i];
        }
        return implode('', $rows);
    }

    public function descifrar(string $txt, int $r): string
    {
        if ($r < 2) throw new \InvalidArgumentException('Filas debe ser ≥ 2');

        $len = strlen($txt);
        $c   = (int)ceil($len / $r);
        $q   = intdiv($len, $r);     // columnas completas
        $rem = $len % $r;            // filas con un char extra

        /* reconstruir filas */
        $rows = [];
        $pos  = 0;
        for ($i = 0; $i < $r; $i++) {
            $lenRow   = $q + (($i < $rem) ? 1 : 0);
            $rows[$i] = substr($txt, $pos, $lenRow);
            $pos     += $lenRow;
        }

        /* leer column-major para obtener texto original */
        $out = '';
        for ($col = 0; $col < $c; $col++) {
            for ($row = 0; $row < $r; $row++) {
                if (isset($rows[$row][$col])) {
                    $out .= $rows[$row][$col];
                }
            }
        }
        return rtrim($out, self::PAD); // quita relleno
    }
}
