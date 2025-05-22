<?php
namespace App\Cifrados\Desplazamiento;

/**
 * “Puro con palabra clave” (Vigenère) para alfabeto español (A-Z).
 *  Cifrado   : Cᵢ = (Mᵢ + Kᵢ) mod 27
 *  Descifrado: Mᵢ = (Cᵢ − Kᵢ) mod 27
 */
final class PuroConPalabraClave  //final class indica, que no se puede extender
{
    /** Alfabeto de 27 símbolos */
    private const ABC = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    /* ---------- API pública ---------- */
    /**
     * Cifra el texto usando la clave.
     * @param string $txt   Texto a cifrar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string Texto cifrado
     */
    public function cifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, +1); //suma desplazamiento
    }

    /**
     * Descifra el texto usando la clave.
     * @param string $txt   Texto a descifrar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @return string Texto descifrado
     */
    public function descifrar(string $txt, string $clave): string
    {
        $this->validar($clave);
        return $this->procesar($txt, $clave, -1); //resta desplazamiento
    }

    /* ---------- núcleo ---------- */
    /**
     * Procesa el texto usando la clave y el signo de desplazamiento.
     * @param string $txt   Texto a procesar
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @param int    $signo Desplazamiento (+1 o -1)
     * @return string Texto procesado
     */
    private function procesar(string $txt, string $clave, int $signo): string
    {
        // Normalizar texto y clave
        $abcArr = $this->mbStrSplit(self::ABC);      // divide cadena en array ['A', 'B', ...]
        $abcMap = array_flip($abcArr);               // letra → índice ['A' => 0, 'B' => 1, ...]

        // Eliminar caracteres no alfabéticos y convertir a mayúsculas
        $msgArr = $this->mbStrSplit(
            mb_strtoupper(  // convertir a mayúsculas
                preg_replace('/[^A-Za-zÑñ]/u', '', $txt), // eliminar no alfabéticos
                'UTF-8'  // codificación UTF-8
            )
        );

        // Convertir clave a mayúsculas y dividir en array
        $keyArr = $this->mbStrSplit(mb_strtoupper($clave, 'UTF-8'));
        $kLen   = count($keyArr); // longitud de la clave

        // Recorremos cada letra del mensaje
        $out = '';
        foreach ($msgArr as $i => $ch) {             // i es el índice del mensaje, ch es la letra, msgArr es el mensaje
            $m = $abcMap[$ch]              ?? 0;     // índice de la letra del mensaje
            $k = $abcMap[$keyArr[$i % $kLen]] ?? 0;  // índice de la letra de la clave
            $c = ($m + $signo * $k + 27) % 27;       // módulo 27
            $out .= $abcArr[$c];                     // convertimos índice a letra
        }
        return $out;
    }

    /* ---------- validación ---------- */
    /**
     * Valida la clave de cifrado.
     * @param string $clave Clave de cifrado (solo letras A-Z y Ñ)
     * @throws \InvalidArgumentException Si la clave no es válida
     */
    private function validar(string $clave): void
    {
        if (!preg_match('/^[A-Za-zÑñ]+$/u', $clave)) {
            throw new \InvalidArgumentException(
                'La clave solo puede contener letras A-Z y Ñ'
            );
        }
    }

    /* ---------- helpers multibyte ---------- */
    /**
     * Divide una cadena UTF-8 en un array de caracteres.
     * @param string $s Cadena a dividir
     * @return array Array de caracteres
     */
    private function mbStrSplit(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
