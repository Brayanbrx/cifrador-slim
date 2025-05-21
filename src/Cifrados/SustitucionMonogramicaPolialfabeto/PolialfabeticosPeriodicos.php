<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Polialfabética Periódica con clave NUMÉRICA (cada dígito = desplazamiento).
 *  Ej. clave 3142  =>  desplaza 3,1,4,2 y repite.
 */
final class PolialfabeticosPeriodicos
{
    private const ABC='ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function cifrar(string $txt,string $numKey):string
    { $seq=$this->parseKey($numKey); return $this->process($txt,$seq,+1); }

    public function descifrar(string $txt,string $numKey):string
    { $seq=$this->parseKey($numKey); return $this->process($txt,$seq,-1); }

    /* ------ helpers ------ */
    private function parseKey(string $key):array
    {
        if(!preg_match('/^[0-9]+$/',$key) || strpos($key,'0')!==false)
            throw new \InvalidArgumentException('Clave numérica debe ser dígitos 1-9');
        return array_map('intval',str_split($key)); // ej [3,1,4,2]
    }

    private function process(string $txt,array $seq,int $sign):string
    {
        $abc=self::ABC; $clean=strtoupper(preg_replace('/[^A-Z]/','',$txt));
        $out=''; $lenSeq=count($seq);
        foreach(str_split($clean) as $i=>$ch){
            $m=strpos($abc,$ch);
            $k=$seq[$i%$lenSeq];
            $c=($m+$sign*$k+26)%26;
            $out.=$abc[$c];
        }
        return $out;
    }
}
