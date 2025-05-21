<?php
namespace App\Cifrados\Transposicion;

/**
 * Transposición por grupos con inversión interna.
 * Descifrar = mismo proceso.
 */
final class Grupos
{
    private const PAD = 'X';

    public function cifrar(string $txt, int $g): string   { return $this->procesar($txt, $g); }
    public function descifrar(string $txt, int $g): string{ return $this->procesar($txt, $g); }

    /* ------- lógica común ------- */
    private function procesar(string $txt, int $g): string
    {
        if ($g < 2) {
            throw new \InvalidArgumentException('Tamaño de grupo debe ser ≥ 2');
        }

        $clean = strtoupper(preg_replace('/[^A-Z]/', '', $txt));
        $pad   = ($g - (strlen($clean) % $g)) % $g;
        if ($pad) $clean .= str_repeat(self::PAD, $pad);

        $out = '';
        for ($i = 0; $i < strlen($clean); $i += $g) {
            $out .= strrev(substr($clean, $i, $g));
        }
        return $out;
    }
}
