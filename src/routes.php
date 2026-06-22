<?php
use App\Auth\Jwt;
use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Database;
use App\Middleware\AuthMiddleware;
use App\Repositories\BookRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $pdo = Database::get();
    $jwt = new Jwt($_ENV['JWT_SECRET'] ?? 'dev_secret_change_me');
    $authMw = new AuthMiddleware($jwt);
    $authCtrl = new AuthController($pdo, $jwt);
    $bookCtrl = new BookController(new BookRepository($pdo));

    $app->options('/{routes:.*}', function (Request $request, Response $response): Response {
        return $response;
    });

    $app->get('/', function (Request $request, Response $response): Response {
        $response->getBody()->write(json_encode(['ok' => true, 'app' => 'UTM Books API']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/auth/login', [$authCtrl, 'login']);
    $app->post('/auth/register', [$authCtrl, 'register']);
    $app->get('/auth/me', [$authCtrl, 'me'])->add($authMw);

    $app->group('/api', function ($g) use ($bookCtrl, $authMw) {
        $g->get('/books', [$bookCtrl, 'index']);
        $g->get('/books/{id}', [$bookCtrl, 'show']);
        $g->post('/books', [$bookCtrl, 'create'])->add($authMw);
        $g->put('/books/{id}', [$bookCtrl, 'update'])->add($authMw);
        $g->delete('/books/{id}', [$bookCtrl, 'delete'])->add($authMw);
    });
};
