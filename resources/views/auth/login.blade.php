<x-layout>
    <x-slot:heading>Login</x-slot:heading>

    <div class="max-w-md mx-auto">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <h2 class="text-lg font-bold text-slate-900 mb-6">Sign in to your account</h2>

            <form method="POST" action="/login" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="block w-full rounded-lg border border-slate-300 bg-white
                                  py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                  transition-shadow duration-150">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password"
                           class="block w-full rounded-lg border border-slate-300 bg-white
                                  py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                  transition-shadow duration-150">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-500
                               text-white rounded-xl px-5 py-2.5 text-sm font-bold
                               active:scale-95 transition-all duration-200 mt-2">
                    Sign In
                </button>
            </form>
        </div>
    </div>

</x-layout>
