<?php

namespace Shopgate\Shopware\Shopgate\Extended;

trait CloningTrait
{
    /**
     * @param array $data
     *
     * @return $this
     */
    protected function dataToEntity(array $data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . $this->snakeToCamel($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }

    /**
     * @param string $input
     * @return string
     */
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
