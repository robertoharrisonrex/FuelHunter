@props(['active' => false])

<a {{ $attributes }} class="{{ $active
    ? 'bg-indigo-50 text-indigo-600 font-semibold'
    : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
}} rounded-lg px-3 py-2 text-sm transition-colors duration-150">
    {{ $slot }}
</a>
