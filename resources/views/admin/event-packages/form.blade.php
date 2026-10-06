@php
    $editing = $package->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$package->name : 'New table package'">
    <x-admin.page-header :title="$editing ? 'Edit table package' : 'New table package'">
        <x-slot:actions>
            <a href="{{ route('admin.event-packages.index', array_filter(['event' => $package->award_event_id])) }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.event-packages.update', $package) : route('admin.event-packages.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Package</h3>
                <x-select name="award_event_id" label="Award event" :options="$events" :value="$package->award_event_id" placeholder="Select an event" required />
                <x-input name="name" label="Package name" :value="$package->name" required maxlength="150" placeholder="e.g. VIP table" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="price" type="number" step="0.01" min="0" label="Price" :value="$package->price" required hint="In the event's currency." />
                    <x-input name="seats" type="number" min="1" max="1000" label="Seats per package" :value="$package->seats" required />
                </div>
                <x-textarea name="description" label="Description" :value="$package->description" rows="4" />

                <div>
                    <x-textarea name="benefits_text" label="Benefits" :value="implode(PHP_EOL, $package->benefits ?? [])" rows="6" hint="One benefit per line (up to 30)." />
                    @foreach ($errors->get('benefits*') as $messages)
                        @foreach ((array) $messages as $message)
                            <p class="field-error">{{ $message }}</p>
                        @endforeach
                    @endforeach
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Availability</h3>
                <x-input name="quantity_available" type="number" min="{{ $package->quantity_sold ?? 0 }}" max="100000" label="Quantity available" :value="$package->quantity_available" required hint="Total number of this package that can be sold." />
                @if ($editing)
                    <div>
                        <p class="label">Sold</p>
                        <p class="font-display text-2xl font-semibold tabular-nums text-ink-900">{{ number_format($package->quantity_sold) }} <span class="text-base font-normal text-ink-400">/ {{ number_format($package->quantity_available) }}</span></p>
                        <p class="hint">Read-only. Updated automatically when bookings are paid.</p>
                    </div>
                @endif
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$package->status" required hint="Inactive packages cannot be booked." />
                <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$package->sort_order" hint="Lower numbers are shown first." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create package' }}</button>
        </div>
    </form>
</x-layouts.admin>
