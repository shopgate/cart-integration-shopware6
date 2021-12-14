<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use RuntimeException;
use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate_Helper_DataStructure;
use Shopware\Core\Framework\Api\Controller\SyncController;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

abstract class EntityInstaller
{
    protected array $entityInstallList = [];
    protected string $entityName;
    protected SyncController $syncController;
    protected RequestStack $requestStack;
    /** @var EntityRepositoryInterface */
    protected $entityRepo;

    public function __construct(ContainerInterface $container)
    {
        $this->entityRepo = $container->get($this->entityName . '.repository');
        $this->syncController = $container->get(SyncController::class);
        $this->requestStack = $container->get('request_stack');
    }

    /**
     * @throws Throwable
     */
    public function install(InstallContext $context): void
    {
        foreach ($this->getEntities() as $method) {
            $this->upsertEntity($method, $context->getContext());
        }
    }

    /**
     * @return ClassCastInterface[]
     */
    protected function getEntities(): array
    {
        return array_map(static function (string $method) {
            return new $method();
        }, $this->entityInstallList);
    }

    /**
     * @throws Throwable
     */
    protected function upsertEntity(ClassCastInterface $entity, Context $context): void
    {
        $payload = [
            [
                'action' => 'upsert',
                'entity' => $this->entityName,
                'payload' => [$entity->toArray()],
            ]
        ];

        $this->syncPayload($payload, $context);
    }

    /**
     * @throws Throwable
     */
    protected function syncPayload(array $payload, Context $context): void
    {
        $jsonHelper = new Shopgate_Helper_DataStructure();
        $request = new Request([], [], [], [], [], [], $jsonHelper->jsonEncode($payload));
        $this->requestStack->push($request);
        $response = $this->syncController->sync($request, $context);
        $this->requestStack->pop();
        $result = $jsonHelper->jsonDecode($response->getContent(), true);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf('Error initializing: %s', print_r($result, true)));
        }
    }
}
