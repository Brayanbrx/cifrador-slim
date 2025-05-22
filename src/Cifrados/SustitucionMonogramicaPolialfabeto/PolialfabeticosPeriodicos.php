<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Polialfabética Periódica con clave NUMÉRICA (cada dígito = desplazamiento),
 * Alfabeto de 27 letras; módulo 27.
 *
 *  Ej. clave 3142  →  desplaza 3,1,4,2 y repite.
 */
final class PolialfabeticosPeriodicos
{
    /** Alfabeto español A-Z + Ñ */
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    /* ---------- API ---------- */
    public function cifrar(string $txt, string $numKey): string
    {
        $seq = $this->parseKey($numKey);
        return $this->process($txt, $seq, +1);
    }

    public function descifrar(string $txt, string $numKey): string
    {
        $seq = $this->parseKey($numKey);
        return $this->process($txt, $seq, -1);
    }

    /* ---------- helpers ---------- */
    private function parseKey(string $key): array
    {
        if (!preg_match('/^[1-9]+$/', $key)) {
            throw new \InvalidArgumentException('Clave numérica: solo dígitos 1-9, sin ceros');
        }
        return array_map('intval', str_split($key));   // ej. "3142" → [3,1,4,2]
    }

    private function process(string $txt, array $seq, int $sign): string
    {
        $abcArr = $this->mbStrSplit(self::ABC);
        $abcMap = array_flip($abcArr);                 // letra → índice

        $cleanArr = $this->mbStrSplit(
            mb_strtoupper(
                preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
                'UTF-8'
            )
        );

        $lenSeq = count($seq);
        $out = '';

        foreach ($cleanArr as $i => $ch) {
            $m = $abcMap[$ch]        ?? 0;
            $k = $seq[$i % $lenSeq];                // desplazamiento numérico
            $c = ($m + $sign * $k + 27) % 27;       // módulo 27
            $out .= $abcArr[$c];
        }
        return $out;
    }

    /* --- dividir string UTF-8 en array de caracteres --- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
