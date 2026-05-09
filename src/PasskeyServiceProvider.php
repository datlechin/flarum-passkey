<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey;

use Datlechin\Passkey\WebAuthn\ChallengeStore;
use Datlechin\Passkey\WebAuthn\OptionsBuilder;
use Datlechin\Passkey\WebAuthn\ServerFactory;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;

class PasskeyServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(IlluminateEventDispatcherAdapter::class, function ($container) {
            return new IlluminateEventDispatcherAdapter($container->make(Dispatcher::class));
        });

        // The webauthn library expects a PSR-14 dispatcher; route it to our
        // Illuminate-backed adapter so the events surface on Flarum's bus.
        $this->container->bind(EventDispatcherInterface::class, IlluminateEventDispatcherAdapter::class);

        $this->container->singleton(ServerFactory::class, function ($container) {
            return new ServerFactory(
                $container->make(SettingsRepositoryInterface::class),
                $container->make(Config::class),
                $container->make(EventDispatcherInterface::class),
            );
        });

        $this->container->singleton(OptionsBuilder::class, function ($container) {
            return new OptionsBuilder(
                $container->make(SettingsRepositoryInterface::class),
                $container->make(Config::class),
            );
        });

        $this->container->singleton(ChallengeStore::class);

        $this->container->bind(SerializerInterface::class.'@passkey', function ($container) {
            return $container->make(ServerFactory::class)->serializer();
        });

        $this->container->bind(AuthenticatorAttestationResponseValidator::class, function ($container) {
            return $container->make(ServerFactory::class)->attestationValidator();
        });

        $this->container->bind(AuthenticatorAssertionResponseValidator::class, function ($container) {
            return $container->make(ServerFactory::class)->assertionValidator();
        });
    }
}
