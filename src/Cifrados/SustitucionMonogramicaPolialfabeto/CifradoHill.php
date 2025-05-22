<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Cifrado Hill genérico n × n (mod 27) para alfabeto español (A-Z + Ñ).
 * La matriz se pasa como "a,b,c;d,e,f;g,h,i".
 *  – Todas las operaciones usan módulo 27.
 *  – Se eliminan los rellenos X al descifrar.
 */
final class CifradoHill
{
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ'; // 27 símbolos
    private const MOD = 27;                            // módulo aritmético
    private const PAD = 'X';

    /* ---------- API ---------- */
    public function cifrar(string $txt, string $matStr): string
    {
        [$mat, $n] = $this->parseMatrix($matStr);
        return $this->enc($txt, $mat, $n);
    }

    public function descifrar(string $txt, string $matStr): string
    {
        [$mat, $n] = $this->parseMatrix($matStr);
        $inv       = $this->inverseMatrix($mat, $n);
        $out       = $this->enc($txt, $inv, $n);
        return rtrim($out, self::PAD);                 // quitar relleno X
    }

    /* ---------- núcleo ---------- */
    private function enc(string $txt, array $mat, int $n): string
    {
        // normalizar mensaje
        $msgArr = $this->mbStrSplit(
            mb_strtoupper(
                preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
                'UTF-8'
            )
        );

        // padding
        $pad = ($n - (count($msgArr) % $n)) % $n;
        if ($pad) $msgArr = array_merge($msgArr, array_fill(0, $pad, self::PAD));

        // mapa letra → índice y viceversa
        $abcArr = $this->mbStrSplit(self::ABC);
        $abcMap = array_flip($abcArr);

        $out = '';
        for ($i = 0; $i < count($msgArr); $i += $n) {
            // vector columna
            $vec = [];
            for ($k = 0; $k < $n; $k++) {
                $vec[] = $abcMap[$msgArr[$i + $k]];
            }

            // multiplicación matriz × vector
            for ($row = 0; $row < $n; $row++) {
                $sum = 0;
                for ($col = 0; $col < $n; $col++) {
                    $sum += $mat[$row][$col] * $vec[$col];
                }
                $out .= $abcArr[($sum % self::MOD + self::MOD) % self::MOD];
            }
        }
        return $out;
    }

    /* ---------- parsear y validar matriz ---------- */
    private function parseMatrix(string $s): array
    {
        if (trim($s) === '') {
            throw new \InvalidArgumentException('Debe indicar matriz');
        }

        $rows = array_map('trim', explode(';', $s));
        $mat  = [];
        foreach ($rows as $r) {
            $nums = array_map('intval', explode(',', $r));
            $mat[] = $nums;
        }

        $n = count($mat);
        foreach ($mat as $row) {
            if (count($row) !== $n) {
                throw new \InvalidArgumentException('Matriz no cuadrada');
            }
        }

        // determinante coprimo con 27 (no múltiplo de 3)
        if ($this->gcd($this->det($mat, $n), self::MOD) !== 1) {
            throw new \InvalidArgumentException('Determinante no coprimo con 27; matriz no invertible');
        }
        return [$mat, $n];
    }

    /* ---------- determinante recursivo mod 27 (n ≤ 3 práctico) ---------- */
    private function det(array $m, int $n): int
    {
        if ($n === 1) return $m[0][0] % self::MOD;
        if ($n === 2) return ($m[0][0] * $m[1][1] - $m[0][1] * $m[1][0]) % self::MOD;

        $det = 0;
        for ($c = 0; $c < $n; $c++) {
            $minor = $this->minor($m, 0, $c, $n);
            $det  += (($c % 2 ? -1 : 1) * $m[0][$c] * $this->det($minor, $n - 1));
        }
        return ($det % self::MOD + self::MOD) % self::MOD;
    }

    private function minor(array $m, int $row, int $col, int $n): array
    {
        $minor = [];
        for ($i = 0; $i < $n; $i++) {
            if ($i === $row) continue;
            $line = [];
            for ($j = 0; $j < $n; $j++) {
                if ($j === $col) continue;
                $line[] = $m[$i][$j];
            }
            $minor[] = $line;
        }
        return $minor;
    }

    /* ---------- inversa mod 27 ---------- */
    private function inverseMatrix(array $m, int $n): array
    {
        $det    = $this->det($m, $n);
        $detInv = $this->modInv($det, self::MOD);

        $adj = [];
        for ($r = 0; $r < $n; $r++) {
            $row = [];
            for ($c = 0; $c < $n; $c++) {
                $minor = $this->minor($m, $r, $c, $n);
                $cof   = $this->det($minor, $n - 1);
                if (($r + $c) % 2 === 1) $cof = -$cof;
                $row[] = ($detInv * $cof) % self::MOD;
            }
            $adj[] = $row;
        }

        // trasponer adjugate y normalizar mod 27
        $inv = [];
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                $inv[$r][$c] = (($adj[$c][$r] % self::MOD) + self::MOD) % self::MOD;
            }
        }
        return $inv;
    }

    /* ---------- arithmetic util ---------- */
    private function modInv(int $a, int $m): int
    {
        [$g, $x] = $this->egcd($a, $m);
        if ($g !== 1) {
            throw new \InvalidArgumentException('Determinante no invertible mod '.$m);
        }
        return ($x % $m + $m) % $m;
    }

    private function egcd(int $a, int $b): array
    {
        if ($b === 0) return [$a, 1, 0];
        [$g, $x1, $y1] = $this->egcd($b, $a % $b);
        return [$g, $y1, $x1 - intdiv($a, $b) * $y1];
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? abs($a) : $this->gcd($b, $a % $b);
    }

    /* ---------- multibyte helper ---------- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
