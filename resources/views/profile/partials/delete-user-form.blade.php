<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-red-700 dark:text-red-400">
            {{ __('Delete Account') }}
        </h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Delete account warning') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ __('Delete account confirm title') }}
            </h2>

            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Delete account confirm description') }}
            </p>

            <div class="mt-6">
                <x-input-label for="confirm-email" value="{{ __('Email') }}" class="sr-only" />
                <x-text-input id="confirm-email" name="email" type="email" class="mt-1 block w-3/4" placeholder="{{ __('Email') }}" />
                <x-input-error :messages="$errors->userDeletion->get('email')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-danger-button>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
