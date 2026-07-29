<?php

namespace Imhotep\Tests\Encryption;

use Imhotep\Contracts\Encryption\EncryptionException;
use Imhotep\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class EncrypterTest extends TestCase
{
    private string $currentKey;
    private string $oldKey1;
    private string $oldKey2;
    private string $cipher;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->cipher = 'aes-256-cbc';
        $this->currentKey = Encrypter::genKeyBase64();
        $this->oldKey1 = Encrypter::genKeyBase64();
        $this->oldKey2 = Encrypter::genKeyBase64();
    }

    public function test_simple()
    {
        $e = new Encrypter($this->currentKey, $this->cipher);
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));

        $e = new Encrypter($this->currentKey, $this->cipher);
        $encrypted = $e->encrypt(25.421);
        $this->assertNotSame(25.421, $encrypted);
        $this->assertSame(25.421, $e->decrypt($encrypted));

        $e = new Encrypter($this->currentKey, $this->cipher);
        $encrypted = $e->encrypt([1,2,3]);
        $this->assertNotSame([1,2,3], $encrypted);
        $this->assertSame([1,2,3], $e->decrypt($encrypted));

        $e = new Encrypter($this->currentKey, $this->cipher);
        $encrypted = $e->encryptString('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decryptString($encrypted));
    }

    public function test_cipher_names_case()
    {
        $upper = new Encrypter($this->currentKey, 'AES-128-GCM');
        $encrypted = $upper->encrypt('bar');
        $this->assertNotSame('bar', $encrypted);

        $lower = new Encrypter($this->currentKey, 'aes-128-gcm');
        $this->assertSame('bar', $lower->decrypt($encrypted));

        $mixed = new Encrypter($this->currentKey, 'aEs-128-GcM');
        $this->assertSame('bar', $mixed->decrypt($encrypted));
    }

    public function test_aead_cipher()
    {
        $e = new Encrypter($this->currentKey, 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));
        $this->assertEmpty($data->mac);
        $this->assertNotEmpty($data->tag);

        $e = new Encrypter($this->currentKey, 'AES-128-CBC');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));
        $this->assertNotEmpty($data->mac);
        $this->assertEmpty($data->tag);
    }

    public function test_decrypt_with_previous_key()
    {
        $oldEncrypter = new Encrypter($this->oldKey1, $this->cipher);
        $data = 'Secret data with old key';
        $encrypted = $oldEncrypter->encrypt($data);

        $newEncrypter = new Encrypter(
            $this->currentKey,
            $this->cipher,
            $this->oldKey1 // previous key
        );

        // 3. Должен расшифровать с previous ключом
        $decrypted = $newEncrypter->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function test_decrypt_with_multiple_previous_keys(): void
    {
        $oldEncrypter = new Encrypter($this->oldKey2, $this->cipher);
        $data = 'Data encrypted with very old key';
        $encrypted = $oldEncrypter->encrypt($data);

        var_dump( [$this->oldKey1, $this->oldKey2]);
        $newEncrypter = new Encrypter(
            $this->currentKey,
            $this->cipher,
            implode(",", [$this->oldKey1, $this->oldKey2])
        );

        $decrypted = $newEncrypter->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function test_wrong_keys(): void
    {
        $this->expectException(EncryptionException::class);

        $encrypter = new Encrypter("invalid key", $this->cipher);
    }

    public function test_wrong_previous_keys(): void
    {
        $this->expectException(EncryptionException::class);

        $encrypter = new Encrypter($this->currentKey, $this->cipher, "invalid previous key");
    }
}