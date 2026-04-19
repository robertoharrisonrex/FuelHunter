<div>
    @if($submitted)
        {{-- Success state --}}
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-1">Thanks for your feedback!</h3>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">We appreciate you taking the time to share your thoughts.</p>
            <button wire:click="$set('submitted', false)"
                    class="text-sm text-indigo-600 hover:text-indigo-500 font-semibold transition-colors">
                Send another message
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5" novalidate>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Name --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Your Name
                    </label>
                    <input type="text"
                           wire:model="name"
                           placeholder="Jane Smith"
                           class="w-full border rounded-xl px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-300
                                  dark:text-slate-100 dark:placeholder-slate-500
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                  transition-shadow duration-150
                                  @error('name') border-red-400 bg-red-50 @else border-gray-200 bg-gray-50 dark:bg-slate-800 dark:border-slate-600 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Email Address
                    </label>
                    <input type="email"
                           wire:model="email"
                           placeholder="jane@example.com"
                           class="w-full border rounded-xl px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-300
                                  dark:text-slate-100 dark:placeholder-slate-500
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                  transition-shadow duration-150
                                  @error('email') border-red-400 bg-red-50 @else border-gray-200 bg-gray-50 dark:bg-slate-800 dark:border-slate-600 @enderror">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Message
                </label>
                <textarea wire:model="message"
                          rows="5"
                          placeholder="Tell us what you think, report a bug, or suggest a feature…"
                          class="w-full border rounded-xl px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-300
                                 dark:text-slate-100 dark:placeholder-slate-500
                                 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                 transition-shadow duration-150 resize-none
                                 @error('message') border-red-400 bg-red-50 @else border-gray-200 bg-gray-50 dark:bg-slate-800 dark:border-slate-600 @enderror">
                </textarea>
                @error('message')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-400 dark:text-slate-500">Your feedback is genuinely appreciated — thank you for taking the time.</p>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95
                               text-white rounded-xl px-6 py-2.5 text-sm font-bold
                               shadow-[0_0_18px_rgba(99,102,241,0.35)]
                               hover:shadow-[0_0_26px_rgba(99,102,241,0.5)]
                               transition-all duration-200">
                    <span wire:loading.remove wire:target="submit">Send Message</span>
                    <span wire:loading wire:target="submit" style="display:none" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Sending…
                    </span>
                </button>
            </div>

        </form>
    @endif
</div>
