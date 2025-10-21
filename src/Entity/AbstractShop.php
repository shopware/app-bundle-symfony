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

    #[Column(type: 'string', nullable: true)]
    protected ?string $pendingShopUrl = null;

    #[Column(type: 'string', nullable: true)]
    protected ?string $pendingShopSecret = null;

    #[Column(type: 'string', nullable: true)]
    protected ?string $previousShopSecret = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $secretsRotatedAt = null;

    /**
     * @deprecated tag:v6.0.0 - Will be removed. Double signature verification will always be enforced.
     */
    #[Column(type: 'boolean')]
    protected bool $hasVerifiedWithDoubleSignature = false;

    #[Column(type: 'boolean')]
    protected bool $isRegistrationConfirmed = false;

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

    public function getPendingShopUrl(): ?string
    {
        return $this->pendingShopUrl;
    }

    public function getPreviousShopSecret(): ?string
    {
        return $this->previousShopSecret;
    }

    public function getPendingShopSecret(): ?string
    {
        return $this->pendingShopSecret;
    }

    public function setPendingShopSecret(?string $secret): ShopInterface
    {
        $this->pendingShopSecret = $secret;

        return $this;
    }

    public function setPendingShopUrl(?string $shopUrl): ShopInterface
    {
        $this->pendingShopUrl = $shopUrl;

        return $this;
    }

    public function setPreviousShopSecret(string $secret): ShopInterface
    {
        $this->previousShopSecret = $secret;

        return $this;
    }

    public function setSecretsRotatedAt(\DateTimeImmutable $updatedAt): ShopInterface
    {
        $this->secretsRotatedAt = $updatedAt;

        return $this;
    }

    public function getSecretsRotatedAt(): ?\DateTimeImmutable
    {
        return $this->secretsRotatedAt;
    }

    /**
     * @deprecated tag:v6.0.0 - Will be removed. Double signature verification will always be enforced.
     */
    public function setVerifiedWithDoubleSignature(): ShopInterface
    {
        $this->hasVerifiedWithDoubleSignature = true;

        return $this;
    }

    /**
     * @deprecated tag:v6.0.0 - Will be removed. Double signature verification will always be enforced.
     */
    public function hasVerifiedWithDoubleSignature(): bool
    {
        return $this->hasVerifiedWithDoubleSignature;
    }


    public function setRegistrationConfirmed(): ShopInterface
    {
        $this->isRegistrationConfirmed = true;

        return $this;
    }

    public function isRegistrationConfirmed(): bool
    {
        return $this->isRegistrationConfirmed;
    }
}
