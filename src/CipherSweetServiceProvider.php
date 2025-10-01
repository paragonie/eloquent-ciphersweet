<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Exception;
use Illuminate\Support\ServiceProvider;
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\Backend\BoringCrypto;
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
        return match (config('ciphersweet.backend')) {
            'fips' => new FIPSCrypto,
            default => new BoringCrypto,
        };
    }

    /**
     * @param BackendInterface $backend
     * @return KeyProviderInterface
     * @throws CryptoOperationException
     * @throws Exception
     */
    protected function buildKeyProvider(BackendInterface $backend): KeyProviderInterface
    {
        switch (config('ciphersweet.provider')) {
            case 'custom':
                return $this->buildCustomKeyProvider();
            case 'file':
                $file = config('ciphersweet.providers.file.path');
                if (!is_readable($file)) {
                    throw new Exception("File does not exist");
                }
                return new FileProvider($file);
            case 'string':
                $key = config('ciphersweet.providers.string.key');
                if (is_null($key)) {
                    throw new Exception("Config directive is not specified");
                }
                return new StringProvider($key);
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
