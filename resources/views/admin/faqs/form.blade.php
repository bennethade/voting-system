@php
    $editing = $faq->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit FAQ' : 'New FAQ'">
    <x-admin.page-header :title="$editing ? 'Edit FAQ' : 'New FAQ'">
        <x-slot:actions>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Question</h3>
                <x-input name="question" label="Question" :value="$faq->question" required maxlength="255" />
                <x-textarea name="answer" label="Answer" :value="$faq->answer" rows="8" required maxlength="5000" hint="Plain text. Line breaks are kept." />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-input name="category" label="Category" :value="$faq->category" list="faq-categories" maxlength="100" hint="Pick an existing category or type a new one." />
                <datalist id="faq-categories">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}"></option>
                    @endforeach
                </datalist>
                <x-input name="sort_order" type="number" min="0" label="Display order" :value="$faq->sort_order" hint="Lower numbers appear first." />
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$faq->status" required />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create FAQ' }}</button>
        </div>
    </form>
</x-layouts.admin>
