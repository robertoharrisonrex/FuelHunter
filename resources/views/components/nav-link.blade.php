@props(['active' => false])

<a {{ $attributes }} class="{{ $active
    ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-semibold'
    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800'
}} rounded-lg px-3 py-2 text-sm transition-colors duration-150">
    {{ $slot }}
</a>
