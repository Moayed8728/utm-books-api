<?php
namespace App\Controllers;

use App\Auth\Jwt;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(private PDO $pdo, private Jwt $jwt) {}

    public function login(Request $request, Response $response): Response
    {
        $body = (array)$request->getParsedBody();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->json($response, ['error' => 'Invalid credentials'], 401);
        }

        $payload = [
            'sub' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (int)($_ENV['JWT_TTL'] ?? 3600),
        ];

        return $this->json($response, [
            'access_token' => $this->jwt->encode($payload),
            'token_type' => 'Bearer',
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        $body = (array)$request->getParsedBody();
        $name = trim((string)($body['name'] ?? ''));
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $errors = [];

        if ($name === '') $errors['name'] = 'name is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'valid email is required';
        if (strlen($password) < 8) $errors['password'] = 'password must be at least 8 characters';
        if ($errors) return $this->json($response, ['errors' => $errors], 400);

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) return $this->json($response, ['error' => 'Email already registered'], 409);

        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => 'member',
        ]);

        return $this->json($response, [
            'message' => 'Registered successfully',
            'user' => ['id' => (int)$this->pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'member'],
        ], 201);
    }

    public function me(Request $request, Response $response): Response
    {
        $auth = (array)$request->getAttribute('auth', []);
        return $this->json($response, [
            'id' => (int)($auth['sub'] ?? 0),
            'name' => $auth['name'] ?? '',
            'email' => $auth['email'] ?? '',
            'role' => $auth['role'] ?? 'member',
            'created_at' => null,
        ]);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}
