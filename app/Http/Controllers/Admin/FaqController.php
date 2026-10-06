<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FaqController extends AdminController
{
    public function index(Request $request): View
    {
        $faqs = Faq::query()
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w->where('question', 'like', $term)->orWhere('answer', 'like', $term)))
            ->when($request->query('category'), fn ($q, $category) => $q->where('category', $category))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->tap(fn ($q) => $this->applySort($q, $request, ['question' => 'question', 'category' => 'category', 'sort_order' => 'sort_order', 'updated_at' => 'updated_at'], 'sort_order', 'asc'))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'categories' => $this->categories(),
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.form', [
            'faq' => new Faq(['status' => 'active', 'sort_order' => 0]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $faq = Faq::create($request->faqData());

        $this->audit->created($faq, 'Created FAQ "'.Str::limit($faq->question, 80).'"');

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.form', [
            'faq' => $faq,
            'categories' => $this->categories(),
        ]);
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->fill($request->faqData());

        $this->audit->updated($faq, 'Updated FAQ "'.Str::limit($faq->question, 80).'"');
        $faq->save();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->audit->deleted($faq, 'Deleted FAQ "'.Str::limit($faq->question, 80).'"');
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }

    /** @return array<string, string> */
    private function categories(): array
    {
        return Faq::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();
    }
}
