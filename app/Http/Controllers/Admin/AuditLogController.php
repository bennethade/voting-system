<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only view of the audit trail. Entries are never edited or deleted. */
class AuditLogController extends AdminController
{
    public function index(Request $request): View
    {
        $action = trim((string) $request->query('action'));

        $query = AuditLog::query()
            ->with('user:id,name')
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w->where('description', 'like', $term)->orWhere('action', 'like', $term)))
            // Matches an exact action ("payment.refunded") or a whole group ("payment").
            ->when($action !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', $action)
                ->orWhere('action', 'like', addcslashes($action, '%_\\').'.%')))
            ->when($request->integer('user'), fn ($q, $userId) => $q->where('user_id', $userId));

        $this->applyDateRange($query, $request);

        $logs = $this->applySort($query, $request, ['created_at' => 'created_at', 'action' => 'action'])
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => $this->actionOptions(),
            'staff' => User::query()->staff()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Distinct actions grouped by prefix: "award_event" (all) followed by
     * "award_event.created", "award_event.updated", ...
     *
     * @return array<string, string>
     */
    private function actionOptions(): array
    {
        $options = [];

        AuditLog::query()->distinct()->orderBy('action')->pluck('action')
            ->groupBy(fn (string $action) => explode('.', $action, 2)[0])
            ->each(function ($actions, string $prefix) use (&$options) {
                if ($actions->count() > 1 || ! $actions->contains($prefix)) {
                    $options[$prefix] = $prefix.' (all)';
                }
                foreach ($actions as $action) {
                    $options[$action] = $action === $prefix ? $action : '— '.$action;
                }
            });

        return $options;
    }
}
