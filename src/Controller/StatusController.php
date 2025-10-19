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

        // Default: 60min-Ansicht initial einbetten
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

    /**
     * Live-Werte für die Kacheln (wird alle 2s vom Frontend gepollt).
     */
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

    /**
     * Historie per Range (1min|60min|24h|week)
     * Beispiel: /status/history?range=24h
     */
    #[Route('/status/history', name: 'app_status_history')]
    public function history(Request $req, DatabaseConnection $db, StatusFunctions $fx): JsonResponse
    {
        $range   = $req->query->get('range', '60min');
        $allowed = ['1min','60min','24h','week'];
        if (!in_array($range, $allowed, true)) {
            $range = '60min';
        }

        $rows = $fx->getHistory($db->getConnection(), $range);
        return $this->json([
            'range'   => $range,
            'history' => $rows,
        ]);
    }
}
