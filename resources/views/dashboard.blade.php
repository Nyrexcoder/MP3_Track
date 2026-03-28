<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <h2 class="font-bold text-2xl text-white leading-tight tracking-tight">
                    {{ $currentFolder ? "📁 {$currentFolder->name}" : __('MP3 Player') }}
                </h2>
                <!-- Queue Status Badge -->
                <div id="queue-status" class="{{ ($pendingJobs ?? 0) > 0 ? '' : 'hidden' }} flex items-center bg-indigo-500/20 border border-indigo-500/30 px-4 py-2 rounded-xl">
                    <div class="relative flex h-2 w-2 mr-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </div>
                    <span class="text-xs font-medium text-indigo-300">
                        Processing <span id="queue-count" class="font-bold">{{ $pendingJobs ?? 0 }}</span> files...
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                @if($currentFolder)
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white rounded-xl text-sm font-bold transition-all border border-white/10">← Back</a>
                @endif
                
                <!-- Bulk Actions Menu (Hidden by default) -->
                <div id="bulk-actions-menu" class="hidden flex items-center space-x-2 bg-white/5 p-1 rounded-xl border border-white/10">
                    <span class="text-xs text-gray-400 px-2 leading-none"><span id="selected-count">0</span> selected</span>
                    <select id="bulk-folder-select" class="bg-gray-800 text-white text-xs border-none rounded-lg focus:ring-0 py-1 leading-none h-8">
                        <option value="">Move to...</option>
                        @foreach(\App\Models\Folder::where('user_id', auth()->id())->get() as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                    <button onclick="handleBulkAction('move')" class="p-2 text-indigo-400 hover:bg-indigo-500/20 rounded-lg transition-all" title="Move selected">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    </button>
                    <button onclick="handleBulkAction('delete')" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition-all" title="Delete selected">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white rounded-xl text-sm font-bold transition-all border border-white/10">Logout</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Actions -->
                <div class="space-y-8">
                    <!-- Upload Section -->
                    <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700 overflow-hidden shadow-2xl sm:rounded-2xl p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="bg-indigo-600 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white uppercase tracking-wider">Upload Tracks</h3>
                        </div>

                        <form id="upload-form" action="{{ route('mp3.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="folder_id" value="{{ $currentFolder?->id }}">
                            
                            <div class="flex items-center justify-center space-x-4 mb-2">
                                <button type="button" onclick="document.getElementById('files').click()" class="flex-1 bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-xl py-3 text-xs font-bold text-gray-300 uppercase tracking-widest transition-all">
                                    🎵 Pick Files
                                </button>
                                <button type="button" onclick="document.getElementById('folder-input').click()" class="flex-1 bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-xl py-3 text-xs font-bold text-gray-300 uppercase tracking-widest transition-all">
                                    📁 Pick Folder
                                </button>
                            </div>

                            <div class="relative group">
                                <!-- Hidden inputs for Files and Folder -->
                                <input id="files" name="files[]" type="file" accept=".mp3,.wav,.ogg" class="hidden" required multiple onchange="updateFileNames(this)" />
                                <input id="folder-input" name="files[]" type="file" class="hidden" webkitdirectory mozdirectory msdirectory odirectory directory onchange="updateFileNames(this)" />
                                
                                <div class="flex flex-col items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-600 rounded-xl bg-gray-700/10 text-center">
                                    <svg class="w-8 h-8 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span id="file-count" class="text-gray-400 text-sm font-medium">Nothing selected</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Track Visibility</label>
                                    <select name="visibility" class="w-full bg-gray-700 border-gray-600 text-white rounded-xl text-sm" onchange="toggleTrackPassword(this.value)">
                                        <option value="public">🌍 Public</option>
                                        <option value="private">🔒 Private</option>
                                    </select>
                                </div>
                                <div id="track-password-container" class="hidden">
                                    <input id="track-password" name="password" type="password" class="w-full bg-gray-700 border-gray-600 text-white rounded-xl text-sm" placeholder="Password for these tracks" />
                                </div>
                            </div>

                            <div id="queue-container" class="space-y-3 hidden">
                                <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Upload Progress</p>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div id="upload-progress-bar" class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <p id="upload-progress-text" class="text-[10px] text-gray-400 text-center font-bold">0% Complete</p>
                            </div>
                            
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-bold uppercase tracking-widest shadow-lg shadow-indigo-500/20 transition-all transform hover:scale-[1.02] active:scale-95">
                                Add to Queue
                            </button>
                        </form>
                    </div>

                    <!-- Create Folder Section -->
                    @if(!$currentFolder)
                    <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700 overflow-hidden shadow-2xl sm:rounded-2xl p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="bg-purple-600 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white uppercase tracking-wider">New Folder</h3>
                        </div>

                        <form action="{{ route('folder.create') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Folder Name</label>
                                <input name="name" type="text" class="w-full bg-gray-700 border-gray-600 text-white rounded-xl text-sm" placeholder="e.g. My Favorites" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Folder Privacy</label>
                                <select name="visibility" class="w-full bg-gray-700 border-gray-600 text-white rounded-xl text-sm" onchange="toggleFolderPassword(this.value)">
                                    <option value="public">🌍 Public</option>
                                    <option value="private">🔒 Private</option>
                                </select>
                            </div>
                            <div id="folder-password-container" class="hidden">
                                <input id="folder-password" name="password" type="password" class="w-full bg-gray-700 border-gray-600 text-white rounded-xl text-sm" placeholder="Folder Password" />
                            </div>
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white py-3 rounded-xl font-bold uppercase tracking-widest shadow-lg shadow-purple-500/20 transition-all transform hover:scale-[1.02]">
                                Create Folder
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <!-- Right Column: Folders and Files -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Folders Grid -->
                    @if(!$currentFolder && count($folders) > 0)
                    <div class="space-y-4">
                        <h3 class="text-xl font-bold text-white uppercase tracking-widest pl-2">Your Folders</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($folders as $folder)
                            <div class="group relative bg-gray-800/40 hover:bg-gray-800/80 border border-gray-700/50 rounded-2xl p-5 transition-all duration-300 hover:shadow-2xl">
                                <div class="flex items-center space-x-4">
                                    <input type="checkbox" name="selected_folders[]" value="{{ $folder->id }}" class="folder-checkbox rounded border-gray-600 bg-gray-700 text-purple-500 focus:ring-purple-500/50" onchange="updateSelectedCount()">
                                    <a href="{{ route('dashboard', ['folder' => $folder->id]) }}" class="flex items-center space-x-4 flex-1">
                                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <svg class="w-6 h-6 text-purple-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-white font-bold truncate">{{ $folder->name }}</h4>
                                            <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-tighter">
                                                <span class="{{ $folder->visibility === 'public' ? 'text-green-400' : 'text-amber-400' }}">{{ $folder->visibility }}</span>
                                                <span class="text-gray-600">•</span>
                                                <span class="text-gray-500">{{ $folder->mp3_files_count ?? count($folder->mp3Files) }} tracks</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <form action="{{ route('folder.destroy', $folder->id) }}" method="POST" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-400" onclick="return confirm('Delete folder and all its music?')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Files List -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pl-2">
                            <h3 class="text-xl font-bold text-white uppercase tracking-widest">Tracks {{ $currentFolder ? "in {$currentFolder->name}" : "" }}</h3>
                            <div class="flex items-center space-x-2">
                                <label class="text-xs text-gray-500 font-bold uppercase cursor-pointer flex items-center space-x-2">
                                    <input type="checkbox" id="select-all-files" class="rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500/50" onclick="toggleSelectAll(this)">
                                    <span>Select All</span>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-3">
                            @forelse($files as $file)
                                <div class="group bg-gray-800/40 hover:bg-gray-800/80 backdrop-blur-md border border-gray-700/50 rounded-2xl p-4 flex items-center justify-between transition-all duration-300 hover:-translate-y-0.5">
                                    <div class="flex items-center space-x-4 flex-1 min-w-0">
                                        <input type="checkbox" name="selected_files[]" value="{{ $file->id }}" class="file-checkbox rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500/50" onchange="updateSelectedCount()">
                                        <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center group-hover:bg-indigo-500 transition-colors duration-500">
                                            <svg class="w-6 h-6 text-indigo-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-white font-bold truncate">{{ $file->title }}</h4>
                                            <p class="text-gray-500 text-xs font-medium">{{ $file->artist }} • {{ number_format($file->size / 1024 / 1024, 2) }} MB</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="playFile({{ $file->id }}, '{{ $file->visibility }}', '{{ addslashes($file->title) }}')" class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-400 hover:text-white p-3 rounded-full transition-all">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                                        </button>
                                        <form action="{{ route('mp3.destroy', $file->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-600 hover:text-red-400 p-2" onclick="return confirm('Delete track?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12 bg-gray-800/20 rounded-2xl border-2 border-dashed border-gray-700">
                                    <p class="text-gray-500 font-medium italic">This area is empty.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-[100] flex flex-col gap-3 pointer-events-none"></div>

    <!-- Persistent Player -->
    <div id="player-container" class="fixed bottom-0 left-0 right-0 bg-gray-900/95 backdrop-blur-2xl border-t border-white/10 p-6 hidden z-50 animate-slide-up shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
        <div class="max-w-7xl mx-auto flex items-center space-x-8">
            <!-- Title & Info (Desktop) -->
            <div class="hidden md:flex items-center space-x-4 w-1/4">
                <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center animate-pulse">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                </div>
                <div class="min-w-0">
                    <p id="player-title" class="text-white font-bold truncate">Song Title</p>
                    <p class="text-indigo-400 text-[10px] font-bold uppercase tracking-widest">Streaming from MinIO</p>
                </div>
            </div>

            <!-- Audio Control -->
            <div class="flex-1 w-full">
                <audio id="audio-player" controls class="w-full h-10"></audio>
            </div>

            <!-- Close Button -->
            <div class="flex items-center">
                <button onclick="closePlayer()" class="text-gray-500 hover:text-white transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <style>
        .animate-slide-up { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .animate-toast-in { animation: toastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .animate-toast-out { animation: toastOut 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        @keyframes toastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes toastOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    </style>

    <script>
        let lastQueueCount = {{ $pendingJobs ?? 0 }};
        let queuePollingInterval = null;
        let lastCheckTimestamp = {{ time() }};

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-indigo-600' : 'bg-red-600';
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center space-x-3 pointer-events-auto min-w-[300px] animate-toast-in`;
            toast.innerHTML = `
                <div class="bg-white/20 p-1.5 rounded-lg">${icon}</div>
                <div class="flex-1 font-bold text-sm">${message}</div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.replace('animate-toast-in', 'animate-toast-out');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        async function checkQueueStatus() {
            try {
                const response = await fetch(`{{ route('dashboard.queue-status') }}?last_check=${lastCheckTimestamp}`);
                const data = await response.json();
                const statusEl = document.getElementById('queue-status');
                const countEl = document.getElementById('queue-count');

                if (data.count > 0) {
                    statusEl.classList.remove('hidden');
                    countEl.textContent = data.count;
                    
                    // If count decreased, something was processed
                    if (data.count < lastQueueCount) {
                        const processedCount = lastQueueCount - data.count;
                        // Show toast for new files
                        if (data.new_files && data.new_files.length > 0) {
                            data.new_files.forEach(file => {
                                showToast(`Processed: ${file.title}`);
                            });
                        } else {
                            showToast(`${processedCount} file(s) processed!`);
                        }
                    }
                } else {
                    // If it was processing and now it's 0, reload to show new files
                    if (lastQueueCount > 0) {
                        statusEl.classList.add('hidden');
                        showToast('All files processed successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        statusEl.classList.add('hidden');
                    }
                    stopPolling();
                }

                lastQueueCount = data.count;
                lastCheckTimestamp = data.timestamp;
            } catch (error) {
                console.error('Queue status check failed:', error);
            }
        }

        function startPolling() {
            if (!queuePollingInterval) {
                queuePollingInterval = setInterval(checkQueueStatus, 3000);
            }
        }

        function stopPolling() {
            if (queuePollingInterval) {
                clearInterval(queuePollingInterval);
                queuePollingInterval = null;
            }
        }

        // Start polling if there are pending jobs
        if (lastQueueCount > 0) {
            startPolling();
        }

        // Bulk Action Logic
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.file-checkbox, .folder-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedFiles = document.querySelectorAll('.file-checkbox:checked');
            const selectedFolders = document.querySelectorAll('.folder-checkbox:checked');
            const total = selectedFiles.length + selectedFolders.length;
            
            const menu = document.getElementById('bulk-actions-menu');
            const countEl = document.getElementById('selected-count');
            
            if (total > 0) {
                menu.classList.remove('hidden');
                countEl.textContent = total;
            } else {
                menu.classList.add('hidden');
            }
        }

        async function handleBulkAction(action) {
            const selectedFiles = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
            const selectedFolders = Array.from(document.querySelectorAll('.folder-checkbox:checked')).map(cb => cb.value);
            const folderId = document.getElementById('bulk-folder-select').value;

            if (selectedFiles.length === 0 && selectedFolders.length === 0) return;
            
            if (action === 'move') {
                if (!folderId) {
                    showToast('Please select a destination folder', 'error');
                    return;
                }
                if (selectedFolders.length > 0) {
                    showToast('Moving folders is not supported yet', 'error');
                    return;
                }
            }

            if (!confirm(`Are you sure you want to ${action} these items?`)) return;

            try {
                const response = await fetch('{{ route('dashboard.bulk-action') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file_ids: selectedFiles,
                        folder_ids: selectedFolders,
                        action: action,
                        folder_id: folderId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Action failed', 'error');
                }
            } catch (error) {
                console.error('Bulk action failed:', error);
                showToast('Server error during bulk action', 'error');
            }
        }

        function updateFileNames(input) {
            const count = input.files.length;
            const otherInputId = input.id === 'files' ? 'folder-input' : 'files';
            
            // Clear the other input to avoid duplicate/mixed uploads
            document.getElementById(otherInputId).value = '';
            
            document.getElementById('file-count').textContent = count > 0 ? `${count} ${input.id === 'files' ? 'files' : 'folder items'} selected` : 'Nothing selected';
            document.getElementById('file-count').classList.add('text-indigo-400');
        }

        function toggleTrackPassword(val) {
            document.getElementById('track-password-container').classList.toggle('hidden', val === 'public');
        }

        function toggleFolderPassword(val) {
            document.getElementById('folder-password-container').classList.toggle('hidden', val === 'public');
        }

        document.getElementById('upload-form').onsubmit = async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const container = document.getElementById('queue-container');
            const bar = document.getElementById('upload-progress-bar');
            const text = document.getElementById('upload-progress-text');
            const btn = form.querySelector('button[type="submit"]');

            container.classList.remove('hidden');
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    bar.style.width = percent + '%';
                    text.textContent = percent + '% Uploaded';
                }
            };

            xhr.onload = function() {
                console.log('Upload Response:', xhr.status, xhr.responseText);
                btn.disabled = false;
                btn.textContent = 'Add to Queue';
                
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        showToast(data.message);
                        
                        // Start polling for queue status
                        startPolling();
                        checkQueueStatus();
                        
                        // Reset progress bar after a short delay
                        setTimeout(() => container.classList.add('hidden'), 2000);
                    } catch (e) {
                        console.error('Parse Error:', e);
                        showToast('Success but failed to parse response', 'success');
                        setTimeout(() => location.reload(), 2000);
                    }
                } else {
                    let errorMsg = 'Check server logs';
                    try {
                        const error = JSON.parse(xhr.responseText);
                        errorMsg = error.message || errorMsg;
                    } catch(e) {
                        console.error('Error Parse Error:', e);
                    }
                    showToast('Upload Error: ' + errorMsg, 'error');
                    container.classList.add('hidden');
                }
            };

            xhr.onerror = function() {
                showToast('Connection Error', 'error');
                btn.disabled = false;
                btn.textContent = 'Add to Queue';
                container.classList.add('hidden');
            };

            xhr.send(formData);
        };

        async function playFile(id, visibility, title) {
            let password = '';
            if (visibility === 'private') {
                password = prompt('🔒 This track is protected. Enter password:');
                if (password === null) return;
            }

            const streamUrl = `/dashboard/stream/${id}?password=${encodeURIComponent(password)}`;
            const player = document.getElementById('audio-player');
            const container = document.getElementById('player-container');
            const titleEl = document.getElementById('player-title');

            // Setup the player
            player.src = streamUrl;
            titleEl.textContent = title;
            container.classList.remove('hidden');

            // Play the track
            try {
                await player.play();
            } catch (error) {
                // If it fails, maybe the password was wrong or something else
                alert('Playback failed. Check password if private.');
            }
        }

        function closePlayer() {
            const player = document.getElementById('audio-player');
            const container = document.getElementById('player-container');
            player.pause();
            player.src = '';
            container.classList.add('hidden');
        }
    </script>
</x-app-layout>
