<?php
declare(strict_types=1);
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

final class CipherSweetServiceProvider extends ServiceProvider
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
            $backend = $this->buildBackend();

            return new CipherSweet($this->buildKeyProvider($backend), $backend);
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
            case 'custom':
                return $this->buildCustomKeyProvider();
            case 'file':
                return new FileProvider(config('ciphersweet.file.path'));
            case 'string':
                return new StringProvider(config('ciphersweet.string.key'));
            case 'random':
            default:
                return new RandomProvider($backend);
        }
    }

    /**
     * @return KeyProviderInterface
     */
    protected function buildCustomKeyProvider(): KeyProviderInterface
    {
        $factory = app(config('ciphersweet.custom.via'));

        return $factory();
    }
}
