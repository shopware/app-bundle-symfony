<?php declare(strict_types=1);

namespace Shopware\AppBundle;

trait ArrayAssignableTrait
{
    /**
     * @return $this
     */
    public function assign(array $options)
    {
        foreach ($options as $key => $value) {
            try {
                $this->$key = $value;
            } catch (\Error | \Exception $error) {
                // nth
            }
        }

        return $this;
    }
}
