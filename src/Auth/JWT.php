<?php

namespace Imhotep\Auth;

class JWT
{
    public function __construct(
        protected string $secretKey,
        protected string $algorithm = 'HS256'
    ) { }

    public function encode(array $payload, array $header = []): string
    {
        $header = json_encode(array_merge(['typ' => 'JWT', 'alg' => $this->algorithm], $header));
        $header = $this->base64Encode($header);

        $payload = json_encode($payload);
        $payload = $this->base64Encode($payload);

        $signature = $this->sign($header.$payload);

        return $header.'.'.$payload.'.'.$signature;
    }

    public function decode(string $jwt): ?array
    {
        list($header, $payload, $signature) = explode('.', $jwt);

        if ($this->verifySign($header, $payload, $signature)) {
            return json_decode($this->base64Decode($payload), true);
        }

        return null;
    }

    protected function sign(string $data): string
    {
        $data = hash_hmac('sha256', $data, $this->secretKey, true);

        return $this->base64Encode($data);
    }

    protected function verifySign($header, $payload, $signature): bool
    {
        return hash_equals($this->sign($header.$payload), $signature);
    }

    protected static function base64Encode(string $input): string
    {
        return base64_encode($input);
    }

    protected static function base64Decode(string $input): string
    {
        return base64_decode($input);
    }
}