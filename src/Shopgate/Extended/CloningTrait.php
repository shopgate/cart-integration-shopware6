<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

trait CloningTrait
{
    /**
     * @param array $data
     *
     * @return $this
     */
    protected function dataToEntity(array $data): self
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($this->snakeToCamel($key));
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }

    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
