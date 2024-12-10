<?php declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Contracts\Encryption\Encrypter;
use SessionHandlerInterface;
use Throwable;

class EncryptedStore extends Store
{
    protected Encrypter $encrypter;

    public function __construct(Encrypter $encrypter, SessionHandlerInterface $handler, array $config = [])
    {
        $this->encrypter = $encrypter;

        parent::__construct($handler, $config);
    }

    protected function prepareWriteData(string $data): string
    {
        return $this->encrypter->encrypt($data);
    }

    protected function prepareReadData(string $data): string
    {
        try {
            return $this->encrypter->decrypt($data);
        } catch (Throwable) { }

        return json_encode([]);
    }

    protected function getEncrypter(): Encrypter
    {
        return $this->encrypter;
    }
}