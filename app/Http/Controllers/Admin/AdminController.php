<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared helpers for admin listings: date filters, whitelisted sorting and
 * streamed CSV exports. Authorization is enforced by route middleware
 * ('staff' + 'can:<permission>'), see routes/admin.php.
 */
abstract class AdminController extends Controller
{
    public function __construct(protected readonly AuditLogger $audit) {}

    protected function applyDateRange(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where($column, '<=', $to->endOfDay()));
    }

    /**
     * Sort by a whitelisted column only (never raw user input in ORDER BY).
     *
     * @param  array<string, string>  $allowed  request key => column
     */
    protected function applySort(Builder $query, Request $request, array $allowed, string $default = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $key = (string) $request->query('sort');
        $column = $allowed[$key] ?? $default;
        $direction = $request->query('direction') === 'asc' ? 'asc' : (array_key_exists($key, $allowed) ? 'desc' : $defaultDirection);

        return $query->orderBy($column, $direction);
    }

    /** Escape LIKE wildcards in user search input. */
    protected function like(?string $term): ?string
    {
        $term = trim((string) $term);

        return $term === '' ? null : '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * Stream a CSV (opens in Excel) without loading every row into memory.
     *
     * @param  array<int, string>  $headings
     * @param  callable(): iterable<array<int, mixed>>  $rows
     */
    protected function csv(string $filename, array $headings, callable $rows): StreamedResponse
    {
        $this->audit->log('export.csv', "Exported {$filename}");

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₦ etc. correctly
            fputcsv($out, $headings);

            foreach ($rows() as $row) {
                fputcsv($out, array_map(fn ($v) => $this->csvSafe($v), $row));
            }

            fclose($out);
        }, $filename.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise spreadsheet formula injection (=, +, -, @ at the start of a cell). */
    private function csvSafe(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
