<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Shopware\App\SDK\Shop\ShopInterface;

#[MappedSuperclass]
abstract class Shop implements ShopInterface
{
    #[Id, Column(type: 'string')]
    protected string $shopId;

    #[Column(type: 'string')]
    protected string $shopUrl;

    #[Column(type: 'string')]
    protected string $shopSecret;

    #[Column(type: 'string', nullable: true)]
    protected ?string $shopClientId;

    #[Column(type: 'string', nullable: true)]
    protected ?string $shopClientSecret;

    #[Column(type: 'boolean')]
    protected bool $shopActive = false;

    public function __construct(string $shopId, string $shopUrl, string $shopSecret)
    {
        $this->shopId = $shopId;
        $this->shopUrl = $shopUrl;
        $this->shopSecret = $shopSecret;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getShopSecret(): string
    {
        return $this->shopSecret;
    }

    public function getShopClientId(): ?string
    {
        return $this->shopClientId;
    }

    public function getShopClientSecret(): ?string
    {
        return $this->shopClientSecret;
    }

    public function withShopApiCredentials(string $clientId, string $clientSecret): ShopInterface
    {
        $result = clone $this;
        $result->shopClientId = $clientId;
        $result->shopClientSecret = $clientSecret;

        return $result;
    }

    public function withShopUrl(string $url): ShopInterface
    {
        $result = clone $this;
        $result->shopUrl = $url;

        return $result;
    }

    public function withShopActive(bool $active): ShopInterface
    {
        $result = clone $this;
        $result->shopActive = $active;

        return $result;
    }

    public function setShopUrl(string $shopUrl): void
    {
        $this->shopUrl = $shopUrl;
    }

    public function setShopSecret(string $shopSecret): void
    {
        $this->shopSecret = $shopSecret;
    }

    public function setShopClientId(?string $shopClientId): void
    {
        $this->shopClientId = $shopClientId;
    }

    public function setShopClientSecret(?string $shopClientSecret): void
    {
        $this->shopClientSecret = $shopClientSecret;
    }

    public function isShopActive(): bool
    {
        return $this->shopActive;
    }
}
