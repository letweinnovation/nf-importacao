<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NF-Import | Premium Data Tool</title>
    @vite('resources/css/app.css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Alpine Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body
    class="bg-slate-900 text-white font-sans antialiased min-h-screen flex flex-col relative overflow-hidden selection:bg-brand-500 selection:text-white">

    <!-- Loading Overlay with Progress Bar -->
    <div x-data="{ loading: false, progress: 0 }" @process-start.window="loading = true; progress = 0; 
            let interval = setInterval(() => { 
                if (progress < 90) progress += Math.random() * 5; 
            }, 500);
            $watch('loading', value => { if(!value) clearInterval(interval) });"
        @process-end.window="progress = 100; setTimeout(() => loading = false, 500)">

        <div x-show="loading"
            class="fixed inset-0 z-[100] bg-slate-900/80 backdrop-blur-sm flex items-center justify-center transition-opacity duration-300"
            x-transition.opacity>

            <div class="bg-slate-800 border border-slate-600 p-8 rounded-3xl shadow-2xl w-full max-w-lg transform transition-all scale-100 relative overflow-hidden"
                x-show="loading" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">

                <!-- Glow Effect -->
                <div
                    class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 via-purple-500 to-brand-400 animate-gradient-x">
                </div>

                <div class="flex flex-col items-center text-center space-y-6">
                    <!-- Icon Animation -->
                    <div class="relative">
                        <div
                            class="w-16 h-16 bg-brand-500/10 rounded-full flex items-center justify-center animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-8 h-8 text-brand-400 animate-bounce">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-2xl font-bold text-white">Processando Arquivos</h3>
                        <p class="text-slate-400 text-sm">Estamos gerando sua planilha, aguarde um momento...</p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full space-y-2">
                        <div class="flex justify-between text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <span>Progresso</span>
                            <span x-text="Math.round(progress) + '%'"></span>
                        </div>
                        <div class="h-4 w-full bg-slate-700/50 rounded-full overflow-hidden border border-slate-700">
                            <div class="h-full bg-gradient-to-r from-brand-500 to-purple-500 relative transition-all duration-300 ease-out flex items-center justify-end pr-1"
                                :style="`width: ${progress}%`">
                                <!-- Shiny effect -->
                                <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Success Modal -->
    <div x-data="{ show: false, message: '', downloadUrl: '', fileName: '' }" 
         @show-success.window="show = true; message = $event.detail.message; downloadUrl = $event.detail.downloadUrl; fileName = $event.detail.fileName"
         @process-start.window="show = false">
        
        <div x-show="show" 
             class="fixed inset-0 z-[110] bg-slate-900/90 backdrop-blur-md flex items-center justify-center transition-opacity duration-300"
             x-transition.opacity>
            
            <div class="bg-slate-800 border border-green-500/50 p-8 rounded-3xl shadow-2xl w-full max-w-lg relative transform transition-all"
                 @click.away="show = false"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">
                
                <div class="flex flex-col items-center text-center space-y-4">
                    <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-white">Sucesso!</h3>
                    <p class="text-slate-300" x-text="message"></p>

                    <a :href="downloadUrl" :download="fileName" class="mt-4 px-6 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-bold transition-all flex items-center gap-2 transform hover:scale-105 shadow-lg shadow-green-600/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Baixar Arquivo Agora
                    </a>

                    <button @click="show = false" class="mt-4 text-sm text-slate-500 hover:text-slate-300">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div x-data="{ show: false, message: '', debug: '' }"
        @show-error.window="show = true; message = $event.detail.message; debug = $event.detail.debug"
        @process-start.window="show = false; message = ''; debug = ''">

        <div x-show="show"
            class="fixed inset-0 z-[110] bg-slate-900/90 backdrop-blur-md flex items-center justify-center transition-opacity duration-300"
            x-transition.opacity>

            <div class="bg-slate-800 border border-slate-600 p-8 rounded-3xl shadow-2xl w-full max-w-lg relative transform transition-all"
                @click.away="show = false" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">

                <div class="flex flex-col items-center text-center space-y-4">
                    <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-8 h-8 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-white">Ops! Algo deu errado</h3>
                    <p class="text-slate-300" x-text="message"></p>

                    <!-- Debug Section -->
                    <div x-data="{ expanded: false }" class="w-full" x-show="debug">
                        <button @click="expanded = !expanded"
                            class="text-xs text-slate-500 hover:text-white flex items-center justify-center gap-1 mx-auto mt-2 transition-colors">
                            <span x-text="expanded ? 'Ocultar detalhes técnicos' : 'Ver detalhes técnicos'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-3 h-3 transition-transform"
                                :class="expanded ? 'rotate-180' : ''">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="expanded" x-collapse class="mt-4">
                            <div
                                class="bg-slate-900 rounded-lg p-3 text-left overflow-x-auto max-h-40 custom-scrollbar border border-slate-700">
                                <pre class="text-xs text-red-400 font-mono" x-text="debug"></pre>
                            </div>
                        </div>
                    </div>

                    <button @click="show = false"
                        class="mt-6 px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-full transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-brand-500 opacity-20 blur-3xl animate-pulse">
        </div>
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-purple-600 opacity-10 blur-[100px]">
        </div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-cyan-500 opacity-20 blur-3xl"></div>
    </div>

    <!-- (Header and Main Content remain unchanged) -->

    <!-- Header -->
    <header class="w-full py-6 px-8 flex justify-between items-center glass sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">NF-Import</h1>
        </div>
        <div class="text-sm font-medium text-slate-400">v1.1.0 (Ref: {{ date('H:i:s') }})</div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center justify-center p-6 w-full max-w-5xl mx-auto"
        x-data="fileUpload()">

        <div class="text-center mb-10 space-y-2">
            <h2
                class="text-4xl md:text-5xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400">
                Importação Inteligente de NFs
            </h2>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto">
                Arraste seus arquivos XML (DANFE) e gere planilhas compatíveis com Omie e GTI Plug em segundos.
            </p>
        </div>

        <!-- Drop Zone -->
        <div class="w-full max-w-3xl glass rounded-3xl p-10 border-2 border-dashed transition-all duration-300 relative group"
            :class="{'border-brand-500 bg-brand-500/10 scale-[1.02]': isDragging, 'border-slate-700 hover:border-slate-500': !isDragging}"
            @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)">
            <input type="file" multiple class="hidden" x-ref="fileInput" @change="handleFiles($event.target.files)"
                accept=".xml">

            <div class="flex flex-col items-center justify-center space-y-4 text-center" x-show="files.length === 0">
                <div
                    class="w-20 h-20 rounded-full bg-slate-800 flex items-center justify-center mb-2 shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-10 h-10 text-brand-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-xl font-semibold text-white">Arraste e solte seus XMLs aqui</p>
                    <p class="text-sm text-slate-400">ou <button
                            class="text-brand-400 hover:text-brand-300 font-medium hover:underline"
                            @click="$refs.fileInput.click()">navegue pelos arquivos</button></p>
                </div>
                <p class="text-xs text-slate-500 mt-4">Suporta múltiplos arquivos XML simultaneamente</p>
            </div>

            <!-- File List Preview -->
            <div x-show="files.length > 0" class="w-full space-y-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-white">Arquivos Selecionados (<span
                            x-text="files.length"></span>)</h3>
                    <button @click="files = []"
                        class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-3 h-3">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Limpar tudo
                    </button>
                </div>

                <div class="max-h-60 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                    <template x-for="(file, index) in files" :key="index">
                        <div
                            class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700/50 hover:border-slate-600 transition-colors">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 rounded bg-slate-700 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-300">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-medium text-slate-200 truncate" x-text="file.name"></span>
                                    <span class="text-xs text-slate-500" x-text="formatSize(file.size)"></span>
                                </div>
                            </div>
                            <button @click="removeFile(index)"
                                class="p-1 hover:bg-slate-700 rounded-full text-slate-500 hover:text-red-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Add More Button -->
                <div class="flex justify-center mt-4">
                    <button @click="$refs.fileInput.click()"
                        class="text-sm text-brand-400 hover:text-brand-300 flex items-center gap-2 px-4 py-2 hover:bg-brand-500/10 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Adicionar mais arquivos
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons Section -->
        <div class="w-full max-w-3xl mt-10 grid grid-cols-1 md:grid-cols-2 gap-8" x-show="files.length > 0"
            x-transition.opacity.duration.500ms>

            <!-- Cadastro de Produto -->
            <div class="space-y-4 bg-slate-800/30 p-6 rounded-3xl border border-slate-700/50">
                <h3 class="text-lg font-semibold text-slate-300 flex items-center gap-2">
                    <div class="w-1 h-6 bg-indigo-500 rounded-full"></div>
                    Cadastro de Produto
                </h3>
                <div class="grid grid-cols-1 gap-6">
                    <button
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 p-px shadow-lg hover:shadow-indigo-500/25 transition-all duration-300 hover:scale-[1.02]"
                        @click="processFiles('omie')">
                        <div
                            class="relative bg-slate-900/50 rounded-xl p-4 flex items-center justify-between group-hover:bg-opacity-0 transition-all duration-300">
                            <div class="flex flex-col items-start">
                                <span class="text-[10px] font-bold text-indigo-300 tracking-wider uppercase opacity-80">Exportar para</span>
                                <span class="text-lg font-bold text-white">Omie</span>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    <button
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-px shadow-lg hover:shadow-emerald-500/25 transition-all duration-300 hover:scale-[1.02]"
                        @click="processFiles('gti')">
                        <div
                            class="relative bg-slate-900/50 rounded-xl p-4 flex items-center justify-between group-hover:bg-opacity-0 transition-all duration-300">
                            <div class="flex flex-col items-start">
                                <span class="text-[10px] font-bold text-emerald-300 tracking-wider uppercase opacity-80">Exportar para</span>
                                <span class="text-lg font-bold text-white">GTI PLUG</span>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Cadastro de Operação GTI PLUG -->
            <div class="space-y-4 bg-slate-800/30 p-6 rounded-3xl border border-slate-700/50">
                <h3 class="text-lg font-semibold text-slate-300 flex items-center gap-2">
                    <div class="w-1 h-6 bg-cyan-500 rounded-full"></div>
                    Operação GTI PLUG
                </h3>
                <div class="grid grid-cols-1 gap-8">
                    <button
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 p-px shadow-lg hover:shadow-blue-500/25 transition-all duration-300 hover:scale-[1.02]"
                        @click="processFiles('recebimento')">
                        <div
                            class="relative bg-slate-900/50 rounded-xl p-4 flex items-center justify-between group-hover:bg-opacity-0 transition-all duration-300">
                            <div class="flex flex-col items-start">
                                <span class="text-[10px] font-bold text-blue-300 tracking-wider uppercase opacity-80">Exportar para</span>
                                <span class="text-lg font-bold text-white">Recebimento</span>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    <button
                        class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-rose-500 to-rose-700 p-px shadow-lg hover:shadow-rose-500/25 transition-all duration-300 hover:scale-[1.02]"
                        @click="processFiles('expedicao')">
                        <div
                            class="relative bg-slate-900/50 rounded-xl p-4 flex items-center justify-between group-hover:bg-opacity-0 transition-all duration-300">
                            <div class="flex flex-col items-start">
                                <span class="text-[10px] font-bold text-rose-300 tracking-wider uppercase opacity-80">Exportar para</span>
                                <span class="text-lg font-bold text-white">Expedição</span>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-5 h-5 text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="w-full max-w-3xl mt-12 mb-20" x-show="history.length > 0" x-transition>
            <h3 class="text-xl font-bold text-slate-300 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Histórico de Arquivos
            </h3>
            <div
                class="bg-slate-800/50 rounded-2xl border border-slate-700/50 overflow-hidden divide-y divide-slate-700/50">
                <template x-for="item in history" :key="item.name">
                    <div class="p-4 flex items-center justify-between hover:bg-slate-700/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center text-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-slate-200" x-text="item.name"></div>
                                <div class="text-xs text-slate-500 flex gap-2">
                                    <span x-text="item.date"></span>
                                    <span>&bull;</span>
                                    <span x-text="item.size"></span>
                                </div>
                            </div>
                        </div>
                        <a :href="item.url"
                            class="p-2 text-slate-400 hover:text-white hover:bg-slate-600 rounded-lg transition-colors"
                            title="Baixar novamente">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </a>
                    </div>
                </template>
            </div>
        </div>

    </main>

    <footer class="py-6 text-center text-slate-500 text-sm">
        <p>&copy; {{ date('Y') }} NF-Import. Todos os direitos reservados.</p>
    </footer>

    <script>
        function fileUpload() {
            return {
                isDragging: false,
                files: [],
                history: [],
                init() {
                    this.fetchHistory();
                },
                async fetchHistory() {
                    try {
                        const response = await fetch('/history');
                        this.history = await response.json();
                    } catch (e) {
                        console.error("Erro ao carregar histórico", e);
                    }
                },
                handleDrop(e) {
                    this.isDragging = false;
                    this.handleFiles(e.dataTransfer.files);
                },
                handleFiles(fileList) {
                    // Convert FileList to Array and filter XMLs if needed, or take all
                    const newFiles = Array.from(fileList).filter(file => file.name.toLowerCase().endsWith('.xml'));

                    if (newFiles.length === 0 && fileList.length > 0) {
                        alert('Por favor, envie apenas arquivos XML.');
                        return;
                    }

                    // Add to existing files
                    this.files = [...this.files, ...newFiles];
                },
                removeFile(index) {
                    this.files.splice(index, 1);
                },
                formatSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },
                async processFiles(type) {
                    if (this.files.length === 0) return;

                    this.$dispatch('process-start');

                    const formData = new FormData();
                    this.files.forEach(file => {
                        formData.append('files[]', file);
                    });

                    try {
                        const response = await fetch(`/process/${type}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        // Check if response is JSON (error)
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            const data = await response.json();
                            throw new Error(data.message || 'Erro no processamento');
                        }

                        if (!response.ok) throw new Error('Erro na requisição');

                        // Handle File Download
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;

                        // Try to get filename from header or fallback
                        const disposition = response.headers.get('Content-Disposition');
                        let fileName = `importacao_${type}_${new Date().toISOString().slice(0, 10)}.xlsx`;
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                            const matches = filenameRegex.exec(disposition);
                            if (matches != null && matches[1]) {
                                fileName = matches[1].replace(/['"]/g, '');
                            }
                        }


                        link.setAttribute('download', fileName);
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        // Delay revoking to ensure download starts
                        setTimeout(() => window.URL.revokeObjectURL(url), 1000); // v1.1.0 fix

                        // Show Success Modal with Link (Backup)
                        this.$dispatch('show-success', {
                            message: 'Arquivo gerado com sucesso!',
                            downloadUrl: url,
                            fileName: fileName
                        });

                        // Refresh history
                        this.fetchHistory();

                    } catch (error) {
                        console.error(error);
                        // Extract debug info if available (e.g., from server response)
                        let debugMsg = error.stack || '';
                        let userMsg = error.message || 'Ocorreu um erro ao processar os arquivos.';

                        // If it's a server error message containing details
                        if (userMsg.includes('Erro ao processar arquivos:')) {
                            // Try to split technical details
                            const parts = userMsg.split(': ');
                            if (parts.length > 1) {
                                userMsg = parts[0];
                                debugMsg = parts.slice(1).join(': ');
                            }
                        }

                        this.$dispatch('show-error', {
                            message: userMsg,
                            debug: debugMsg
                        });
                    } finally {
                        this.$dispatch('process-end');
                    }
                }
            }
        }
    </script>


    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.8);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.8);
        }
    </style>
</body>

</html>