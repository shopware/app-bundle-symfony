<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use App\Attribute\ConfirmationRoute;
use App\Attribute\RegistrationRoute;
use Exception;
use ReflectionObject;
use Shopware\AppBundle\Attribute\ActionButton;
use Shopware\AppBundle\Attribute\MainModule;
use Shopware\AppBundle\Attribute\Module;
use Shopware\AppBundle\Attribute\PaymentFinalizeRoute;
use Shopware\AppBundle\Attribute\PaymentRoute;
use Shopware\AppBundle\Attribute\Webhook;
use Shopware\AppBundle\Interfaces\PaymentMethodInterface;

class AttributeReader
{
    private bool $initializedRoutes = false;

    private bool $initializedPaymentMethods = false;

    private ?RegistrationRoute $registrationRoute = null;

    private ?ConfirmationRoute $confirmationRoute = null;

    /**
     * @var array<Webhook>
     */
    private array $webhooks = [];

    /**
     * @var array<Module>
     */
    private array $modules = [];

    private ?MainModule $mainModule = null;

    /**
     * @var array<ActionButton>
     */
    private array $actionButtons = [];

    private array $payments = [];

    public function __construct(
        private iterable $abstractControllers,
        private iterable $paymentMethods
    ) {
    }

    public function getRegistrationRoute(): ?RegistrationRoute
    {
        $this->extractRoutes();

        return $this->registrationRoute;
    }

    public function getConfirmationRoute(): ?ConfirmationRoute
    {
        $this->extractRoutes();

        return $this->confirmationRoute;
    }

    /**
     * @return Webhook[]
     */
    public function getWebhooks(): array
    {
        $this->extractRoutes();

        return $this->webhooks;
    }

    public function getModules(): array
    {
        $this->extractRoutes();

        return $this->modules;
    }

    public function getMainModule(): ?MainModule
    {
        $this->extractRoutes();

        return $this->mainModule;
    }

    /**
     * @return array<ActionButton>
     */
    public function getActionButtons(): array
    {
        $this->extractRoutes();

        return $this->actionButtons;
    }

    public function getPaymentMethods(): array
    {
        $this->extractPaymentMethods();

        return $this->payments;
    }

    private function extractRoutes(): void
    {
        if ($this->initializedRoutes) {
            return;
        }

        foreach ($this->abstractControllers as $abstractController) {
            $reflectionObject = new ReflectionObject($abstractController);

            foreach ($reflectionObject->getMethods() as $reflectionMethod) {
                foreach ($reflectionMethod->getAttributes() as $attribute) {
                    switch ($attribute->getName()) {
                        case RegistrationRoute::class:
                            if ($this->registrationRoute !== null) {
                                throw new Exception('Duplicated registration route');
                            }
                            $this->registrationRoute = $attribute->newInstance();
                            break;
                        case ConfirmationRoute::class:
                            if ($this->confirmationRoute !== null) {
                                throw new Exception('Duplicated confirmation route');
                            }
                            $this->confirmationRoute = $attribute->newInstance();
                            break;
                        case Webhook::class:
                            $this->webhooks[] = $attribute->newInstance();
                            break;
                        case Module::class:
                            $this->modules[] = $attribute->newInstance();
                            break;
                        case MainModule::class:
                            if ($this->mainModule !== null) {
                                throw new Exception('Duplicated main-module route');
                            }
                            $this->mainModule = $attribute->newInstance();
                            break;
                        case ActionButton::class:
                            $this->actionButtons[] = $attribute->newInstance();
                            break;
                    }
                }
            }
        }

        $this->initializedRoutes = true;
    }

    private function extractPaymentMethods(): void
    {
        if ($this->initializedPaymentMethods) {
            return;
        }

        /** @var PaymentMethodInterface $paymentMethod */
        foreach ($this->paymentMethods as $paymentMethod) {
            $this->payments[$paymentMethod->getIdentifier()]['object'] = $paymentMethod;

            $reflectionObject = new ReflectionObject($paymentMethod);

            foreach ($reflectionObject->getMethods() as $reflectionMethod) {
                foreach ($reflectionMethod->getAttributes() as $attribute) {
                    switch ($attribute->getName()) {
                        case PaymentRoute::class:
                            if (\array_key_exists('paymentRoute', $this->payments[$paymentMethod->getIdentifier()])) {
                                throw new Exception('Duplicated payment routes.');
                            }

                            $this->payments[$paymentMethod->getIdentifier()]['paymentRoute'] = $attribute->newInstance();
                            break;
                        case PaymentFinalizeRoute::class:
                            if (\array_key_exists('paymentFinalizeRoute', $this->payments[$paymentMethod->getIdentifier()])) {
                                throw new Exception('Duplicated payment finalize routes.');
                            }

                            $this->payments[$paymentMethod->getIdentifier()]['paymentFinalizeRoute'] = $attribute->newInstance();
                            break;
                    }
                }
            }
        }
    }
}
