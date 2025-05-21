<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Columnas
 * Clave numérica (perm. columnas). C: leer columnas según dígitos 1..n asc.
 */
final class Columnas
{
    private const PAD = 'X';

    /* ---------- CIFRAR ---------- */
    public function cifrar(string $txt, string $orden): string
    {
        $perm = $this->parseKey($orden); // [col => rank]
        $n    = count($perm);

        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $pad   = ($n - (strlen($clean) % $n)) % $n;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        $rows = str_split($clean, $n);
        $out  = '';

        for ($rank = 1; $rank <= $n; $rank++) {
            $col = array_search($rank, $perm, true);
            foreach ($rows as $row) {
                $out .= $row[$col];
            }
        }
        return $out;
    }

    /* ---------- DESCIFRAR ---------- */
    public function descifrar(string $txt, string $orden): string
    {
        $perm = $this->parseKey($orden);
        $n    = count($perm);
        $len  = strlen($txt);
        $rows = intdiv($len, $n);

        /* cortar texto en columnas según orden 1..n */
        $cols = array_fill(0,$n,'');
        $pos  = 0;
        for ($rank = 1; $rank <= $n; $rank++) {
            $col            = array_search($rank,$perm,true);
            $cols[$col]     = substr($txt,$pos,$rows);
            $pos           += $rows;
        }

        /* reconstruir leyendo filas naturales */
        $out = '';
        for ($r=0;$r<$rows;$r++){
            for ($c=0;$c<$n;$c++){
                $out .= $cols[$c][$r];
            }
        }
        return rtrim($out, self::PAD);
    }

    /* valida y convierte "3142" en array [0=>3,1=>1,2=>4,3=>2] */
    private function parseKey(string $orden): array
    {
        if (!preg_match('/^[1-9]+$/', $orden)) {
            throw new \InvalidArgumentException('Clave numérica inválida');
        }
        $digits = array_map('intval', str_split($orden));
        $n      = count($digits);

        sort($digits);
        for ($i=1;$i<=$n;$i++){
            if ($digits[$i-1] !== $i) {
                throw new \InvalidArgumentException('Clave debe ser permutación 1..'.$n);
            }
        }
        return array_map('intval', str_split($orden));
    }
}
