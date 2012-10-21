<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

//loader do Composer
$loader = require_once __DIR__.'/vendor/autoload.php';

//cria a aplicação
$app = new Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/app.db',
    ),
));

$app->get('/cervejas/{id}', function ($id) use ($app) {
    if ($id == null) {
        $sql = "SELECT * FROM cervejas";
        $cervejas = $app['db']->fetchAll($sql);
        return new Response (json_encode($cervejas), 200); 
    }

    $sql = "SELECT * FROM cervejas WHERE nome = ?";
    $cerveja = $app['db']->fetchAssoc($sql, array($id));
    if (!$cerveja ) {
        return new Response (json_encode('Não encontrada'), 404); 
    }

    return new Response (json_encode($cerveja), 200); 
})->value('id', null);

$app->post('/cervejas', function (Request $request) use ($app) {
    //pega os dados
    if (!$data = $request->get('cerveja')) {
        return new Response('Faltam parâmetros', 400);
    }

    $app['db']->insert('cervejas', array('nome' => $data['nome'], 'estilo' => $data['estilo']));
    
    //redireciona para a nova cerveja
    return $app->redirect('/cervejas/' . $data['nome'], 201);
});

$app->put('/cervejas/{id}', function (Request $request, $id) use ($app) {
    //pega os dados
    if (!$data = $request->get('cerveja')) {
        return new Response('Faltam parâmetros', 400);
    }
    $sql = "SELECT * FROM cervejas WHERE nome = ?";
    $cerveja = $app['db']->fetchAssoc($sql, array($id));
    if (!$cerveja ) {
        return new Response (json_encode('Não encontrada'), 404); 
    }

    //Persiste na base de dados
    $app['db']->update(
        'cervejas', 
        array('nome' => $data['nome'], 'estilo' => $data['estilo']), 
        array('id' => $cerveja['id'])
    );
    
    return new Response('Cerveja atualizada', 200);
});

$app->delete('/cervejas/{id}', function (Request $request, $id) use ($app) {
   //busca da base de dados
    $sql = "SELECT * FROM cervejas WHERE nome = ?";
    $cerveja = $app['db']->fetchAssoc($sql, array($id));
    if (!$cerveja ) {
        return new Response (json_encode('Não encontrada'), 404); 
    }

    $app['db']->delete('cervejas', array('id' => $cerveja['id']));
    
    return new Response('Cerveja removida', 200);
});

// $app->before(function (Request $request) use ($app) {
//     if( ! $request->headers->has('authorization')){
//         return new Response('Unauthorized', 401);
//     }

//     require_once 'configs/clients.php';
//     if (!in_array($request->headers->get('authorization'), array_keys($clients))) {
//         return new Response('Unauthorized', 401);
//     }
// });


$app->after(function (Request $request, Response $response) {
    $response->headers->set('Content-Type', 'text/json');
});
$app['debug'] = true;
$app->run();
