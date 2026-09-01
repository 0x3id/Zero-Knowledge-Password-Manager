/**
 * Client-side breach detection using the Have I Been Pwned Pwned Passwords API.
 *
 * Implements the k-Anonymity model per ARCHITECTURE.md and SRS FR-6:
 * 1. Computes the SHA-1 hash of the candidate password locally in browser memory.
 * 2. Transmits ONLY the first 5 hexadecimal characters (the prefix) to api.pwnedpasswords.com.
 * 3. The server returns a list of matching hash suffixes and breach occurrence counts.
 * 4. The client searches the suffix list locally to determine if the password was leaked.
 *
 * The raw password or full hash NEVER leaves the client.
 */

/**
 * Computes the uppercase hex SHA-1 digest of a string using Web Crypto API.
 *
 * @param {string} text - The input string to hash.
 * @returns {Promise<string>} 40-character uppercase hexadecimal SHA-1 string.
 */
export async function sha1Hex(text) {
    // Step 1: Encode string into UTF-8 byte buffer
    const encoder = new TextEncoder();
    const data = encoder.encode(text);

    // Step 2: Calculate SHA-1 digest via native Web Crypto
    const hashBuffer = await window.crypto.subtle.digest('SHA-1', data);

    // Step 3: Convert ArrayBuffer to uppercase hexadecimal string
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('')
        .toUpperCase();
}

/**
 * Checks if a candidate password appears in known data breaches via k-Anonymity.
 *
 * @param {string} password - The plaintext password to inspect locally.
 * @returns {Promise<{breached: boolean, count: number, error: string|null}>} Breach check result.
 */
export async function checkPasswordBreach(password) {
    if (!password || password.length === 0) {
        return { breached: false, count: 0, error: null };
    }

    try {
        // Step 1: Compute full SHA-1 hash locally
        const fullHash = await sha1Hex(password);
        const prefix = fullHash.substring(0, 5);
        const suffix = fullHash.substring(5);

        // Step 2: Query Pwned Passwords API with only the 5-character prefix
        const response = await fetch(`https://api.pwnedpasswords.com/range/${prefix}`, {
            method: 'GET',
            headers: {
                'Add-Padding': 'true', // Mask response size to prevent side-channel analysis
            },
        });

        if (!response.ok) {
            return { breached: false, count: 0, error: 'Breach API unavailable' };
        }

        const text = await response.text();

        // Step 3: Parse returned hash suffixes line-by-line
        const lines = text.split('\n');
        for (const line of lines) {
            const parts = line.trim().split(':');
            if (parts.length >= 2) {
                const returnedSuffix = parts[0].trim().toUpperCase();
                const count = parseInt(parts[1].trim(), 10);

                // Step 4: Compare suffix locally
                if (returnedSuffix === suffix) {
                    return {
                        breached: true,
                        count: isNaN(count) ? 1 : count,
                        error: null,
                    };
                }
            }
        }

        // Password not found in any known breach database
        return { breached: false, count: 0, error: null };
    } catch (err) {
        // Gracefully degrade if network fails or offline
        return { breached: false, count: 0, error: 'Network error during breach check' };
    }
}
