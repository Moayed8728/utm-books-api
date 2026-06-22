<?php
namespace App\Middleware;

use App\Auth\Jwt;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Jwt $jwt) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->error('Missing bearer token', 401);
        }

        $payload = $this->jwt->decode($matches[1]);
        if (!$payload) {
            return $this->error('Invalid or expired token', 401);
        }

        return $handler->handle($request->withAttribute('auth', $payload));
    }

    private function error(string $message, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
