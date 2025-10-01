<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\CipherSweet;
use ParagonIE\EloquentCipherSweet\Tests\TestCase;

class CipherSweetTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ciphersweet.provider' => 'string',
            'ciphersweet.string.key' => '3f3c6556191e3624a9745367614f24a18641973b5269781604a838507c13e565'
        ]);
    }

    /**
     * @test
     */
    public function testEncryptionAndDecryption()
    {
        $user = new User();
        $user->name = 'test';
        $user->email = 'test@example.com';
        $user->save();

        // Check the raw value in the database
        $rawUser = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame('test@example.com', $rawUser->email);
        $this->assertNotEmpty($rawUser->email_bi);

        // Retrieve the user using the model and check decryption
        $retrievedUser = User::whereBlind('email', 'test@example.com')->first();
        $this->assertSame('test@example.com', $retrievedUser->email);
    }
}

class User extends Model
{
    use CipherSweet;

    protected $table = 'users';

    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('users')
            ->addTextField('users', 'name')
            ->addBlindIndex(
                'users',
                'name',
                new BlindIndex('name_bi')
            )
            ->addTextField('users', 'email')
            ->addBlindIndex(
                'users',
                'email',
                new BlindIndex('email_bi', [], 16)
            );
    }
}
