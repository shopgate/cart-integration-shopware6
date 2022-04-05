<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use ShopgateLogger;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class Logger implements LoggerInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function debug($info): void
    {
        if (!is_scalar($info)) {
            $info = $this->serializer->serialize($info, 'json', $this->getSerializerContext());
        }
        ShopgateLogger::getInstance()->log($info, ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @inheritdoc
     */
    public function error($error): void
    {
        if (!is_scalar($error)) {
            $error = $this->serializer->serialize($error, 'json', $this->getSerializerContext());
        }
        ShopgateLogger::getInstance()->log($error);
    }

    /**
     * Helps to handle circular references.
     */
    private function getSerializerContext(): array
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        return [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return get_class($object);
            },
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['session'],
            JsonEncode::OPTIONS => JSON_PARTIAL_OUTPUT_ON_ERROR
        ];
    }
}
