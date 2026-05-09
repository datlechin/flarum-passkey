<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\WebAuthn;

use Cose\Algorithm\Manager as AlgorithmManager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

/**
 * Builds the validators and JSON serializer used by every passkey ceremony.
 */
class ServerFactory
{
    private ?SerializerInterface $serializer = null;
    private ?AttestationStatementSupportManager $attestationStatementSupportManager = null;

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly Config $config,
        private readonly EventDispatcherInterface $events,
    ) {
    }

    public function serializer(): SerializerInterface
    {
        return $this->serializer ??= (new WebauthnSerializerFactory(
            $this->attestationStatementSupportManager()
        ))->create();
    }

    public function attestationValidator(): AuthenticatorAttestationResponseValidator
    {
        $validator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyStepManagerFactory()->creationCeremony()
        );
        $validator->setEventDispatcher($this->events);

        return $validator;
    }

    public function assertionValidator(): AuthenticatorAssertionResponseValidator
    {
        $validator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyStepManagerFactory()->requestCeremony()
        );
        $validator->setEventDispatcher($this->events);

        return $validator;
    }

    public function attestationStatementSupportManager(): AttestationStatementSupportManager
    {
        if ($this->attestationStatementSupportManager !== null) {
            return $this->attestationStatementSupportManager;
        }

        $manager = AttestationStatementSupportManager::create();
        $manager->add(NoneAttestationStatementSupport::create());
        $manager->add(PackedAttestationStatementSupport::create($this->algorithmManager()));
        $manager->add(FidoU2FAttestationStatementSupport::create());
        $manager->add(AppleAttestationStatementSupport::create());
        $manager->add(AndroidKeyAttestationStatementSupport::create());
        $manager->add(TPMAttestationStatementSupport::create());

        return $this->attestationStatementSupportManager = $manager;
    }

    private function algorithmManager(): AlgorithmManager
    {
        return AlgorithmManager::create()
            ->add(ES256::create())
            ->add(RS256::create())
            ->add(Ed25519::create());
    }

    private function ceremonyStepManagerFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAlgorithmManager($this->algorithmManager());
        $factory->setAttestationStatementSupportManager($this->attestationStatementSupportManager());

        $allowedOrigins = $this->buildAllowedOrigins();
        if ($allowedOrigins !== []) {
            $factory->setAllowedOrigins($allowedOrigins, allowSubdomains: false);
        }

        return $factory;
    }

    /** @return string[] */
    private function buildAllowedOrigins(): array
    {
        $origins = [rtrim((string) $this->config->url(), '/')];

        foreach (self::parseRelatedOrigins((string) $this->settings->get('datlechin-passkey.related_origins')) as $origin) {
            $origins[] = $origin;
        }

        return array_values(array_unique($origins));
    }

    /** @return string[] */
    public static function parseRelatedOrigins(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $origins = [];
        foreach ($lines as $line) {
            $trimmed = rtrim(trim($line), '/');
            if ($trimmed === '' || ! self::isValidOrigin($trimmed)) {
                continue;
            }
            $origins[] = $trimmed;
        }

        return $origins;
    }

    private static function isValidOrigin(string $value): bool
    {
        $parts = parse_url($value);
        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            return false;
        }
        if ($parts['scheme'] === 'https') {
            return true;
        }
        if ($parts['scheme'] === 'http' && in_array($parts['host'], ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return false;
    }
}
