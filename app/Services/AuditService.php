<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log an action on an entity.
     */
    public static function log(
        string  $action,
        string  $entityType,
        ?int    $entityId = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id'     => $request->user()?->id,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }

    /**
     * Log model creation.
     */
    public static function logCreated(Model $model, ?Request $request = null): AuditLog
    {
        return self::log(
            'created',
            class_basename($model),
            $model->getKey(),
            null,
            $model->toArray(),
            $request,
        );
    }

    /**
     * Log model update — records only changed attributes.
     */
    public static function logUpdated(Model $model, array $oldValues, ?Request $request = null): AuditLog
    {
        $changed = array_intersect_key($model->toArray(), $model->getChanges());

        return self::log(
            'updated',
            class_basename($model),
            $model->getKey(),
            array_intersect_key($oldValues, $changed),
            $changed,
            $request,
        );
    }

    /**
     * Log model deletion.
     */
    public static function logDeleted(Model $model, ?Request $request = null): AuditLog
    {
        return self::log(
            'deleted',
            class_basename($model),
            $model->getKey(),
            $model->toArray(),
            null,
            $request,
        );
    }
}
