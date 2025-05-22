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
    /**
     * Cifra un texto usando la clave.
     * @param string $txt   Texto a cifrar (solo letras A-Z y Ñ)
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string       Texto cifrado
     */
    public function cifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, +1);
    }

    /**
     * Descifra un texto usando la clave.
     * @param string $txt   Texto a descifrar (solo letras A-Z y Ñ)
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string       Texto descifrado
     */
    public function descifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, -1);
    }

    /* ---------------- helpers ---------------- */
    /**
     * Valida la clave: solo letras A-Z y Ñ.
     * @param string $k Clave a validar
     * @throws \InvalidArgumentException si la clave no es válida
     */
    private function validar(string $k): void
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $k)) {
            throw new \InvalidArgumentException('La clave solo puede contener letras A-Z y Ñ');
        }
    }

    /**
     * Procesa el texto usando la clave y el signo (+1 o -1).
     * @param string $txt   Texto a procesar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @param int    $signo +1 para cifrar, -1 para descifrar
     * @return string       Texto procesado
     */
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

    /**
     * Divide un string UTF-8 en un array de caracteres.
     * @param string $s String a dividir
     * @return array     Array de caracteres
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
