<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por Columnas (permuta las columnas según clave numérica),
 * (alfabeto español de 27 símbolos).
 */
final class Columnas
{
    private const PAD = 'X'; // Caracter de relleno

    /* ---------- API ---------- */
    /**
     * Cifra el texto usando la clave de transposición.
     *
     * @param string $txt   Texto a cifrar.
     * @param string $orden Clave de transposición (permutación de 1..n).
     * @return string Texto cifrado.
     */
    public function cifrar(string $txt, string $orden): string
    {
        $perm = $this->parseKey($orden);      // valida que orden sea correcto 1..n y devuelve array
        $n    = count($perm);                 // número de columnas   

        // Normalizar: A-Z + Ñ, a mayúsculas
        $clean = mb_strtoupper(preg_replace('/[^A-Za-zÑñ]/u', '', $txt), 'UTF-8');

        // Relleno para múltiplo de n
        $len = mb_strlen($clean, 'UTF-8');                  // longitud del texto limpio
        $pad = ($n - ($len % $n)) % $n;                     // relleno necesario
        if ($pad) $clean .= str_repeat(self::PAD, $pad);    // relleno con X

        // Dividir en filas de n caracteres
        $rows = [];
        for ($i = 0; $i < mb_strlen($clean, 'UTF-8'); $i += $n) {
            $rows[] = $this->mbSubstr($clean, $i, $n);
        }

        // Leer columnas en orden ascendente de la clave
        $out = '';
        for ($rank = 1; $rank <= $n; $rank++) {
            $col = array_search($rank, $perm, true);    // índice de columna
            foreach ($rows as $row) {
                $out .= $this->mbSubstr($row, $col, 1);
            }
        }
        return $out;
    }

    /* ---------- DESCIFRAR ---------- */
    /**
     * Descifra el texto usando la clave de transposición.
     *
     * @param string $txt   Texto a descifrar.
     * @param string $orden Clave de transposición (permutación de 1..n).
     * @return string Texto descifrado.
     */
    public function descifrar(string $txt, string $orden): string
    {
        $perm = $this->parseKey($orden);
        $n    = count($perm);
        $len  = mb_strlen($txt, 'UTF-8');
        $rows = intdiv($len, $n);               // número de filas

        /* cortar texto en columnas según orden 1..n */
        $cols = array_fill(0, $n, []);
        $pos  = 0;
        for ($rank = 1; $rank <= $n; $rank++) {
            $colIdx = array_search($rank, $perm, true);
            $segment = $this->mbSubstr($txt, $pos, $rows);
            $cols[$colIdx] = $this->mbStrSplit($segment);
            $pos += $rows;
        }

        /* reconstruir leyendo filas naturales */
        $out = '';
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $n; $c++) {
                $out .= $cols[$c][$r];
            }
        }
        return rtrim($out, self::PAD);          // quita relleno X final
    }

    /* ---------- validación de clave ---------- */
    /**
     * Valida la clave de transposición (permutación de 1..n).
     *
     * @param string $orden Clave de transposición.
     * @return array Clave como array de enteros.
     * @throws \InvalidArgumentException Si la clave no es válida.
     */
    private function parseKey(string $orden): array
    {
        if (!preg_match('/^[1-9]+$/', $orden)) {
            throw new \InvalidArgumentException('Clave numérica inválida (solo dígitos 1-9).');
        }
        $digits = array_map('intval', str_split($orden));
        $n      = count($digits);

        sort($digits);
        for ($i = 1; $i <= $n; $i++) {
            if ($digits[$i - 1] !== $i) {
                throw new \InvalidArgumentException("Clave debe ser permutación 1..$n");
            }
        }
        return array_map('intval', str_split($orden));   // [col => rank]
    }

    /* ---------- helpers multibyte ---------- */
    /**
     * Divide un string UTF-8 en un array de caracteres.
     *
     * @param string $s String a dividir.
     * @return array Array de caracteres.
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Extrae una subcadena de un string UTF-8.
     *
     * @param string $s String original.
     * @param int $start Posición inicial.
     * @param int $length Longitud de la subcadena.
     * @return string Subcadena extraída.
     */
    private function mbSubstr(string $s, int $start, int $length): string
    {
        return mb_substr($s, $start, $length, 'UTF-8');
    }
}
