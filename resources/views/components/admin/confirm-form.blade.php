{{--
    A small POST form with a JS confirm() guard, for destructive one-click
    actions (delete, deactivate…). Server-side authorization still applies.
--}}
@props(['action', 'method' => 'POST', 'confirm' => 'Are you sure?', 'label' => 'Delete', 'class' => 'text-rose-600 hover:text-rose-800'])

<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm))">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
    <button type="submit" class="text-sm font-medium {{ $class }}">{{ $label }}</button>
</form>
