<?php

namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Support\ServiceProvider;
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\Backend\ModernCrypto;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Contract\BackendInterface;
use ParagonIE\CipherSweet\Contract\KeyProviderInterface;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use ParagonIE\CipherSweet\KeyProvider\FileProvider;
use ParagonIE\CipherSweet\KeyProvider\RandomProvider;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;
use ParagonIE\EloquentCipherSweet\Console\GenerateKey;

class CipherSweetServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateKey::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/config/ciphersweet.php' => config_path('ciphersweet.php'),
        ]);
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CipherSweet::class, function () {
            return new CipherSweet($this->buildKeyProvider($this->buildBackend()));
        });
    }

    /**
     * @return BackendInterface
     */
    protected function buildBackend(): BackendInterface
    {
        switch (config('ciphersweet.backend')) {
            case 'fips':
                return new FIPSCrypto;
            case 'nacl':
            default:
                return new ModernCrypto;
        }
    }

    /**
     * @param BackendInterface $backend
     * @return KeyProviderInterface
     * @throws CryptoOperationException
     */
    protected function buildKeyProvider(BackendInterface $backend): KeyProviderInterface
    {
        switch (config('ciphersweet.provider')) {
            case 'file':
                return new FileProvider($backend, config('ciphersweet.file.path'));
            case 'string':
                return new StringProvider($backend, config('ciphersweet.string.key'));
            case 'random':
            default:
                return new RandomProvider($backend);
        }
    }
}
