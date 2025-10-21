<?php

namespace App\Controller;

use App\Service\DatabaseConnection;
use App\Functions\StatusFunctions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusController extends AbstractController
{
    #[Route('/status', name: 'app_status')]
    public function index(DatabaseConnection $db, StatusFunctions $fx): Response
    {
        $pdo    = $db->getConnection();
        $latest = $fx->getLatest($pdo);

        // 60 Minuten Histore als Standard Laden
        //---------------------------------------------------
        $history = $fx->getHistory($pdo, '60min');

        return $this->render('status/index.html.twig', [
            'temperature' => $latest['temperature'] ?? null,
            'humidity'    => $latest['humidity']    ?? null,
            'pressure'    => $latest['pressure']    ?? null,
            'brightness'  => $latest['brightness']  ?? null,
            'lastUpdate'  => $latest['timestamp']   ?? null,
            'history'     => $history,
        ]);
    }

    // Live Daten als JSON
    //-----------------------------------------------------------------------------
    #[Route('/status/live', name: 'app_status_live')]
    public function live(DatabaseConnection $db, StatusFunctions $fx): JsonResponse
    {
        $latest = $fx->getLatest($db->getConnection());
        return $this->json($latest ?? [
            'temperature' => null,
            'humidity'    => null,
            'pressure'    => null,
            'brightness'  => null,
            'timestamp'   => null,
        ]);
    }
}
