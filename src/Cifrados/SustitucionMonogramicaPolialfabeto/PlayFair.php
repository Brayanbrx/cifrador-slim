<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Cifrado Playfair (5×5, I=J, relleno X).
 */
final class PlayFair
{
    private array $mat;     // matriz 5x5
    private array $pos;     // letra → [row,col]

    /* --- API --- */
    public function cifrar(string $txt,string $clave):string
    {
        $this->buildMatrix($clave);
        return $this->process($txt,+1);
    }
    public function descifrar(string $txt,string $clave):string
    {
        $this->buildMatrix($clave);
        return $this->process($txt,-1);
    }

    /* --- construir matriz 5×5 --- */
    private function buildMatrix(string $key):void
    {
        if(!preg_match('/^[A-Za-z]+$/',$key))
            throw new \InvalidArgumentException('Clave solo letras');

        $key=strtoupper(str_replace('J','I',$key));
        $abc='ABCDEFGHIKLMNOPQRSTUVWXYZ';          // sin J
        $seq=$this->dedup($key).$abc;
        $seq=$this->dedup($seq);

        $this->mat=$this->pos=[];
        $i=0;
        foreach(str_split($seq) as $ch){
            $r=intdiv($i,5); $c=$i%5;
            $this->mat[$r][$c]=$ch;
            $this->pos[$ch]=[$r,$c];
            $i++;
            if($i===25)break;
        }
    }
    private function dedup(string $s):string
    {
        $out='';
        foreach(str_split($s) as $ch) if(!str_contains($out,$ch)) $out.=$ch;
        return $out;
    }

    /* --- cifrar / descifrar texto --- */
    private function process(string $txt,int $dir):string
    {
        $clean=strtoupper(preg_replace('/[^A-Z]/','',$txt));
        $clean=str_replace('J','I',$clean);

        /* formar dígrafos */
        $pairs=[];
        $i=0; $n=strlen($clean);
        while($i<$n){
            $a=$clean[$i];
            $b=($i+1<$n)? $clean[$i+1]:'X';
            if($a===$b){ $b='X'; $i++; }
            else{ $i+=2; }
            $pairs[]=$a.$b;
        }
        if(strlen(end($pairs))===1) $pairs[count($pairs)-1].='X';

        $out='';
        foreach($pairs as $p){
            [$a,$b]=str_split($p);
            [$ra,$ca]=$this->pos[$a];
            [$rb,$cb]=$this->pos[$b];

            if($ra===$rb){                // misma fila
                $ca=($ca+$dir+5)%5;
                $cb=($cb+$dir+5)%5;
            }elseif($ca===$cb){           // misma columna
                $ra=($ra+$dir+5)%5;
                $rb=($rb+$dir+5)%5;
            }else{                        // rectángulo
                [$ca,$cb]=[$cb,$ca];
            }
            $out.=$this->mat[$ra][$ca].$this->mat[$rb][$cb];
        }
        if ($dir === -1) {                 // estamos descifrando
    $out = preg_replace('/X$/', '', $out);   // quita X final única
}
        return $out;
    }
}
