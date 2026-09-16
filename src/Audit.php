<?php
declare(strict_types=1);

namespace SouqLink;

use PDO;

final class Audit
{
    public static function log(PDO $db, ?int $actorId, string $action, string $entityType, ?int $entityId, ?array $before = null, ?array $after = null): void
    {
        $statement = $db->prepare('INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, before_json, after_json, ip_address) VALUES (:actor, :action, :type, :entity, :before, :after, :ip)');
        $statement->execute([
            'actor' => $actorId,
            'action' => $action,
            'type' => $entityType,
            'entity' => $entityId,
            'before' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            'after' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
