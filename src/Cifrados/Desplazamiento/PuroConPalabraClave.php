<?php
namespace App\Cifrados\Desplazamiento;

/**
 * “Puro con palabra clave” (Vigenère) para alfabeto español (A-Z + Ñ).
 *
 *  Cifrado   : Cᵢ = (Mᵢ + Kᵢ) mod 27
 *  Descifrado: Mᵢ = (Cᵢ − Kᵢ) mod 27
 */
final class PuroConPalabraClave
{
    /** Alfabeto de 27 símbolos — Ñ después de N */
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    /* ---------- API ---------- */
    public function cifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, +1);
    }

    public function descifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, -1);
    }

    /* ---------- núcleo ---------- */
    private function procesar(string $txt, string $clave, int $signo): string
    {
        // Normalizar texto y clave
        $abcArr = $this->mbStrSplit(self::ABC);      // array de 27 letras
        $abcMap = array_flip($abcArr);               // letra → índice

        $msgArr = $this->mbStrSplit(
            mb_strtoupper(
                preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
                'UTF-8'
            )
        );

        $keyArr = $this->mbStrSplit(mb_strtoupper($clave, 'UTF-8'));
        $kLen   = count($keyArr);

        $out = '';
        foreach ($msgArr as $i => $ch) {
            $m = $abcMap[$ch]              ?? 0;
            $k = $abcMap[$keyArr[$i % $kLen]] ?? 0;
            $c = ($m + $signo * $k + 27) % 27;
            $out .= $abcArr[$c];
        }
        return $out;
    }

    /* ---------- validación ---------- */
    private function validar(string $clave): void
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $clave)) {
            throw new \InvalidArgumentException(
                'La clave solo puede contener letras A-Z y Ñ'
            );
        }
    }

    /* ---------- helpers multibyte ---------- */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
