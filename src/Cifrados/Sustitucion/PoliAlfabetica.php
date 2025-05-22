<?php
namespace App\Cifrados\Sustitucion;

/**
 * Polialfabética (Vigenère) con alfabeto español A-Z.
 */
final class Polialfabetica
{
    /** Alfabeto de 27 símbolos*/
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    /* ---------------- API pública ---------------- */

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

    /* ---------------- helpers ---------------- */

    private function validar(string $k): void
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $k)) {
            throw new \InvalidArgumentException('La clave solo puede contener letras A-Z y Ñ');
        }
    }

    private function procesar(string $txt, string $clave, int $signo): string
    {
        $abcArr = $this->mbStrSplit(self::ABC);              // array de 27 letras
        $abcMap = array_flip($abcArr);                       // letra → índice

        $msgArr = $this->mbStrSplit(
            mb_strtoupper(
                preg_replace('/[^A-Za-zÑñ]/u', '', $txt),
                'UTF-8'
            )
        );

        $keyArr = $this->mbStrSplit(mb_strtoupper($clave, 'UTF-8'));
        $klen   = count($keyArr);

        $out = '';
        foreach ($msgArr as $i => $ch) {
            $m = $abcMap[$ch]        ?? 0;
            $k = $abcMap[$keyArr[$i % $klen]] ?? 0;
            $c = ($m + $signo * $k + 27) % 27;
            $out .= $abcArr[$c];
        }
        return $out;
    }

    /** divide cadena UTF-8 en array de caracteres */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
