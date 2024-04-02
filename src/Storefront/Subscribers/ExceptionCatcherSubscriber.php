<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Subscribers;

use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateLibraryException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionCatcherSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ExceptionEvent::class => 'handleException'];
    }

    /**
     * Handles uncaught exceptions by SDK & prevents default
     * HTML output
     */
    public function handleException(ExceptionEvent $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }
        $error = $event->getThrowable();
        $event->allowCustomResponseCode();
        $event->setResponse(new JsonResponse([
            'error' => ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            'error_text' => $error->getMessage()
        ]));

        $this->logger->error('Uncaught error was thrown: ');
        $this->logger->error($error->getMessage());
        $this->logger->error($error->getTraceAsString());
    }
}
