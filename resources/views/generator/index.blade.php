<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="zkpm-page-header">{{ __('Password Generator') }}</h2>
                <p class="zkpm-page-subtitle">
                    {{ __('Multi-mode cryptographic password generator with live k-Anonymity breach detection') }}
                </p>
            </div>
            <x-security-badge type="encrypted" :label="__('k-Anonymity')" />
        </div>
    </x-slot>

    <div class="py-8">
        <div id="generator-app" class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="glass-card rounded-2xl p-6 shadow-xl text-slate-900 dark:text-white space-y-6">

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Generated Secret') }}</label>
                    <div class="flex flex-col sm:flex-row items-stretch gap-2">
                        <div class="relative flex-1">
                            <input type="text"
                                   id="gen-output"
                                   readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-lg text-emerald-600 font-bold tracking-wide focus:outline-none focus:border-blue-500 select-all transition shadow-inner dark:border-slate-700 dark:bg-slate-950 dark:text-emerald-400">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="gen-copy-btn" class="zkpm-btn-primary flex-1 sm:flex-none">
                                <x-icon-copy class="w-4 h-4" />
                                <span>{{ __('Copy') }}</span>
                            </button>
                            <button type="button" id="gen-refresh-btn" title="{{ __('Regenerate') }}"
                                    class="inline-flex items-center justify-center p-3 rounded-xl bg-white hover:bg-slate-50 text-slate-600 transition border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700">
                                <x-icon-refresh class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Strength') }}:</span>
                            <span id="gen-entropy-badge" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800">
                                {{ __('Very Strong') }} (128 bits)
                            </span>
                            <span class="text-slate-400 dark:text-slate-500">•</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Crack Time') }}:</span>
                            <span id="gen-crack-time" class="font-semibold text-slate-800 dark:text-slate-200">{{ __('Centuries+') }}</span>
                        </div>
                    </div>

                    <div id="gen-breach-warning" class="hidden text-xs p-2.5 rounded-lg"></div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700/80 pt-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <label for="gen-mode" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Generation Mode') }}</label>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Select algorithmic pattern') }}</p>
                        </div>
                        <select id="gen-mode" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="random">{{ __('Random Characters') }}</option>
                            <option value="passphrase">{{ __('Memorable Passphrase') }}</option>
                            <option value="pronounceable">{{ __('Pronounceable Words') }}</option>
                        </select>
                    </div>

                    <div id="gen-options-random" class="space-y-4 pt-2">
                        <div>
                            <div class="flex justify-between text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                                <span>{{ __('Length') }}: <strong id="gen-length-val" class="text-blue-600 dark:text-blue-400 font-mono">24</strong> {{ __('characters') }}</span>
                                <span class="text-slate-400 dark:text-slate-500">8 - 64</span>
                            </div>
                            <input type="range" id="gen-length" min="8" max="64" value="24" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-500 dark:bg-slate-800">
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-opt-upper" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Uppercase A-Z') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-opt-lower" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Lowercase a-z') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-opt-numbers" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Numbers 0-9') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-opt-symbols" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Special symbols') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-opt-ambiguous" class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Exclude ambiguous') }}</span>
                            </label>
                        </div>
                    </div>

                    <div id="gen-options-passphrase" class="hidden space-y-4 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="gen-words" class="block text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Word count') }} (3 - 8)</label>
                                <input type="number" id="gen-words" min="3" max="8" value="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            </div>
                            <div>
                                <label for="gen-separator" class="block text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Word separator') }}</label>
                                <select id="gen-separator" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="-">{{ __('Hyphen') }} (-)</option>
                                    <option value=".">{{ __('Period') }} (.)</option>
                                    <option value="_">{{ __('Underscore') }} (_)</option>
                                    <option value=" ">{{ __('Space') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-passphrase-cap" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Capitalize first letters') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-passphrase-num" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Append random number') }}</span>
                            </label>
                        </div>
                    </div>

                    <div id="gen-options-pronounceable" class="hidden space-y-4 pt-2">
                        <div>
                            <label for="gen-syl" class="block text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Syllable count') }} (3 - 8)</label>
                            <input type="number" id="gen-syl" min="3" max="8" value="4" class="mt-1 w-full sm:w-1/2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        </div>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-pron-cap" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Capitalize') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-pron-num" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Include numbers') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" id="gen-pron-sym" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                                <span>{{ __('Include symbols') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700/80">
                        <label class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-300 cursor-pointer">
                            <input type="checkbox" id="gen-opt-autobreach" checked class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                            <span class="font-semibold">{{ __('Auto-regenerate if breached') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Session Generation History') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Ephemeral history description') }}</p>
                    </div>
                    <x-security-badge type="info" :label="__('In-Memory Only')" />
                </div>
                <div id="gen-history-list" class="space-y-2"></div>
            </div>

        </div>
    </div>
</x-app-layout>
