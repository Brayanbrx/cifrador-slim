<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Series (permuta cada bloque según clave numérica),
 * compatible con Ñ (UTF-8).
 */
final class Series
{
    private const PAD = 'X';

    /* ------------------- API ------------------- */
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

    /** Valida y transforma «3142» → [2,0,3,1] (0-based) */
    private function parsePerm(string $orden): array
    {
        if (!preg_match('/^[1-9]+$/', $orden)) {
            throw new \InvalidArgumentException('Orden numérico inválido (solo dígitos 1-9, sin ceros).');
        }
        $digits = array_map('intval', str_split($orden));
        $n      = count($digits);

        sort($digits);
        for ($i = 1; $i <= $n; $i++) {
            if ($digits[$i - 1] !== $i) {
                throw new \InvalidArgumentException("Orden debe ser permutación 1..$n");
            }
        }

        $perm = [];
        foreach (str_split($orden) as $d) {
            $perm[] = $d - 1;            // 0-based
        }
        return $perm;
    }

    /**
     * $inverse = true aplica la permutación inversa (descifrado)
     */
    private function procesar(string $txt, array $perm, bool $inverse): string
    {
        // Normalizar: quitar todo salvo A-Z, y pasar a mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );
        $n = count($perm);

        /* invertir permutación si desciframos */
        if ($inverse) {
            $inv = array_fill(0, $n, 0);
            foreach ($perm as $src => $dst) {
                $inv[$dst] = $src;
            }
            $perm = $inv;
        }

        /* relleno hasta múltiplo de n */
        $len = mb_strlen($clean, 'UTF-8');
        $pad = ($n - ($len % $n)) % $n;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        /* permutar cada bloque */
        $out = '';
        for ($i = 0; $i < mb_strlen($clean, 'UTF-8'); $i += $n) {
            $block = $this->mbSubstr($clean, $i, $n);
            $tmp   = $this->mbStrSplit($block);

            $permBlock = array_fill(0, $n, '');
            foreach ($perm as $src => $dst) {
                $permBlock[$dst] = $tmp[$src];
            }
            $out .= implode('', $permBlock);
        }
        return $out;
    }

    /* ---------- util multibyte ---------- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function mbSubstr(string $s, int $start, int $length): string
    {
        return mb_substr($s, $start, $length, 'UTF-8');
    }
}
