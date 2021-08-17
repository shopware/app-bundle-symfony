<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

class ShopEntity
{
    public function __construct(
        private string $shopId,
        private string $shopUrl,
        private string $shopSecret,
        private ?string $apiKey = null,
        private ?string $secretKey = null
    ) {
    }

    public function getId(): string
    {
        return $this->shopId;
    }

    public function getUrl(): string
    {
        return $this->shopUrl;
    }

    public function getShopSecret(): string
    {
        return $this->shopSecret;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }
}
