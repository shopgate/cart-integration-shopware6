<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\System\File\FileReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class LogController
{
    public function __construct(readonly private string $logDirectory, private readonly FileReader $fileReader)
    {
    }

    #[Route(path: '/api/shopgate/log', name: 'api.shopgate.logger.read', defaults: [
        '_routeScope' => ['api'],
        '_contextTokenRequired' => true,
    ], methods: [
        'GET'
    ])]
    public function read(Request $request): JsonResponse
    {
        if (!is_dir($this->logDirectory)) {
            return new JsonResponse(['error' => 'Log directory does not exist'], 404);
        }

        $lines = (int) $request->get('lines', 25);
        $sequence = $request->get('sequence');
        $file = $request->get('file', $this->fileReader->getLatestFile($this->logDirectory));

        // Sanitize file name
        $file = trim($file, '/');
        $file = preg_replace('/\/{2,}/', '/', $file);
        $file = preg_replace('/^\./', '', $file);
        $file = preg_replace('/\.\.\//', '', $file);

        $filePath = $this->logDirectory . '/' . $file;

        // Check if file is within the expected directory
        if (!str_starts_with($filePath, $this->logDirectory)) {
            return new JsonResponse(['error' => 'Invalid file name'], 400);
        }

        if ($sequence) {
            return new JsonResponse($this->fileReader->sequenceSearch($filePath, $sequence));
        }
        return new JsonResponse(
            explode("\n", $this->fileReader->tailFile($filePath, $lines))
        );
    }

    #[Route(path: '/api/shopgate/log/list', name: 'api.shopgate.logger.list', defaults: [
        '_routeScope' => ['api'],
        '_contextTokenRequired' => true,
    ], methods: [
        'GET'
    ])]
    public function list(): JsonResponse
    {
        if (!is_dir($this->logDirectory)) {
            return new JsonResponse(['error' => 'Log directory does not exist'], 404);
        }
        return new JsonResponse(
            $this->fileReader->getDirectoryFiles($this->logDirectory)
        );
    }
}
