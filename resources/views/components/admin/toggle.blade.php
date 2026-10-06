@props(['name', 'label', 'checked' => false, 'hint' => null])

<label class="flex cursor-pointer items-start gap-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old(str_replace(['[', ']'], ['.', ''], $name), $checked)) class="peer sr-only">
    <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-ink-200 transition peer-checked:bg-gold-400 peer-focus-visible:ring-4 peer-focus-visible:ring-gold-200 after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span>
    <span>
        <span class="block text-sm font-medium text-ink-800">{{ $label }}</span>
        @if ($hint)<span class="block text-xs text-ink-500">{{ $hint }}</span>@endif
    </span>
</label>
