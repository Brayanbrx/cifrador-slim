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
    /**
     * Cifra el texto usando la matriz.
     *
     * @param string $txt    Texto a cifrar.
     * @param string $matStr Cadena de matriz (ej. "1,2,3;4,5,6;7,8,9").
     * @return string Texto cifrado.
     */
    public function cifrar(string $txt, string $matStr): string
    {
        [$mat, $n] = $this->parseMatrix($matStr);
        return $this->enc($txt, $mat, $n);
    }

    /**
     * Descifra el texto usando la matriz.
     *
     * @param string $txt    Texto a descifrar.
     * @param string $matStr Cadena de matriz (ej. "1,2,3;4,5,6;7,8,9").
     * @return string Texto descifrado.
     */
    public function descifrar(string $txt, string $matStr): string
    {
        [$mat, $n] = $this->parseMatrix($matStr);
        $inv       = $this->inverseMatrix($mat, $n);
        $out       = $this->enc($txt, $inv, $n);
        return rtrim($out, self::PAD);                 // quitar relleno X
    }

    /* ---------- núcleo ---------- */
    /**
     * Cifra o descifra el texto usando la matriz.
     *
     * @param string $txt Texto a cifrar o descifrar.
     * @param array  $mat Matriz de cifrado (n × n).
     * @param int    $n   Tamaño de la matriz (n × n).
     * @return string Texto cifrado o descifrado.
     */
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
    /**
     * Valida y parsea la cadena de matriz.
     *
     * @param string $s Cadena de matriz (ej. "1,2,3;4,5,6;7,8,9").
     * @return array Matriz y tamaño de la matriz.
     * @throws \InvalidArgumentException Si la matriz no es válida.
     */
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
    /**
     * Calcula el determinante de una matriz cuadrada.
     *
     * @param array $m Matriz cuadrada.
     * @param int   $n Tamaño de la matriz (n × n).
     * @return int Determinante de la matriz.
     */
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

    /**
     * Calcula el menor de una matriz eliminando la fila y columna especificadas.
     *
     * @param array $m Matriz original.
     * @param int   $row Fila a eliminar.
     * @param int   $col Columna a eliminar.
     * @param int   $n Tamaño de la matriz (n × n).
     * @return array Matriz menor resultante.
     */
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
    /**
     * Calcula la matriz inversa de una matriz cuadrada.
     *
     * @param array $m Matriz a invertir.
     * @param int   $n Tamaño de la matriz (n × n).
     * @return array Matriz inversa.
     * @throws \InvalidArgumentException Si el determinante no es invertible.
     */
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
    /**
     * Calcula el inverso multiplicativo de un número módulo m.
     *
     * @param int $a Número a invertir.
     * @param int $m Módulo.
     * @return int Inverso multiplicativo de a módulo m.
     * @throws \InvalidArgumentException Si el determinante no es invertible.
     */
    private function modInv(int $a, int $m): int
    {
        [$g, $x] = $this->egcd($a, $m);
        if ($g !== 1) {
            throw new \InvalidArgumentException('Determinante no invertible mod '.$m);
        }
        return ($x % $m + $m) % $m;
    }

    /* ---------- extended gcd ---------- */
    /**
     * Calcula el máximo común divisor (gcd) y los coeficientes de Bézout.
     *
     * @param int $a Primer número.
     * @param int $b Segundo número.
     * @return array Array con el gcd y los coeficientes de Bézout.
     */
    private function egcd(int $a, int $b): array
    {
        if ($b === 0) return [$a, 1, 0];
        [$g, $x1, $y1] = $this->egcd($b, $a % $b);
        return [$g, $y1, $x1 - intdiv($a, $b) * $y1];
    }

    /* ---------- gcd util ---------- */
    /**
     * Calcula el máximo común divisor (gcd) de dos números enteros.
     *
     * @param int $a Primer número.
     * @param int $b Segundo número.
     * @return int Máximo común divisor.
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? abs($a) : $this->gcd($b, $a % $b);
    }

    /* ---------- multibyte helper ---------- */
    /**
     * Convierte una cadena a un array de caracteres multibyte.
     *
     * @param string $s Cadena a convertir.
     * @return array Array de caracteres.
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
