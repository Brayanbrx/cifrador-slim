<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Message\ResponseInterface as Res;
use Slim\Psr7\Response;

use App\Cifrados\Desplazamiento\PuroConPalabraClave;
use App\Cifrados\Sustitucion\{MonoAlfabetica,Polialfabetica};
use App\Cifrados\SustitucionMonogramicaPolialfabeto\{
    PolialfabeticosPeriodicos, 
    AnagramacionFilasColumnas, 
    PlayFair, 
    CifradoHill, 
    AtaqueKasiski
};
use App\Cifrados\Transposicion\{Grupos,Series,Filas,Columnas,ZigZag};

$app=AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true,true,true);

$app->post('/cifrar',function(Req $req,Res $res):Res{
    $p=(array)$req->getParsedBody();
    $txt=$p['texto']??''; 
    $tipo=$p['tipo']??''; 
    $act=$p['accion']??'cifrar';

    try{
        $out=match($tipo){
            /* Sustitución */
            'desplazamiento-clave'=>run(new PuroConPalabraClave(),$txt,$p['clave']??'',$act),
            'monoalfabetica'     =>run(new MonoAlfabetica()      ,$txt,$p['clave']??'',$act),
            'polialfabetica'     =>run(new Polialfabetica()      ,$txt,$p['clave']??'',$act),
            'periodicos'         =>run(new PolialfabeticosPeriodicos(),$txt,$p['num_clave']??'',$act),
            'anagramacion'       =>run(new AnagramacionFilasColumnas(),$txt,[
                                            'col'=>$p['orden_col']??'',
                                            'fil'=>$p['orden_fil']??'' ],$act),
            'playfair' => run(new PlayFair(), $txt, $p['clave']??'', $act),
            'hill' => run(new CifradoHill(), $txt, $p['matriz']??'', $act),
            'kasiski' => (new AtaqueKasiski())->analizar($txt),

            /* Transposición */
            'grupos'   =>run(new Grupos()  ,$txt,(int)($p['tam_grupo']??0),$act),
            'series'   =>run(new Series()  ,$txt,$p['orden']??'',$act),
            'filas'    =>run(new Filas()   ,$txt,(int)($p['filas']??0),$act),
            'columnas' =>run(new Columnas(),$txt,$p['orden']??'',$act),
            'zigzag'   =>run(new ZigZag()  ,$txt,(int)($p['rieles']??0),$act),
            default => throw new RuntimeException('Algoritmo no implementado')
        };
    }catch(\Throwable $e){
        $out='Error: '.$e->getMessage(); 
        $res=$res->withStatus(400);
    }
    $res->getBody()->write($out);
    return $res->withHeader('Content-Type','text/plain');
});

$app->get('/',function():Res{
    $html=file_get_contents(__DIR__.'/form.html');
    $r=new Response(); 
    $r->getBody()->write($html);
    return $r->withHeader('Content-Type','text/html');
});

$app->run();

function run(object $alg,string $txt,mixed $key,string $accion):string
{ 
    return $accion==='descifrar'? $alg->descifrar($txt,$key):$alg->cifrar($txt,$key); 
}
