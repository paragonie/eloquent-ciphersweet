<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\CipherSweet;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, CipherSweet;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @param EncryptedMultiRows $multiRows
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\CipherSweetException
     * @throws \SodiumException
     */
    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('users')
            ->addTextField('users', 'name')
            ->addBlindIndex('users', 'name', new BlindIndex('users_name_bi'))
            ->addTextField('users', 'email')
            ->addBlindIndex('users', 'email', new BlindIndex('users_email_bi'));
    }
}
