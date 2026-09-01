{{-- Client-side i18n bridge for dynamic JS messages (vault.js, generator.js, lock.js, auth.js, webauthn-manager.js) --}}
@php
    $zkpmI18nData = [
        'All' => __('All'),
        'All items' => __('All items'),
        'Filter all items' => __('Filter all items'),
        'Item count summary' => __('Item count summary'),
        'Reveal' => __('Reveal'),
        'Hide' => __('Hide'),
        'Copy' => __('Copy'),
        'Copied' => __('Copied'),
        'Copied!' => __('Copied!'),
        'Edit' => __('Edit'),
        'Delete' => __('Delete'),
        'Remove' => __('Remove'),
        'Create New Vault Item' => __('Create New Vault Item'),
        'Edit Vault Item' => __('Edit Vault Item'),
        'No Category' => __('No Category'),
        'Password strength' => __('Password strength'),
        'Very Weak' => __('Very Weak'),
        'Weak' => __('Weak'),
        'Fair' => __('Fair'),
        'Strong' => __('Strong'),
        'Very Strong' => __('Very Strong'),
        'Checking breach status…' => __('Checking breach status…'),
        'Pwned breach warning' => __('Pwned breach warning'),
        'Pwned breach count' => __('Pwned breach count'),
        'Clean breach status' => __('Clean breach status'),
        'Clean generator status' => __('Clean generator status'),
        'Generator breach warning' => __('Generator breach warning'),
        'Failed to load vault items.' => __('Failed to load vault items.'),
        'Failed to decrypt existing item values.' => __('Failed to decrypt existing item values.'),
        'Decryption failed.' => __('Decryption failed.'),
        'Copy failed.' => __('Copy failed.'),
        'Save failed.' => __('Save failed.'),
        'Delete failed.' => __('Delete failed.'),
        'Delete vault item confirm' => __('Delete vault item confirm'),
        'No categories created yet.' => __('No categories created yet.'),
        'Delete category confirm' => __('Delete category confirm'),
        'Failed to delete category.' => __('Failed to delete category.'),
        'Failed to create category.' => __('Failed to create category.'),
        'No vault items found.' => __('No vault items found.'),
        'Request failed.' => __('Request failed.'),
        'Passwords do not match.' => __('Passwords do not match.'),
        'Deriving keys…' => __('Deriving keys…'),
        'Verifying…' => __('Verifying…'),
        'Authenticating…' => __('Authenticating…'),
        'Unlocking…' => __('Unlocking…'),
        'Register' => __('Register'),
        'Continue' => __('Continue'),
        'Verify' => __('Verify'),
        'Unlock Vault' => __('Unlock Vault'),
        'Unlock recovery' => __('Unlock recovery'),
        'Registration failed.' => __('Registration failed.'),
        'Login failed.' => __('Login failed.'),
        'Recovery key does not match this vault.' => __('Recovery key does not match this vault.'),
        'Failed to reset password.' => __('Failed to reset password.'),
        'Incorrect master password.' => __('Incorrect master password.'),
        'Unlock failed.' => __('Unlock failed.'),
        'Device biometric unlock session expired; enter Master Password.' => __('Device biometric unlock session expired; enter Master Password.'),
        'No passkeys or security keys registered yet.' => __('No passkeys or security keys registered yet.'),
        'Security Key' => __('Security Key'),
        'Remove passkey confirm' => __('Remove passkey confirm'),
        'Failed to delete credential.' => __('Failed to delete credential.'),
        'Failed to load passkeys.' => __('Failed to load passkeys.'),
        'Waiting for authenticator…' => __('Waiting for authenticator…'),
        'Passkey device label prompt' => __('Passkey device label prompt'),
        'Added' => __('Added'),
        'Generated Secret' => __('Generated Secret'),
        'Regenerate' => __('Regenerate'),
        'Strength' => __('Strength'),
        'Crack Time' => __('Crack Time'),
        'Very Strong' => __('Very Strong'),
        'Centuries+' => __('Centuries+'),
        'Generation Mode' => __('Generation Mode'),
        'Select algorithmic pattern' => __('Select algorithmic pattern'),
        'Random Characters' => __('Random Characters'),
        'Memorable Passphrase' => __('Memorable Passphrase'),
        'Pronounceable Words' => __('Pronounceable Words'),
        'Session Generation History' => __('Session Generation History'),
        'In-Memory Only' => __('In-Memory Only'),
        'Ephemeral history description' => __('Ephemeral history description'),
        'No generation history yet.' => __('No generation history yet.'),
        'Dismiss' => __('Dismiss'),
        'Locked' => __('Locked'),
        'Vault Inactive & Locked' => __('Vault Inactive & Locked'),
    ];
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
    window.zkpmI18n = @json($zkpmI18nData);

    /**
     * Resolve a localized string for client-side modules.
     * Supports `:placeholder` interpolation for dynamic values.
     *
     * @param {string} key - Translation key from the lang JSON files.
     * @param {Object<string, string|number>} [replacements] - Placeholder values.
     * @returns {string} The localized string (falls back to the key itself).
     */
    window.zkpmT = function (key, replacements = {}) {
        let text = window.zkpmI18n[key] ?? key;
        Object.entries(replacements).forEach(([placeholder, value]) => {
            text = text.replace(`:${placeholder}`, String(value));
        });
        return text;
    };
</script>