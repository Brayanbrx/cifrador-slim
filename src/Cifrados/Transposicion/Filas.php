<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Filas (UTF-8).
 *
 * Cifrar:
 *   • Se escribe top-down en r filas.
 *   • Se lee left-right.
 *
 * Descifrar:
 *   • Reconstruye las filas según longitudes,
 *     luego lee column-major para recuperar el claro.
 */
final class Filas
{
    private const PAD = 'X';

    /* ---------- CIFRAR ---------- */
    public function cifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Filas debe ser ≥ 2');
        }

        // Normalizar: A-Z + Ñ, a mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );

        $len = mb_strlen($clean, 'UTF-8');
        $c   = (int)ceil($len / $r);          // nº de columnas
        $pad = $r * $c - $len;                // relleno necesario
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        // Escribe column-major
        $rows = array_fill(0, $r, []);
        $chars = $this->mbStrSplit($clean);
        foreach ($chars as $i => $ch) {
            $row = $i % $r;
            $rows[$row][] = $ch;
        }

        // Lee row-major
        $out = '';
        foreach ($rows as $rowArr) {
            $out .= implode('', $rowArr);
        }
        return $out;
    }

    /* ---------- DESCIFRAR ---------- */
    public function descifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Filas debe ser ≥ 2');
        }

        $len = mb_strlen($txt, 'UTF-8');
        $c   = (int)ceil($len / $r);          // estimado de columnas

        $q   = intdiv($len, $r);              // columnas completas
        $rem = $len % $r;                     // primeras $rem filas tienen +1 char

        // Cortar texto en filas según longitud calculada
        $rows = [];
        $pos  = 0;
        for ($i = 0; $i < $r; $i++) {
            $lenRow = $q + (($i < $rem) ? 1 : 0);
            $rows[$i] = $this->mbSubstr($txt, $pos, $lenRow);
            $rows[$i] = $this->mbStrSplit($rows[$i]);      // array de chars
            $pos     += $lenRow;
        }

        // Leer column-mayor para reconstruir texto original
        $out = '';
        for ($col = 0; $col < $c; $col++) {
            for ($row = 0; $row < $r; $row++) {
                if (isset($rows[$row][$col])) {
                    $out .= $rows[$row][$col];
                }
            }
        }
        // Eliminar relleno X al final
        return rtrim($out, self::PAD);
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
