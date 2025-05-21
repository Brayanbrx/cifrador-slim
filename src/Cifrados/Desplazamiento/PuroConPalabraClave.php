<?php
namespace App\Cifrados\Desplazamiento;

/**
 * Implementa el cifrado “puro con palabra clave”.
 *  Cifrar :  C[i] = (M[i] + K[i]) mod 26
 *  Descifrar: M[i] = (C[i] − K[i]) mod 26
 */
final class PuroConPalabraClave
{
    private const ABC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

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

    /* ----------------- helpers ------------------ */

    private function procesar(string $txt, string $clave, int $signo): string
    {
        $abc  = self::ABC;
        $text = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $key  = strtoupper($clave);
        $out  = '';

        $kLen = strlen($key);
        for ($i=0, $n=strlen($text); $i<$n; $i++) {
            $m = strpos($abc, $text[$i]);          // valor mensaje
            $k = strpos($abc, $key[$i % $kLen]);   // valor clave
            $c = ($m + $signo*$k + 26) % 26;       // +26 evita negativos
            $out .= $abc[$c];
        }
        return $out;
    }

    private function validar(string $clave): void
    {
        if (!preg_match('/^[A-Za-z]+$/', $clave)) {
            throw new \InvalidArgumentException('La clave solo puede contener letras A-Z');
        }
    }
}
