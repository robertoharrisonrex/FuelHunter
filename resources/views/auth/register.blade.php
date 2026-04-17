<x-layout>
    <x-slot:heading>Create Account</x-slot:heading>

    <div class="max-w-md mx-auto">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            <h2 class="text-lg font-bold text-slate-900 mb-6">Account Details</h2>

            <form id="register" method="POST" action="/register" class="space-y-5">
                @csrf

                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                    <input type="text" name="first_name" id="first_name"
                           class="block w-full rounded-lg border border-slate-300 bg-white
                                  py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                  transition-shadow duration-150">
                    @error('first_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" id="last_name"
                           class="block w-full rounded-lg border border-slate-300 bg-white
                                  py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                  transition-shadow duration-150">
                    @error('last_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="email"
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

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="block w-full rounded-lg border border-slate-300 bg-white
                                  py-2.5 px-3 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                  transition-shadow duration-150">
                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-4 pt-2">
                    <a href="/" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Cancel</a>
                    <button type="submit"
                            class="flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-500
                                   text-white rounded-xl px-6 py-2.5 text-sm font-bold
                                   active:scale-95 transition-all duration-200">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layout>
