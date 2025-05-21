<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Anagramación (doble transposición) Filas + Columnas.
 * Permuta columnas con πc y, después, filas con πf POR CADA bloque m×n.
 */
final class AnagramacionFilasColumnas
{
    private const PAD = 'X';

    /* ---------------- API ---------------- */
    public function cifrar(string $txt, array $k): string
    {
        [$pc, $pf] = $this->parseKeys($k);
        return $this->processBlocks($txt, $pc, $pf, true);
    }

    public function descifrar(string $txt, array $k): string
    {
        [$pc, $pf] = $this->parseKeys($k);
        return rtrim($this->processBlocks($txt, $pc, $pf, false), self::PAD);
    }

    /* -------- procesa cada bloque m×n -------- */
    private function processBlocks(string $txt, array $pc, array $pf, bool $encrypt): string
    {
        $n   = count($pc);
        $m   = count($pf);
        $blk = $m * $n;

        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));

        /* padding global para completar último bloque */
        if ($encrypt) {
            $pad = ($blk - (strlen($clean) % $blk)) % $blk;
            if ($pad) $clean .= str_repeat(self::PAD, $pad);
        }

        $out = '';
        for ($offset = 0; $offset < strlen($clean); $offset += $blk) {
            $chunk = substr($clean, $offset, $blk);
            $out  .= $encrypt
                ? $this->encChunk($chunk, $pc, $pf, $n, $m)
                : $this->decChunk($chunk, $pc, $pf, $n, $m);
        }
        return $out;
    }

    /* ---------------- cifrar bloque ---------------- */
    private function encChunk(string $chunk, array $pc, array $pf, int $n, int $m): string
    {
        $rows = array_chunk(str_split($chunk), $n);           // m filas × n col

        /* permutar columnas */
        $colMap = $this->orderMap($pc);                       // destino→origen
        foreach ($rows as &$row) {
            $tmp = $row;
            foreach ($colMap as $dst => $src) $row[$dst] = $tmp[$src];
        }

        /* permutar filas */
        $rowMap = $this->orderMap($pf);
        $tmpRows = $rows;
        foreach ($rowMap as $dst => $src) $rows[$dst] = $tmpRows[$src];

        /* salida fila-fila */
        return implode('', array_map(fn($r) => implode('', $r), $rows));
    }

    /* ---------------- descifrar bloque ---------------- */
    private function decChunk(string $chunk, array $pc, array $pf, int $n, int $m): string
    {
        /* reconstruir filas */
        $rows = array_chunk(str_split($chunk), $n);

        /* inversa filas */
        $rowMap = $this->orderMap($pf);
        $invRows = $rows;
        foreach ($rowMap as $dst => $src) $invRows[$src] = $rows[$dst];

        /* inversa columnas */
        $colMap = $this->orderMap($pc);
        foreach ($invRows as &$row) {
            $tmp = $row;
            foreach ($colMap as $dst => $src) $row[$src] = $tmp[$dst];
        }

        return implode('', array_map(fn($r) => implode('', $r), $invRows));
    }

    /* ----------- auxiliares ----------- */
    private function parseKeys(array $k): array
    {
        $pc = $this->parseKey($k['col'] ?? '');
        $pf = $this->parseKey($k['fil'] ?? '');
        return [$pc, $pf];
    }

    private function parseKey(string $k): array
    {
        if (!preg_match('/^[1-9]+$/', $k))
            throw new \InvalidArgumentException('Clave numérica 1-9 sin ceros');
        $arr = array_map('intval', str_split($k));
        $n   = count($arr);
        sort($arr);
        for ($i = 1; $i <= $n; $i++) {
            if ($arr[$i - 1] !== $i)
                throw new \InvalidArgumentException('Clave debe ser permutación 1..' . $n);
        }
        return array_map('intval', str_split($k));            // [3,1,4,2]…
    }

    /** genera mapa destino→origen según orden ascendente de la clave */
    private function orderMap(array $perm): array
    {
        $map = [];
        foreach ($perm as $src => $rank) $map[$rank - 1] = $src;
        ksort($map);
        return $map;
    }
}
