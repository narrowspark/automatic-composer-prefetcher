<?php

declare(strict_types=1);

/**
 * Copyright (c) 2018-2020 Daniel Bannert
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/narrowspark/automatic
 */

namespace Narrowspark\Automatic\Prefetcher;

use Composer\Composer;
use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use Narrowspark\Automatic\Common\AbstractContainer;
use Narrowspark\Automatic\Common\Contract\Container as ContainerContract;
use Narrowspark\Automatic\Common\Downloader\Downloader;
use Narrowspark\Automatic\Common\Downloader\ParallelDownloader;
use Narrowspark\Automatic\Common\Traits\GetGenericPropertyReaderTrait;
use Narrowspark\Automatic\Prefetcher\Contract\LegacyTagsManager as LegacyTagsManagerContract;
use Narrowspark\Automatic\Prefetcher\Contract\Prefetcher as PrefetcherContract;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class Container extends AbstractContainer
{
    use GetGenericPropertyReaderTrait;

    /**
     * Instantiate the container.
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $genericPropertyReader = $this->getGenericPropertyReader();

        parent::__construct([
            Composer::class => static function () use ($composer): Composer {
                return $composer;
            },
            Config::class => static function (ContainerContract $container): Config {
                return $container->get(Composer::class)->getConfig();
            },
            IOInterface::class => static function () use ($io): IOInterface {
                return $io;
            },
            InputInterface::class => static function (ContainerContract $container) use ($genericPropertyReader): ?InputInterface {
                return $genericPropertyReader($container->get(IOInterface::class), 'input');
            },
            RemoteFilesystem::class => static function (ContainerContract $container): RemoteFilesystem {
                return Factory::createRemoteFilesystem(
                    $container->get(IOInterface::class),
                    $container->get(Config::class)
                );
            },
            ParallelDownloader::class => static function (ContainerContract $container): ParallelDownloader {
                $rfs = $container->get(RemoteFilesystem::class);

                return new ParallelDownloader(
                    $container->get(IOInterface::class),
                    $container->get(Config::class),
                    $rfs->getOptions(),
                    $rfs->isTlsDisabled()
                );
            },
            PrefetcherContract::class => static function (ContainerContract $container): PrefetcherContract {
                return new Prefetcher(
                    $container->get(Composer::class),
                    $container->get(IOInterface::class),
                    $container->get(InputInterface::class),
                    $container->get(ParallelDownloader::class)
                );
            },
            LegacyTagsManagerContract::class => static function (ContainerContract $container): LegacyTagsManagerContract {
                $composer = $container->get(Composer::class);
                $io = $container->get(IOInterface::class);

                $endpoint = \getenv('AUTOMATIC_PREFETCHER_SYMFONY_ENDPOINT');

                if ($endpoint === false) {
                    $endpoint = $composer->getPackage()->getExtra()[Plugin::COMPOSER_EXTRA_KEY]['endpoint']['symfony'] ?? 'https://flex.symfony.com';
                }

                $downloader = new Downloader(
                    \rtrim($endpoint, '/'),
                    $composer,
                    $io,
                    $container->get(ParallelDownloader::class)
                );

                return new LegacyTagsManager($io, $downloader);
            },
            'composer-extra' => static function (ContainerContract $container): array {
                return $container->get(Composer::class)->getPackage()->getExtra();
            },
        ]);
    }
}
