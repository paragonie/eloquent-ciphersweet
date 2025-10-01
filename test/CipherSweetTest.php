<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests;

use Illuminate\Support\Facades\DB;
use ParagonIE\EloquentCipherSweet\Tests\TestModels\{
    Contact,
    User
};

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
            'ciphersweet.providers.string.key' => '3f3c6556191e3624a9745367614f24a18641973b5269781604a838507c13e565'
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

    /**
     * @test
     */
    public function testWhereBlindWithCustomIndexName()
    {
        $contact = new Contact();
        $contact->name = 'test';
        $contact->email = 'test@example.com';
        $contact->save();

        // Retrieve the contact using the model and custom blind index
        $retrievedContact = Contact::whereBlind('email', 'test@example.com', 'my_custom_email_index')->first();
        $this->assertSame('test@example.com', $retrievedContact->email);
    }
}
