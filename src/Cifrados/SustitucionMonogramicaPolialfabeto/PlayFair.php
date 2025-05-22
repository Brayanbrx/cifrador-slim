<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * PlayFair 5×5 para el alfabeto español:
 *   • I y J se fusionan.
 *   • Ñ se conserva.
 *   • K se omite para cuadrar 25 casillas.
 *   Relleno X cuando hay duplicado o longitud impar.
 */
final class PlayFair
{
    private const ABC  = 'ABCDEFGHILMNÑOPQRSTUVWXYZ'; // sin J (↔I) y sin K
    private const PAD  = 'X';

    private array $mat  = [];   // matriz 5×5
    private array $pos  = [];   // letra → [row,col]

    /* ---------- API ---------- */
    /**
     * Cifra el texto usando la clave.
     *
     * @param string $txt   Texto a cifrar.
     * @param string $clave Clave para construir la matriz.
     * @return string Texto cifrado.
     */
    public function cifrar(string $txt, string $clave): string
    {
        $this->buildMatrix($clave);
        return $this->process($txt, +1);
    }

    /**
     * Descifra el texto usando la clave.
     *
     * @param string $txt   Texto a descifrar.
     * @param string $clave Clave para construir la matriz.
     * @return string Texto descifrado.
     */
    public function descifrar(string $txt, string $clave): string
    {
        $this->buildMatrix($clave);
        $res = $this->process($txt, -1);
        return rtrim($res, self::PAD);              // quita X de relleno final
    }

    /* ---------- construir la matriz ---------- */
    /**
     * Construye la matriz 5×5 a partir de la clave.
     *
     * @param string $key Clave para construir la matriz.
     * @throws \InvalidArgumentException Si la clave no es válida.
     */
    private function buildMatrix(string $key): void
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $key)) {
            throw new \InvalidArgumentException('Clave solo puede contener letras A-Z y Ñ');
        }

        // normalizar clave: mayúsculas, I/J fusionadas, quitar K
        $key = mb_strtoupper($key, 'UTF-8');
        $key = str_replace(['J', 'K'], ['I', ''], $key);

        // cadena semilla = clave sin duplicados + alfabeto base
        $seed = $this->dedup($key) . self::ABC;
        $seed = $this->dedup($seed);                // asegurar sin repes

        // llenar matriz y mapa de posiciones
        $this->mat = $this->pos = [];
        $chars = $this->mbStrSplit($seed);
        for ($i = 0; $i < 25; $i++) {
            $r = intdiv($i, 5);
            $c =  $i % 5;
            $ch = $chars[$i];
            $this->mat[$r][$c] = $ch;
            $this->pos[$ch]    = [$r, $c];
        }
    }

    /**
     * Elimina duplicados de la cadena.
     *
     * @param string $s Cadena de entrada.
     * @return string Cadena sin duplicados.
     */
    private function dedup(string $s): string
    {
        $out = '';
        foreach ($this->mbStrSplit($s) as $ch) {
            if (!str_contains($out, $ch)) $out .= $ch;
        }
        return $out;
    }

    /* ---------- cifrar / descifrar ---------- */
    /**
     * Procesa el texto cifrado o descifrado.
     *
     * @param string $txt Texto a procesar.
     * @param int    $dir 1: cifrar, -1: descifrar.
     * @return string Texto procesado.
     */
    private function process(string $txt, int $dir): string
    {
        // normalizar texto claro/cifrado
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );
        $clean = str_replace(['J', 'K'], ['I', ''], $clean);

        // formar dígrafos
        $pairs = [];
        $chars = $this->mbStrSplit($clean);
        for ($i = 0, $n = count($chars); $i < $n; ) {
            $a = $chars[$i];
            $b = ($i + 1 < $n) ? $chars[$i + 1] : self::PAD;

            if ($a === $b) {        // par duplicado → inserta X
                $b = self::PAD;
                $i += 1;
            } else {
                $i += 2;
            }
            $pairs[] = [$a, $b];
        }
        if (count(end($pairs)) === 1) {              // longitud impar total
            $pairs[count($pairs) - 1][] = self::PAD;
        }

        // procesar cada par
        $out = '';
        foreach ($pairs as [$a, $b]) {
            [$ra, $ca] = $this->pos[$a];
            [$rb, $cb] = $this->pos[$b];

            if ($ra === $rb) {                       // misma fila
                $ca = ($ca + $dir + 5) % 5;
                $cb = ($cb + $dir + 5) % 5;
            } elseif ($ca === $cb) {                 // misma columna
                $ra = ($ra + $dir + 5) % 5;
                $rb = ($rb + $dir + 5) % 5;
            } else {                                // rectángulo
                [$ca, $cb] = [$cb, $ca];
            }
            $out .= $this->mat[$ra][$ca] . $this->mat[$rb][$cb];
        }

        return $out;
    }

    /* ---------- util multibyte ---------- */
    /**
     * Extrae una subcadena de longitud $len desde la posición $off.
     *
     * @param string $s    Cadena de origen.
     * @param int    $off  Offset inicial.
     * @param int    $len  Longitud de la subcadena.
     * @return string Subcadena extraída.
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
