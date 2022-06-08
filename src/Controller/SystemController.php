<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class SystemController extends AbstractController
{
    /**
     * Healthcheck for the app.
     *
     * @Route("/api/healthcheck", methods={"GET"})
     * @OA\Response(
     *     response=204,
     *     description="Returns empty result and 204 code."
     * )
     * @OA\Tag(name="healthcheck")
     */
    public function healthcheck(LoggerInterface $monologLogger): JsonResponse
    {
        $monologLogger->info('Healthcheck page response: ' . Response::HTTP_NO_CONTENT);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
