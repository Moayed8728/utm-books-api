<?php
namespace App\Controllers;
use App\Repositories\BookRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class BookController {
    public function __construct(private BookRepository $books) {}

    public function index(Request $r, Response $s): Response {
        $p = $r->getQueryParams();
        $rows = $this->books->all((string)($p['q'] ?? ''), (int)($p['limit'] ?? 0));
        return $this->json($s, ['count'=>count($rows), 'data'=>$rows]);
    }

    public function show(Request $r, Response $s, array $a): Response {
        $book = $this->books->find((int)$a['id']);
        return $book ? $this->json($s, $book) : $this->json($s, ['error'=>'not found'], 404);
    }

    public function create(Request $r, Response $s): Response {
        $auth = (array)$r->getAttribute('auth', []);
        $body = (array)$r->getParsedBody();
        $errors = $this->validate($body, true);
        if ($errors) return $this->json($s, ['errors'=>$errors], 400);
        $id = $this->books->create($body, (int)($auth['sub'] ?? 0));
        return $this->json($s, ['message'=>'Book created', 'data'=>$this->books->find($id)], 201)
            ->withHeader('Location', '/api/books/' . $id);
    }

    public function update(Request $r, Response $s, array $a): Response {
        $id = (int)$a['id'];
        $book = $this->books->find($id);
        if (!$book) return $this->json($s, ['error'=>'not found'], 404);

        $auth = (array)$r->getAttribute('auth', []);
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0);
        $isAdmin = ($auth['role'] ?? 'member') === 'admin';
        if (!$isOwner && !$isAdmin) return $this->json($s, ['error'=>'Forbidden'], 403);

        $body = (array)$r->getParsedBody();
        $errors = $this->validate($body, false);
        if ($errors) return $this->json($s, ['errors'=>$errors], 400);
        if (!$body) return $this->json($s, ['error'=>'no fields to update'], 400);

        $this->books->update($id, $body);
        return $this->json($s, ['message'=>'Book updated', 'data'=>$this->books->find($id)]);
    }

    public function delete(Request $r, Response $s, array $a): Response {
        $auth = (array)$r->getAttribute('auth', []);
        if (($auth['role'] ?? 'member') !== 'admin') return $this->json($s, ['error'=>'Forbidden'], 403);
        $id = (int)$a['id'];
        if (!$this->books->delete($id)) return $this->json($s, ['error'=>'not found'], 404);
        return $this->json($s, ['message'=>'Book deleted']);
    }

    private function validate(array $b, bool $requireAll): array {
        $errors = [];
        foreach (['title', 'author'] as $field) {
            if ($requireAll && !array_key_exists($field, $b)) $errors[$field] = "$field is required";
            elseif (array_key_exists($field, $b) && trim((string)$b[$field]) === '') $errors[$field] = "$field cannot be empty";
        }
        if ($requireAll && !array_key_exists('year', $b)) $errors['year'] = 'year is required';
        elseif (array_key_exists('year', $b)) {
            if (filter_var($b['year'], FILTER_VALIDATE_INT) === false) $errors['year'] = 'year must be an integer';
            elseif ((int)$b['year'] < 1000 || (int)$b['year'] > (int)date('Y')) $errors['year'] = 'year must be 1000..now';
        }
        if (array_key_exists('genre', $b) && trim((string)$b['genre']) === '') $errors['genre'] = 'genre cannot be empty';
        return $errors;
    }

    private function json(Response $r, $data, int $code=200): Response {
        $r->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        return $r->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($code);
    }
}
