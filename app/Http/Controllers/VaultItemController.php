<?php

namespace App\Http\Controllers;

use App\Models\VaultItem;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaultItemController extends Controller
{
    /**
     * List the current user's vault items.
     *
     * Sensitive fields (encrypted_password, encrypted_notes, iv) are
     * hidden by default on the model; they are explicitly exposed only
     * to the authenticated owner, still as ciphertext. The client
     * decrypts them locally with the in-memory Encryption Key.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The encrypted vault items.
     */
    public function index(Request $request): JsonResponse
    {
        $items = VaultItem::query()
            ->where('user_id', $request->user()->id)
            ->with('category:id,name')
            ->orderBy('title')
            ->get()
            ->each->makeVisible(['encrypted_password', 'encrypted_notes', 'iv']);

        return response()->json(['items' => $items]);
    }

    /**
     * Store a new vault item.
     *
     * The password and notes arrive as AES-256-GCM ciphertext produced
     * client-side; the server never sees plaintext and simply persists
     * the blob plus the IV.
     *
     * @param  Request  $request  The current request.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The stored item.
     */
    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'encrypted_password' => ['required', 'string'],
            'encrypted_notes' => ['nullable', 'string'],
            'iv' => ['required', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
        ]);

        $item = $request->user()->vaultItems()->create($validated);

        $audit->log($request->user(), 'vault_item_created', $request);

        return response()->json([
            'ok' => true,
            'item' => $item->makeVisible(['encrypted_password', 'encrypted_notes', 'iv']),
        ], 201);
    }

    /**
     * Update an existing vault item owned by the current user.
     *
     * @param  Request  $request  The current request.
     * @param  VaultItem  $vaultItem  The item being updated.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The updated item.
     */
    public function update(Request $request, VaultItem $vaultItem, AuditLogger $audit): JsonResponse
    {
        $this->authorizeOwnership($request, $vaultItem);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'encrypted_password' => ['required', 'string'],
            'encrypted_notes' => ['nullable', 'string'],
            'iv' => ['required', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
        ]);

        $vaultItem->update($validated);

        $audit->log($request->user(), 'vault_item_updated', $request);

        return response()->json([
            'ok' => true,
            'item' => $vaultItem->makeVisible(['encrypted_password', 'encrypted_notes', 'iv']),
        ]);
    }

    /**
     * Delete a vault item owned by the current user.
     *
     * @param  Request  $request  The current request.
     * @param  VaultItem  $vaultItem  The item being deleted.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The outcome payload.
     */
    public function destroy(Request $request, VaultItem $vaultItem, AuditLogger $audit): JsonResponse
    {
        $this->authorizeOwnership($request, $vaultItem);

        $vaultItem->delete();

        $audit->log($request->user(), 'vault_item_deleted', $request);

        return response()->json(['ok' => true]);
    }

    /**
     * Ensure the item belongs to the current user, otherwise 404.
     *
     * A 404 (rather than 403) avoids revealing the existence of other
     * users' items.
     *
     * @param  Request  $request  The current request.
     * @param  VaultItem  $vaultItem  The item to check.
     */
    private function authorizeOwnership(Request $request, VaultItem $vaultItem): void
    {
        if ($vaultItem->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
