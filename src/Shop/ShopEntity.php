<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

class ShopEntity implements ShopInterface
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

    /**
     * @deprecated will be removed in stable version
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * @deprecated will be removed in stable version
     */
    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function withApiKey(string $apiKey): ShopInterface
    {
        return new self(
            $this->shopId,
            $this->shopUrl,
            $this->shopSecret,
            $apiKey,
            $this->secretKey
        );
    }

    public function withSecretKey(string $secretKey): ShopInterface
    {
        return new self(
            $this->shopId,
            $this->shopUrl,
            $this->shopSecret,
            $this->apiKey,
            $secretKey
        );
    }
}
