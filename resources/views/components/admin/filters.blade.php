{{--
    Standard GET filter bar for admin listings. Put filter fields in the slot;
    the search box and date range are opt-in via props.
--}}
@props(['search' => true, 'dates' => false, 'placeholder' => 'Search…'])

<form method="GET" {{ $attributes->class('card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end') }}>
    @if ($search)
        <div class="lg:col-span-2">
            <label for="q" class="label text-xs">Search</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="input">
        </div>
    @endif
    {{ $slot }}
    @if ($dates)
        <div>
            <label for="from" class="label text-xs">From</label>
            <input id="from" type="date" name="from" value="{{ request('from') }}" class="input">
        </div>
        <div>
            <label for="to" class="label text-xs">To</label>
            <input id="to" type="date" name="to" value="{{ request('to') }}" class="input">
        </div>
    @endif
    <div class="flex gap-2">
        <button class="btn btn-ink flex-1">Filter</button>
        @if (collect(request()->except('page'))->filter()->isNotEmpty())
            <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
        @endif
    </div>
</form>
