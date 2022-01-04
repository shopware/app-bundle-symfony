<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Attribute\PaymentFinalizeRoute;
use Shopware\AppBundle\Attribute\PaymentRoute;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Shopware\AppBundle\Interfaces\PaymentMethodInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentsGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws DOMElementCreationException
     * @throws \DOMException
     */
    public function generate(DOMDocument $document): DOMElement
    {
        $payments = $this->createElement($document, 'payments');

        $paymentMethods = $this->attributeReader->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            try {
                $element = $this->createElement($document, 'payment-method');
                /** @var PaymentMethodInterface $object */
                $object = $paymentMethod['object'];
                $identifier = $this->createElement($document, 'identifier', $object->getIdentifier());
                if ($object->getIconPath()) {
                    $icon = $this->createElement($document, 'icon', (string) $object->getIconPath());
                }
            } catch (DOMElementCreationException) {
                continue;
            }

            if (\array_key_exists('paymentRoute', $paymentMethod)) {
                /** @var PaymentRoute $paymentRoute */
                $paymentRoute = $paymentMethod['paymentRoute'];
                $url = $this->urlGenerator->generate($paymentRoute->getName(), [], UrlGeneratorInterface::ABSOLUTE_URL);

                $payUrl = $this->createElement($document, 'pay-url', $url);
            }

            if (\array_key_exists('paymentFinalizeRoute', $paymentMethod)) {
                /** @var PaymentFinalizeRoute $paymentFinalizeRoute */
                $paymentFinalizeRoute = $paymentMethod['paymentFinalizeRoute'];
                $url = $this->urlGenerator->generate(
                    $paymentFinalizeRoute->getName(),
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $finalizeUrl = $this->createElement($document, 'finalize-url', $url);
            }

            $element->appendChild($identifier);
            $element->append(...$this->getTranslatableElements($document, 'name', $object->getName()));
            $element->append(...$this->getTranslatableElements($document, 'description', $object->getDescription()));

            if (isset($payUrl)) {
                $element->appendChild($payUrl);
            }

            if (isset($finalizeUrl)) {
                $element->appendChild($finalizeUrl);
            }

            if (isset($icon)) {
                $element->appendChild($icon);
            }

            $payments->appendChild($element);
        }

        return $payments;
    }
}
