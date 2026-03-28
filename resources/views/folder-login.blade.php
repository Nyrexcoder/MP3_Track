<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-gray-800 shadow-md overflow-hidden sm:rounded-lg border border-gray-700">
            <div class="mb-4 text-center">
                <h2 class="text-2xl font-bold text-white">🔒 Private Folder</h2>
                <p class="text-gray-400 mt-2">This folder is password protected. Please enter the password to view its contents.</p>
            </div>

            <form method="POST" action="{{ route('folder.unlock', $currentFolder->id) }}">
                @csrf

                <div>
                    <x-input-label for="password" value="Folder Password" class="text-gray-300" />
                    <x-text-input id="password" class="block mt-1 w-full bg-gray-700 border-gray-600 text-white"
                                    type="password"
                                    name="password"
                                    required autofocus />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white mr-4">Back to Dashboard</a>
                    <x-primary-button class="bg-indigo-600 hover:bg-indigo-500">
                        Unlock Folder
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
