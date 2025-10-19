<?php
namespace App\Functions;

use PDO;

class StatusFunctions
{
    /**
     * Letzte Messung des Raspberry Pi ausgeben
     */
    public function getLatest(PDO $pdo): ?array
    {
        $sql = "SELECT temperature, humidity, pressure, brightness, timestamp
                  FROM messwerte
              ORDER BY timestamp DESC
                 LIMIT 1";
        $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Allgemeine History je nach Range.
     * - 1h    -> 60 Minuten - Ein Punkt = Eine Minute
     * - 24h   -> 24 Stunden - Ein Punkt = Eine Stunde
     * - 7d    -> 7 Tage     - Ein Punkt = Ein Tag
     */
    public function getHistory(PDO $pdo, string $range): array
    {
        switch ($range) {
            case '24h':
                return $this->historyLast24hHours($pdo);
            case 'week':
                return $this->historyLast7dDays($pdo);
            case '60min':
            default:
                return $this->historyLastHourMinutes($pdo);
        }
    }

    /** Chartanzeige letzte 60min */
    private function historyLastHourMinutes(PDO $pdo): array
    {
        $sql = <<<SQL
            WITH RECURSIVE minutes AS (
                SELECT DATE_FORMAT(NOW() - INTERVAL 59 MINUTE, '%Y-%m-%d %H:%i:00') AS t, 0 AS n
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(t, INTERVAL 1 MINUTE), '%Y-%m-%d %H:%i:00'), n + 1
                FROM minutes
                WHERE n < 59
            )
            SELECT
                m.t AS timestamp,
                ROUND(AVG(w.temperature), 2) AS temperature,
                ROUND(AVG(w.humidity),    2) AS humidity,
                ROUND(AVG(w.pressure),    2) AS pressure,
                ROUND(AVG(w.brightness),  2) AS brightness
            FROM minutes m
            LEFT JOIN messwerte w
                ON DATE_FORMAT(w.timestamp, '%Y-%m-%d %H:%i:00') = m.t
            GROUP BY m.t
            ORDER BY m.t;
        SQL;

        return $this->runHistory($pdo, $sql);
    }

    /** Chartanzeige letzt 24h. */
    private function historyLast24hHours(PDO $pdo): array
    {
        $sql = <<<SQL
            WITH RECURSIVE hours AS (
                SELECT DATE_FORMAT(NOW() - INTERVAL 23 HOUR, '%Y-%m-%d %H:00:00') AS t, 0 AS n
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(t, INTERVAL 1 HOUR), '%Y-%m-%d %H:00:00'), n + 1
                FROM hours
                WHERE n < 23
            )
            SELECT
                h.t AS timestamp,
                ROUND(AVG(w.temperature), 2) AS temperature,
                ROUND(AVG(w.humidity),    2) AS humidity,
                ROUND(AVG(w.pressure),    2) AS pressure,
                ROUND(AVG(w.brightness),  2) AS brightness
            FROM hours h
            LEFT JOIN messwerte w
                ON DATE_FORMAT(w.timestamp, '%Y-%m-%d %H:00:00') = h.t
            GROUP BY h.t
            ORDER BY h.t;
        SQL;

        return $this->runHistory($pdo, $sql);
    }

    /** Chartanzeige letzte 7 Tage. */
    private function historyLast7dDays(PDO $pdo): array
    {
        $sql = <<<SQL
            WITH RECURSIVE days AS (
                SELECT DATE_FORMAT(CURDATE() - INTERVAL 6 DAY, '%Y-%m-%d 00:00:00') AS t, 0 AS n
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(t, INTERVAL 1 DAY), '%Y-%m-%d 00:00:00'), n + 1
                FROM days
                WHERE n < 6
            )
            SELECT
                d.t AS timestamp,
                ROUND(AVG(w.temperature), 2) AS temperature,
                ROUND(AVG(w.humidity),    2) AS humidity,
                ROUND(AVG(w.pressure),    2) AS pressure,
                ROUND(AVG(w.brightness),  2) AS brightness
            FROM days d
            LEFT JOIN messwerte w
                ON DATE_FORMAT(w.timestamp, '%Y-%m-%d 00:00:00') = d.t
            GROUP BY d.t
            ORDER BY d.t;
        SQL;

        return $this->runHistory($pdo, $sql);
    }

    /** Helfer: SQL ausführen und Zahlen sauber typisieren. */
    private function runHistory(PDO $pdo, string $sql): array
    {
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            foreach (['temperature','humidity','pressure','brightness'] as $k) {
                $r[$k] = $r[$k] !== null ? (float)$r[$k] : null;
            }
        }
        unset($r);
        return $rows;
    }
}
