<?php
namespace App\Auth;

final class Jwt
{
    public function __construct(private string $secret) {}

    public function encode(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            $this->base64url(json_encode($header)),
            $this->base64url(json_encode($payload)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[] = $this->base64url($signature);
        return implode('.', $segments);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header64, $payload64, $sig64] = $parts;
        $expected = $this->base64url(hash_hmac('sha256', "$header64.$payload64", $this->secret, true));
        if (!hash_equals($expected, $sig64)) return null;
        $payload = json_decode($this->base64urlDecode($payload64), true);
        if (!is_array($payload)) return null;
        if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
        return $payload;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data . str_repeat('=', (4 - strlen($data) % 4) % 4), '-_', '+/'));
    }
}
