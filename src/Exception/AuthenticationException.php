<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

class AuthenticationException extends \Exception
{
    /**
     * @var string
     */
    private $shopUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $reason;

    public function __construct(string $shopUrl, string $apiKey, string $reason)
    {
        $this->shopUrl = $shopUrl;
        $this->apiKey = $apiKey;
        $this->reason = $reason;

        $message = sprintf('Could not authenticate with store. Shopurl: %s, apikey: %s, reason: %s', $shopUrl, $apiKey, $reason);

        parent::__construct($message, 0, null);
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
