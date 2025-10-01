<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests;

use Illuminate\Support\Facades\DB;
use ParagonIE\EloquentCipherSweet\Tests\TestModels\Post;

class EncryptedFieldModelTest extends TestCase
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

    public function testEncryptionAndDecryption()
    {
        $post = new Post();
        $post->title = 'test';
        $post->body = 'test body';
        $post->save();

        // Check the raw value in the database
        $rawPost = DB::table('posts')->where('id', $post->id)->first();
        $this->assertNotSame('test', $rawPost->title);
        $this->assertNotEmpty($rawPost->title_bi);

        // Retrieve the post using the model and check decryption
        $retrievedPost = Post::whereBlind('title', 'test')->first();
        $this->assertSame('test', $retrievedPost->title);
    }
}
