<?php declare(strict_types=1);

namespace Shopware\AppBundle\Registration;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;
use Shopware\AppBundle\Authentication\RequestVerifier;
use Shopware\AppBundle\Authentication\ResponseSigner;
use Shopware\AppBundle\Exception\SignatureNotFoundException;
use Shopware\AppBundle\Exception\SignatureValidationException;
use Shopware\AppBundle\Shop\ShopEntity;
use Shopware\AppBundle\Shop\ShopRepositoryInterface;

class RegistrationService
{
    public function __construct(
        private string $appSecret,
        private ShopRepositoryInterface $shopRepository,
        private RequestVerifier $requestVerifier,
        private ResponseSigner $responseSigner,
        private ShopSecretGeneratorInterface $shopSecretGeneratorInterface
    ) {
    }

    /**
     * @psalm-suppress DeprecatedMethod
     *
     * @throws SignatureValidationException
     * @throws SignatureNotFoundException
     */
    public function handleShopRegistrationRequest(RequestInterface $request, string $confirmUrl): array
    {
        $this->requestVerifier->authenticateRegistrationRequest($request, $this->appSecret);

        $queries = Query::parse($request->getUri()->getQuery());

        $shop = $this->shopRepository->getShopFromId($queries['shop-id']);

        if ($shop === null) {
            $shop = new ShopEntity(
                $queries['shop-id'],
                $queries['shop-url'],
                $this->shopSecretGeneratorInterface->generate()
            );

            $this->shopRepository->createShop($shop);
        }

        return [
            'proof' => $this->responseSigner->getRegistrationSignature($shop, $this->appSecret),
            'confirmation_url' => $confirmUrl,
            'secret' => $shop->getShopSecret(),
        ];
    }

    /**
     * @psalm-suppress DeprecatedMethod
     *
     * @throws \JsonException
     * @throws SignatureValidationException
     * @throws SignatureNotFoundException
     */
    public function handleConfirmation(RequestInterface $request): void
    {
        $requestContent = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $shop = $this->shopRepository->getShopFromId($requestContent['shopId']);

        if (!$shop) {
            throw new SignatureValidationException($request);
        }

        $request->getBody()->rewind();

        $this->requestVerifier->authenticatePostRequest($request, $shop);

        $this->shopRepository->updateShop(
            $shop->withApiKey($requestContent['apiKey'])
                ->withSecretKey($requestContent['secretKey'])
        );
    }
}
