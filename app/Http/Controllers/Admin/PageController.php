<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends AdminController
{
    /** System page slugs that have their own short public route. */
    private const SYSTEM_ROUTES = [
        'about' => 'about',
        'terms' => 'terms',
        'privacy' => 'privacy',
        'refund-policy' => 'refund-policy',
    ];

    public function __construct(AuditLogger $audit, private readonly HtmlSanitizer $sanitizer)
    {
        parent::__construct($audit);
    }

    public function index(Request $request): View
    {
        $pages = Page::query()
            ->select(['id', 'title', 'slug', 'status', 'is_system', 'updated_at'])
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('slug', 'like', $term)))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->tap(fn ($q) => $this->applySort($q, $request, ['title' => 'title', 'updated_at' => 'updated_at'], 'title', 'asc'))
            ->paginate(20)
            ->withQueryString();

        $publicUrls = $pages->getCollection()->mapWithKeys(fn (Page $page) => [$page->id => $this->publicUrl($page)]);

        return view('admin.pages.index', compact('pages', 'publicUrls'));
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['status' => 'draft'])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = new Page($this->pageData($request));
        $page->save();

        $this->audit->created($page, "Created page {$page->title}");

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', [
            'page' => $page,
            'publicUrl' => $this->publicUrl($page),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->fill($this->pageData($request));

        $this->audit->updated($page, "Updated page {$page->title}");
        $page->save();

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->with('error', 'System pages are linked from the site layout and cannot be deleted. Set them to Draft to hide them instead.');
        }

        $this->audit->deleted($page, "Deleted page {$page->title}");
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function pageData(PageRequest $request): array
    {
        $data = $request->validated();
        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);

        return $data;
    }

    private function publicUrl(Page $page): string
    {
        $route = $page->is_system ? (self::SYSTEM_ROUTES[$page->slug] ?? null) : null;

        return $route ? route($route) : route('pages.show', $page->slug);
    }
}
