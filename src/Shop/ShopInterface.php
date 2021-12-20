<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

interface ShopInterface
{
    public function getId(): string;

    public function getUrl(): string;

    public function getShopSecret(): string;

    public function getApiKey(): ?string;

    public function getSecretKey(): ?string;

    public function withApiKey(string $apiKey): ShopInterface;

    public function withSecretKey(string $secretKey): ShopInterface;
}
