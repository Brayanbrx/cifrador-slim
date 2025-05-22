<?php
namespace App\Cifrados\Transposicion;

/**
 * Cifrado Rail-Fence (Zig-Zag).
 *  – r ≥ 2 rieles
 *  – relleno X en caso de ser necesario (solo en cifrado)
 */
final class ZigZag
{
    private const PAD = 'X';

    /* ------------- API ------------- */
    /** Cifra el texto usando la clave de transposición.
     * @param string $txt Texto a cifrar.
     * @param int    $r   Número de rieles (≥ 2).
     * @return string Texto cifrado.
     */
    public function cifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Rieles debe ser ≥ 2');
        }

        // Normalizar: quitar todo lo que no sea A-Z o Ñ, y pasar a mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );

        // Dividir en rieles (arrays) usando recorrido zig-zag
        $rails = array_fill(0, $r, []);
        $row = 0; $dir = 1;
        foreach ($this->mbStrSplit($clean) as $ch) {
            $rails[$row][] = $ch;
            $row += $dir;
            if ($row === $r - 1 || $row === 0) {
                $dir *= -1;               // cambiar dirección
            }
        }

        // Concatenar rail-por-rail
        $out = '';
        foreach ($rails as $rail) {
            $out .= implode('', $rail);
        }
        return $out;
    }

    /** Descifra el texto usando la clave de transposición.
     * @param string $txt Texto a descifrar.
     * @param int    $r   Número de rieles (≥ 2).
     * @return string Texto descifrado.
     */
    public function descifrar(string $txt, int $r): string
    {
        if ($r < 2) {
            throw new \InvalidArgumentException('Rieles debe ser ≥ 2');
        }

        $len   = mb_strlen($txt, 'UTF-8');
        $cycle = 2 * ($r - 1);

        // 1) ¿cuántos caracteres caen en cada rail?
        $railLen = array_fill(0, $r, 0);
        for ($i = 0; $i < $len; $i++) {
            $row = $this->rowAt($i, $r);
            $railLen[$row]++;
        }

        // 2) Cortar la cadena en trozos multibyte para cada rail
        $rails = [];
        $pos   = 0;
        for ($i = 0; $i < $r; $i++) {
            $rails[$i] = $this->mbSubstr($txt, $pos, $railLen[$i]);
            $rails[$i] = $this->mbStrSplit($rails[$i]);   // array de chars
            $pos      += $railLen[$i];
        }

        // 3) Reconstruir recorriendo zig-zag
        $idx = array_fill(0, $r, 0);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $row = $this->rowAt($i, $r);
            $out .= $rails[$row][$idx[$row]++];
        }
        return $out;
    }

    /* ---------- helpers de cifrado ---------- */
    /** Devuelve el índice de la fila en la que cae el carácter i
     * @param int $i Índice del carácter (0-based).
     * @param int $r Número de rieles.
     * @return int Índice de la fila (0-based).
     */
    private function rowAt(int $i, int $r): int
    {
        $cycle = 2 * ($r - 1);
        $pos   = $i % $cycle;
        return ($pos < $r) ? $pos : $cycle - $pos;
    }

    /* ---------- helpers multibyte ---------- */
    /** Divide string UTF-8 en array de caracteres
     * @param string $s Cadena a dividir.
     * @return array Array de caracteres.
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** Extrae una subcadena de string UTF-8
     * @param string $s      Cadena a extraer.
     * @param int    $start  Posición inicial (0-based).
     * @param int    $length Longitud de la subcadena.
     * @return string Subcadena extraída.
     */
    private function mbSubstr(string $s, int $start, int $length): string
    {
        return mb_substr($s, $start, $length, 'UTF-8');
    }
}
