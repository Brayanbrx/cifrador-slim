<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Ataque de Kasiski: sugiere longitudes de clave para Vigenère.
 * Devuelve string con factores y frecuencias.
 */
final class AtaqueKasiski
{
    public function analizar(string $cipher): string
    {
        $txt = strtoupper(preg_replace('/[^A-Z]/', '', $cipher));
        $distancias = [];

        /* buscar repeticiones de trigramas */
        for ($i = 0; $i < strlen($txt) - 2; $i++) {
            $sub = substr($txt, $i, 3);
            $pos = strpos($txt, $sub, $i + 3);
            while ($pos !== false) {
                $distancias[] = $pos - $i;
                $pos = strpos($txt, $sub, $pos + 1);
            }
        }

        if (empty($distancias)) {
            return "No se hallaron repeticiones de tres letras — texto muy corto.";
        }

        /* factores 2..20 */
        $factores = array_fill(2, 19, 0);
        foreach ($distancias as $d) {
            for ($f = 2; $f <= 20; $f++) {
                if ($d % $f === 0) $factores[$f]++;
            }
        }

        arsort($factores);
        $salida = "Distancias: ".implode(',', $distancias)."\n\n";
        $salida .= "Factores más probables (2-20):\n";
        foreach ($factores as $f => $v) {
            $salida .= "  k = $f  →  $v ocurrencias\n";
        }
        return $salida;
    }
}
