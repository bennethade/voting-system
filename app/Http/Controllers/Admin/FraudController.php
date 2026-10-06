<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FraudStatus;
use App\Models\FraudFlag;
use App\Models\Vote;
use App\Services\FraudDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FraudController extends AdminController
{
    public function index(Request $request): View
    {
        $flags = FraudFlag::query()
            ->with(['awardEvent:id,name', 'nominee:id,name', 'reviewer:id,name'])
            ->when(FraudStatus::tryFrom((string) $request->query('status', 'open')), fn ($q, $s) => $q->where('status', $s->value))
            ->when(array_key_exists((string) $request->query('type'), FraudFlag::TYPES) ? $request->query('type') : null, fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('severity'), fn ($q, $s) => $q->where('severity', $s))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('description', 'like', $term)->orWhere('ip_address', 'like', $term)->orWhere('email', 'like', $term)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Live signals for the last 24 hours, independent of raised flags.
        $since = now()->subDay();
        $topIps = Vote::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->select('ip_address', DB::raw('COUNT(*) as entries'), DB::raw('COUNT(DISTINCT user_id) as accounts'), DB::raw('SUM(votes) as total'))
            ->groupBy('ip_address')
            ->orderByDesc('entries')
            ->limit(10)
            ->get();

        $counts = FraudFlag::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.fraud.index', compact('flags', 'topIps', 'counts'));
    }

    public function show(FraudFlag $fraudFlag, FraudDetectionService $fraud): View
    {
        $fraudFlag->load(['awardEvent', 'nominee', 'user', 'reviewer', 'flaggable']);
        $related = $fraud->relatedVotes($fraudFlag)->load(['nominee:id,name', 'user:id,name,email']);

        return view('admin.fraud.show', ['flag' => $fraudFlag, 'related' => $related]);
    }

    public function resolve(Request $request, FraudFlag $fraudFlag, FraudDetectionService $fraud): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([FraudStatus::Reviewing->value, FraudStatus::Approved->value, FraudStatus::Rejected->value, FraudStatus::Invalidated->value])],
            'notes' => [Rule::requiredIf($request->input('status') === FraudStatus::Invalidated->value), 'nullable', 'string', 'max:2000'],
        ]);

        $status = FraudStatus::from($data['status']);

        if ($status === FraudStatus::Invalidated && ! $request->user()->can('votes.invalidate')) {
            abort(403, 'You do not have permission to invalidate votes.');
        }

        $count = DB::transaction(fn () => $fraud->resolve($fraudFlag, $status, $request->user(), $data['notes'] ?? null));

        $message = "Flag marked as {$status->label()}.".($count ? " {$count} vote ".Str::plural('entry', $count).' invalidated.' : '');

        return redirect()->route('admin.fraud.show', $fraudFlag)->with('success', $message);
    }

    public function scan(FraudDetectionService $fraud): RedirectResponse
    {
        $count = $fraud->scan(24);
        $this->audit->log('fraud.scan', "Ran fraud scan: {$count} new flag(s)");

        return back()->with('success', "Scan complete: {$count} new flag(s) raised.");
    }
}
