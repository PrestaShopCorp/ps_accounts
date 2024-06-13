<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Contract;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;

interface SessionInterface
{
    public static function getSessionName(): string;

    public function getToken(): Token;

    public function setToken(string $token, string $refreshToken): void;

    public function verifyToken(string $token): bool;

    public function refreshToken(string $refreshToken): Token;

    public function getOrRefreshToken(bool $forceRefresh = false): Token;

    public function cleanup(): void;
}
