@props(['active' => false])

<a {{ $attributes }} class="{{ $active
    ? 'text-white bg-white/10'
    : 'text-slate-400 hover:text-white hover:bg-white/[0.06]'
}} rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150">
    {{ $slot }}
</a>
