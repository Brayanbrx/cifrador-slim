<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Doble transposición (columnas πc → filas πf) por bloques m × n.
 *  – Relleno con X y eliminación al descifrar.
 */
final class AnagramacionFilasColumnas
{
    private const PAD = 'X';

    /* --------------- API --------------- */
    public function cifrar(string $txt, array $k): string
    {
        [$pc, $pf] = $this->parseKeys($k);          // columnas, filas
        return $this->processBlocks($txt, $pc, $pf, true);
    }

    public function descifrar(string $txt, array $k): string
    {
        [$pc, $pf] = $this->parseKeys($k);
        $res       = $this->processBlocks($txt, $pc, $pf, false);
        return rtrim($res, self::PAD);              // quita relleno X final
    }

    /* -------- procesa cada bloque m×n -------- */
    private function processBlocks(string $txt, array $pc, array $pf, bool $enc): string
    {
        $n   = count($pc);                   // columnas
        $m   = count($pf);                   // filas
        $blk = $m * $n;                      // tamaño de bloque

        // normalizar texto: quitar todo menos letras y Ñ, mayúsculas
        $clean = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
            'UTF-8'
        );

        // padding para el último bloque
        $len = mb_strlen($clean, 'UTF-8');
        if ($enc) {
            $pad = ($blk - ($len % $blk)) % $blk;
            if ($pad) $clean .= str_repeat(self::PAD, $pad);
        } elseif ($len % $blk !== 0) {
            throw new \InvalidArgumentException('Longitud de texto cifrado no coincide con bloques m×n');
        }

        // procesar bloque a bloque
        $out = '';
        for ($off = 0; $off < mb_strlen($clean, 'UTF-8'); $off += $blk) {
            $chunk = $this->mbSubstr($clean, $off, $blk);
            $out  .= $enc
                ? $this->encChunk($chunk, $pc, $pf, $n, $m)
                : $this->decChunk($chunk, $pc, $pf, $n, $m);
        }
        return $out;
    }

    /* -------------- cifrar bloque -------------- */
    private function encChunk(string $chunk, array $pc, array $pf, int $n, int $m): string
    {
        $chars = $this->mbStrSplit($chunk);
        $rows  = array_chunk($chars, $n);            // m filas × n columnas

        /* columnas: destino → origen */
        $colMap = $this->orderMap($pc);
        foreach ($rows as &$row) {
            $tmp = $row;
            foreach ($colMap as $dst => $src) {
                $row[$dst] = $tmp[$src];
            }
        }

        /* filas */
        $rowMap = $this->orderMap($pf);
        $tmpRows = $rows;
        foreach ($rowMap as $dst => $src) {
            $rows[$dst] = $tmpRows[$src];
        }

        return implode('', array_map(fn($r) => implode('', $r), $rows));
    }

    /* ------------- descifrar bloque ------------- */
    private function decChunk(string $chunk, array $pc, array $pf, int $n, int $m): string
    {
        $chars = $this->mbStrSplit($chunk);
        $rows  = array_chunk($chars, $n);            // matriz m×n

        /* inversa filas */
        $rowMap = $this->orderMap($pf);
        $invRows = $rows;
        foreach ($rowMap as $dst => $src) {
            $invRows[$src] = $rows[$dst];
        }

        /* inversa columnas */
        $colMap = $this->orderMap($pc);
        foreach ($invRows as &$row) {
            $tmp = $row;
            foreach ($colMap as $dst => $src) {
                $row[$src] = $tmp[$dst];
            }
        }

        return implode('', array_map(fn($r) => implode('', $r), $invRows));
    }

    /* ----------- claves y mapas ----------- */
    private function parseKeys(array $k): array
    {
        $pc = $this->parseKey($k['col'] ?? '');
        $pf = $this->parseKey($k['fil'] ?? '');
        return [$pc, $pf];
    }

    private function parseKey(string $k): array
    {
        if (!preg_match('/^[1-9]+$/', $k)) {
            throw new \InvalidArgumentException('Clave numérica 1-9 (sin ceros).');
        }
        $arr = array_map('intval', str_split($k));
        $n   = count($arr);

        $sorted = $arr;
        sort($sorted);
        for ($i = 1; $i <= $n; $i++) {
            if ($sorted[$i - 1] !== $i) {
                throw new \InvalidArgumentException("Clave debe ser permutación 1..$n");
            }
        }
        return $arr;                                 // [3,1,4,2]...
    }

    /** destino → origen (rank ascendente) */
    private function orderMap(array $perm): array
    {
        $map = [];
        foreach ($perm as $src => $rank) {
            $map[$rank - 1] = $src;
        }
        ksort($map);
        return $map;                                 // [0 => 1, 1 => 3, ...]
    }

    /* -------- helpers multibyte -------- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function mbSubstr(string $s, int $start, int $len): string
    {
        return mb_substr($s, $start, $len, 'UTF-8');
    }
}
