<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogger
{
    /** Attributes that must never be written to the audit trail. */
    private const REDACTED = ['password', 'remember_token'];

    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 255),
            'old_values' => $old ? Arr::except($old, self::REDACTED) : null,
            'new_values' => $new ? Arr::except($new, self::REDACTED) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 512),
        ]);
    }

    public function created(Model $model, string $description): AuditLog
    {
        return $this->log(
            $this->actionName($model, 'created'),
            $description,
            $model,
            null,
            $model->attributesToArray(),
        );
    }

    /**
     * Call after `$model->fill(...)` and before `save()` so the dirty
     * attributes can be diffed; saving is left to the caller.
     */
    public function updated(Model $model, string $description): ?AuditLog
    {
        $dirty = Arr::except($model->getDirty(), ['updated_at']);

        if ($dirty === []) {
            return null;
        }

        return $this->log(
            $this->actionName($model, 'updated'),
            $description,
            $model,
            array_intersect_key($model->getOriginal(), $dirty),
            $dirty,
        );
    }

    public function deleted(Model $model, string $description): AuditLog
    {
        return $this->log($this->actionName($model, 'deleted'), $description, $model, $model->attributesToArray());
    }

    private function actionName(Model $model, string $verb): string
    {
        return $model->getMorphClass().'.'.$verb;
    }
}
