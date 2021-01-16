<?php

namespace Shopgate\Shopware\Components\Di;

use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Facade
{
    /**
     * self|null
     */
    private static $instance;

    /**
     * ContainerInterface
     */
    private static $myContainer;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        self::$myContainer = $container;
    }

    /**
     * @param string $serviceId
     *
     * @return object
     * @throws Exception
     */
    public static function create(string $serviceId): object
    {
        if (null === self::$instance) {
            throw new Exception("Facade is not instantiated");
        }

        return self::$myContainer->get($serviceId);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return null|Facade
     */
    public static function init(ContainerInterface $container): ?Facade
    {
        if (null === self::$instance) {
            self::$instance = new self($container);
        }

        return self::$instance;
    }
}
