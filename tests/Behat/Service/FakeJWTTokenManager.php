<?php

declare(strict_types=1);

namespace App\Tests\Behat\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class FakeJWTTokenManager implements JWTTokenManagerInterface
{
    public function create(UserInterface $user): string
    {
        return 'fake-jwt-token';
    }

    public function createFromPayload(UserInterface $user, array $payload = []): string
    {
        return 'fake-jwt-token';
    }

    /**
     * @return array|false
     *
     * @throws JWTDecodeFailureException
     */
    public function decode(TokenInterface $token): array|bool
    {
        return ['token' => 'fake-jwt-token'];
    }

    public function parse(string $token): array
    {
        return ['token' => $token];
    }

    public function getUserIdClaim(): string
    {
        return 'username';
    }
}

