@props(['name', 'label', 'current' => null, 'hint' => 'JPG, PNG or WebP.'])

<div x-data="{ preview: @js(media_url($current)) }">
    <label for="{{ $name }}" class="label">{{ $label }}</label>
    <div class="flex items-center gap-4">
        <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-xl border border-ink-100 bg-ink-50">
            <template x-if="preview"><img :src="preview" alt="" class="size-full object-cover"></template>
            <template x-if="!preview"><span class="text-xs text-ink-400">No image</span></template>
        </div>
        <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/jpeg,image/png,image/webp"
               @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)"
               class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-full file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-700">
    </div>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name) <p class="field-error">{{ $message }}</p> @enderror
</div>
