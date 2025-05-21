<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Series (permuta cada bloque según clave numérica).
 */
final class Series
{
    private const PAD = 'X';

    public function cifrar(string $txt, string $orden): string
    {
        $perm = $this->parsePerm($orden);
        return $this->procesar($txt, $perm, false);
    }

    public function descifrar(string $txt, string $orden): string
    {
        $perm = $this->parsePerm($orden);
        return $this->procesar($txt, $perm, true);
    }

    /* ------------- helpers ------------- */

    /** Convierte "3142" en [2,0,3,1] (índ. 0-based) y valida */
    private function parsePerm(string $orden): array
    {
        if (!preg_match('/^[1-9]+$/', $orden)) {
            throw new \InvalidArgumentException('Orden numérico inválido');
        }
        $digits = array_map('intval', str_split($orden));
        $n = count($digits);

        /* deben ser una permutación de 1..n */
        sort($digits);
        for ($i=1;$i<=$n;$i++){
            if ($digits[$i-1] !== $i) {
                throw new \InvalidArgumentException('Orden debe ser permutación 1..'. $n);
            }
        }
        /* devolver array de posiciones destino 0-based */
        $perm = [];
        foreach (str_split($orden) as $d) {
            $perm[] = $d-1;
        }
        return $perm; // ej [2,0,3,1]
    }

    /**
     * $inverse = true aplica la permutación inversa (descifrado)
     */
    private function procesar(string $txt, array $perm, bool $inverse): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $n     = count($perm);

        /* inversa si desciframos */
        if ($inverse) {
            $inv = array_fill(0,$n,0);
            foreach ($perm as $i=>$p) $inv[$p] = $i;
            $perm = $inv;
        }

        /* relleno */
        $pad = ($n - (strlen($clean) % $n)) % $n;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        /* permutar cada bloque */
        $out = '';
        for ($i=0;$i<strlen($clean);$i+=$n){
            $block = substr($clean,$i,$n);
            $tmp   = str_split($block);
            $permBlock = array_fill(0,$n,'');
            foreach ($perm as $src=>$dst){
                $permBlock[$dst] = $tmp[$src];
            }
            $out .= implode('',$permBlock);
        }
        return $out;
    }
}
