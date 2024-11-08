<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Shopware\App\SDK\Shop\ShopInterface;

#[MappedSuperclass]
abstract class AbstractShop implements ShopInterface
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
        /** @var string $url */
        $url = preg_replace('#([^:])//+#', '$1/', $this->shopUrl);

        return rtrim($url, '/');
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

    public function setShopApiCredentials(string $clientId, string $clientSecret): ShopInterface
    {
        $this->shopClientId = $clientId;
        $this->shopClientSecret = $clientSecret;

        return $this;
    }

    public function setShopActive(bool $active): ShopInterface
    {
        $this->shopActive = $active;

        return $this;
    }

    public function setShopUrl(string $url): ShopInterface
    {
        $this->shopUrl = $url;

        return $this;
    }

    public function setShopSecret(string $shopSecret): ShopInterface
    {
        $this->shopSecret = $shopSecret;

        return $this;
    }

    public function isShopActive(): bool
    {
        return $this->shopActive;
    }
}
