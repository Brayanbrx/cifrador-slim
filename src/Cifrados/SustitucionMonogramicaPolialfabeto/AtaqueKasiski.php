<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Ataque de Kasiski: sugiere longitudes de clave para Vigenère (UTF-8).
 */
final class AtaqueKasiski
{
    /**
     * Analiza el texto cifrado y sugiere longitudes de clave.
     *
     * @param string $cipher Texto cifrado.
     * @return string Texto analizado con longitudes de clave sugeridas.
     */
    public function analizar(string $cipher): string
    {
        // 1. Normalizar: quitar todo salvo letras A-Z y Ñ, poner mayúsculas
        $txt = mb_strtoupper(
            preg_replace('/[^A-Za-zÑñ]/u', '', $cipher),
            'UTF-8'
        );

        $distancias = [];

        // 2. Buscar repeticiones de trigramas usando funciones multibyte
        $len = mb_strlen($txt, 'UTF-8');
        for ($i = 0; $i < $len - 2; $i++) {
            $sub = mb_substr($txt, $i, 3, 'UTF-8');
            $pos = mb_strpos($txt, $sub, $i + 3, 'UTF-8');
            while ($pos !== false) {
                $distancias[] = $pos - $i;               // distancia en caracteres
                $pos = mb_strpos($txt, $sub, $pos + 1, 'UTF-8');
            }
        }

        if (empty($distancias)) {
            return "No se hallaron repeticiones de trigramas — texto muy corto.";
        }

        // 3. Contar factores 2..20
        $factores = array_fill(2, 19, 0);
        foreach ($distancias as $d) {
            for ($f = 2; $f <= 20; $f++) {
                if ($d % $f === 0) $factores[$f]++;
            }
        }
        arsort($factores);

        // 4. Formar salida
        $salida  = "Distancias (en caracteres): " . implode(',', $distancias) . "\n\n";
        $salida .= "Factores más probables (2-20):\n";
        foreach ($factores as $f => $v) {
            $salida .= sprintf("  k = %-2d → %d ocurrencias\n", $f, $v);
        }
        return $salida;
    }
}
