<?php

namespace Imhotep\Tests\Encryption;

use Imhotep\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class EncrypterTest extends TestCase
{
    public function test_simple()
    {
        $key = Encrypter::generateKey();

        $e = new Encrypter($key);
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));

        $e = new Encrypter($key);
        $encrypted = $e->encrypt(25.421);
        $this->assertNotSame(25.421, $encrypted);
        $this->assertSame(25.421, $e->decrypt($encrypted));

        $e = new Encrypter($key);
        $encrypted = $e->encrypt([1,2,3]);
        $this->assertNotSame([1,2,3], $encrypted);
        $this->assertSame([1,2,3], $e->decrypt($encrypted));

        $e = new Encrypter($key);
        $encrypted = $e->encryptString('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decryptString($encrypted));
    }

    public function test_cipher_names_case()
    {
        $key = Encrypter::generateKey();

        $upper = new Encrypter($key, 'AES-128-GCM');
        $encrypted = $upper->encrypt('bar');
        $this->assertNotSame('bar', $encrypted);

        $lower = new Encrypter($key, 'aes-128-gcm');
        $this->assertSame('bar', $lower->decrypt($encrypted));

        $mixed = new Encrypter($key, 'aEs-128-GcM');
        $this->assertSame('bar', $mixed->decrypt($encrypted));
    }

    public function test_aead_cipher()
    {
        $key = Encrypter::generateKey();
        $e = new Encrypter($key, 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));
        $this->assertEmpty($data->mac);
        $this->assertNotEmpty($data->tag);

        $e = new Encrypter($key, 'AES-128-CBC');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));
        $this->assertNotEmpty($data->mac);
        $this->assertEmpty($data->tag);
    }
}