<?php
namespace App\Cifrados\Sustitucion;

/**
 * Polialfabética (Vigenère clásico).
 */
final class Polialfabetica
{
    private const ABC='ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function cifrar(string $txt,string $clave):string
    { $this->validar($clave); return $this->procesar($txt,$clave,+1); }

    public function descifrar(string $txt,string $clave):string
    { $this->validar($clave); return $this->procesar($txt,$clave,-1); }

    /* ---------- helpers ---------- */
    private function validar(string $k):void
    {
        if(!preg_match('/^[A-Za-z]+$/',$k))
            throw new \InvalidArgumentException('Clave solo letras A-Z');
    }

    private function procesar(string $txt,string $clave,int $signo):string
    {
        $abc=self::ABC;
        $msg=strtoupper(preg_replace('/[^A-Z]/','',$txt));
        $key=strtoupper($clave); $klen=strlen($key); $out='';

        for($i=0,$n=strlen($msg);$i<$n;$i++){
            $m=strpos($abc,$msg[$i]);
            $k=strpos($abc,$key[$i%$klen]);
            $c=($m+$signo*$k+26)%26;
            $out.=$abc[$c];
        }
        return $out;
    }
}
