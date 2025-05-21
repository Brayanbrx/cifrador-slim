<?php
namespace App\Cifrados\Transposicion;

/**
 * Cifrado Rail-Fence (Zig-Zag).
 */
final class ZigZag
{
    private const PAD = 'X';

    /* ----------- CIFRAR ----------- */
    public function cifrar(string $txt, int $r): string
    {
        if ($r < 2) throw new \InvalidArgumentException('Rieles debe ser ≥ 2');

        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $rails = array_fill(0, $r, '');

        $row = 0; $dir = 1;
        foreach (str_split($clean) as $ch) {
            $rails[$row] .= $ch;
            $row += $dir;
            if ($row === $r-1 || $row === 0) $dir *= -1;   // cambiar dirección
        }
        return implode('', $rails);
    }

    /* ----------- DESCIFRAR ----------- */
    public function descifrar(string $txt, int $r): string
    {
        if ($r < 2) throw new \InvalidArgumentException('Rieles debe ser ≥ 2');

        $len   = strlen($txt);
        $cycle = 2*($r-1);

        /* calcular nº de caracteres por rail */
        $railLen = array_fill(0,$r,0);
        for ($i=0;$i<$len;$i++){
            $row = $this->rowAt($i,$r);
            $railLen[$row]++;
        }

        /* cortar la cadena en trozos para cada rail */
        $rails = [];
        $pos   = 0;
        for ($i=0;$i<$r;$i++){
            $rails[$i] = substr($txt,$pos,$railLen[$i]);
            $pos      += $railLen[$i];
        }

        /* reconstruir leyendo zig-zag */
        $idxRail = array_fill(0,$r,0);
        $out     = '';
        for ($i=0;$i<$len;$i++){
            $row = $this->rowAt($i,$r);
            $out .= $rails[$row][$idxRail[$row]++];
        }
        return $out;
    }

    /* devuelve la fila (0-based) en la que cae el índice i */
    private function rowAt(int $i, int $r): int
    {
        $cycle = 2*($r-1);
        $pos   = $i % $cycle;
        return ($pos < $r) ? $pos : $cycle - $pos;
    }
}
