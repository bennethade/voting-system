@php
    $editing = $package->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$package->name : 'New voting package'">
    <x-admin.page-header :title="$editing ? 'Edit voting package' : 'New voting package'">
        <x-slot:actions>
            <a href="{{ route('admin.voting-packages.index', array_filter(['event' => $package->award_event_id])) }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.voting-packages.update', $package) : route('admin.voting-packages.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Package</h3>
                <x-select name="award_event_id" label="Award event" :options="$events" :value="$package->award_event_id" placeholder="Select an event" required />
                <x-input name="name" label="Package name" :value="$package->name" required maxlength="100" placeholder="e.g. Gold bundle" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="amount" type="number" step="0.01" min="1" label="Price" :value="$package->amount" required hint="In the event's currency, e.g. 1000 or 1000.50." />
                    <x-input name="votes" type="number" min="1" max="1000000" label="Votes included" :value="$package->votes" required />
                </div>
                <x-input name="description" label="Short description" :value="$package->description" maxlength="255" />
                <p class="hint">Changing the price or number of votes only affects future purchases. Existing orders keep the amount and votes they were bought with.</p>
                @if ($hasOrders ?? false)
                    <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">This package already has orders, so it cannot be deleted. Set it to Inactive to stop selling it.</p>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$package->status" required hint="Inactive packages are hidden from voters." />
                <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$package->sort_order" hint="Lower numbers are shown first." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create package' }}</button>
        </div>
    </form>
</x-layouts.admin>
