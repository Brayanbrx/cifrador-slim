<?php
namespace App\Cifrados\SustitucionMonogramicaPolialfabeto;

/**
 * Cifrado Hill genérico n×n (mod 26).  I/J se tratan igual que letras separadas.
 * La matriz se pasa como "a,b,c;d,e,f;g,h,i".
 */
final class CifradoHill
{
    private const ABC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /* ---- API ---- */
    public function cifrar(string $txt,string $matStr):string
    {
        [$m,$n] = $this->parseMatrix($matStr);
        return $this->enc($txt,$m,$n);
    }
    public function descifrar(string $txt,string $matStr):string
    {
        [$m, $n] = $this->parseMatrix($matStr);
        $inv     = $this->inverseMatrix($m, $n);
        $out     = $this->enc($txt, $inv, $n);

        /*  ← NUEVA LÍNEA: quita ‘X’ de relleno al final (si existe) */
        return rtrim($out, 'X');
    }

    /* ---- core encrypt ---- */
    private function enc(string $txt,array $mat,int $n):string
    {
        $msg = strtoupper(preg_replace('/[^A-Z]/','',$txt));
        $pad = ($n - (strlen($msg)%$n))%$n;
        if($pad) $msg .= str_repeat('X',$pad);

        $out='';
        for($i=0;$i<strlen($msg);$i+=$n){
            $vec=[];
            for($k=0;$k<$n;$k++)
                $vec[] = strpos(self::ABC,$msg[$i+$k]);

            for($row=0;$row<$n;$row++){
                $sum=0;
                for($col=0;$col<$n;$col++)
                    $sum += $mat[$row][$col]*$vec[$col];
                $out .= self::ABC[$sum%26];
            }
        }
        return $out;
    }

    /* ---- matrix helpers ---- */
    /** @return array{array<int,int>,int}   matrix + size n */
    private function parseMatrix(string $s):array
    {
        if(trim($s)==='') throw new \InvalidArgumentException('Debe indicar matriz');
        $rows = array_map('trim',explode(';',$s));
        $mat=[];
        foreach($rows as $r){
            $nums = array_map('intval',explode(',',$r));
            $mat[] = $nums;
        }
        $n=count($mat);
        foreach($mat as $row)
            if(count($row)!==$n) throw new \InvalidArgumentException('Matriz no cuadrada');
        if($this->gcd($this->det($mat,$n),26)!==1)
            throw new \InvalidArgumentException('Determinante no coprimo con 26; matriz no invertible');
        return [$mat,$n];
    }

    /** determinante mod 26 (recursivo, n ≤3 práctico) */
    private function det(array $m,int $n):int
    {
        if($n===1) return $m[0][0]%26;
        if($n===2) return ($m[0][0]*$m[1][1]-$m[0][1]*$m[1][0])%26;

        $det=0;
        for($c=0;$c<$n;$c++){
            $minor=$this->minor($m,0,$c,$n);
            $det += (($c%2? -1:1)*$m[0][$c]*$this->det($minor,$n-1));
        }
        return ($det%26+26)%26;
    }

    private function minor(array $m,int $row,int $col,int $n):array
    {
        $minor=[];
        for($i=0;$i<$n;$i++){
            if($i==$row)continue;
            $line=[];
            for($j=0;$j<$n;$j++){
                if($j==$col)continue;
                $line[]=$m[$i][$j];
            }
            $minor[]=$line;
        }
        return $minor;
    }

    /** inversa (adjugate * det⁻¹ mod 26) */
    private function inverseMatrix(array $m,int $n):array
    {
        $det=$this->det($m,$n);
        $detInv=$this->modInv($det,26);
        $adj=[];

        for($r=0;$r<$n;$r++){
            $row=[];
            for($c=0;$c<$n;$c++){
                $minor=$this->minor($m,$r,$c,$n);
                $cof =$this->det($minor,$n-1);
                if(($r+$c)%2===1) $cof = -$cof;
                $row[] = ($detInv*$cof)%26;
            }
            $adj[]=$row;
        }
        /* trasponer adj */
        $inv=[];
        for($r=0;$r<$n;$r++)
            for($c=0;$c<$n;$c++)
                $inv[$r][$c]=(($adj[$c][$r]%26)+26)%26;
        return $inv;
    }

    /* ---- util ---- */
    private function modInv(int $a,int $m):int
    {
        [$g,$x]= $this->egcd($a,$m);
        if($g!==1) throw new \InvalidArgumentException('Det no invertible');
        return ($x%$m+$m)%$m;
    }
    private function egcd(int $a,int $b):array
    {
        if($b==0) return [$a,1,0];
        [$g,$x1,$y1]=$this->egcd($b,$a%$b);
        return [$g,$y1,$x1- intdiv($a,$b)*$y1];
    }
    private function gcd(int $a,int $b):int
    {
        return $b==0? abs($a):$this->gcd($b,$a%$b);
    }
}
