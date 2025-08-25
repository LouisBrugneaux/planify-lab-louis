<?php

namespace App\Controller;

use App\Service\PlanifyService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/planify')]
class PlanifyController
{

    public  function  __construct(private readonly PlanifyService $planifyService) {}

    #[Route('', name: 'planify', methods: ['POST'])]
    public function planifyLab(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $result  = $this->planifyService->planifyLab($payload);

        return new JsonResponse($result);
    }
}
