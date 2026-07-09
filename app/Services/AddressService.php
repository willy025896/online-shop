<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function listForUser(User $user): Collection
    {
        return $user->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock the user row so concurrent create() calls for the same user
            // (e.g. a double-submitted "first address") serialize instead of
            // racing — clearDefault() alone can't help here because a brand
            // new user has no existing rows for it to lock.
            $user = $this->lockUser($user->id);

            // Short-circuits: skip the exists() query entirely when the
            // caller already asked for this address to be the default.
            $isDefault = ($data['is_default'] ?? false) || ! $user->addresses()->exists();

            if ($isDefault) {
                $this->clearDefault($user);
            }

            return $user->addresses()->create([
                ...$data,
                'is_default' => $isDefault,
            ]);
        });
    }

    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            $wasDefault = $address->is_default;
            $willBeDefault = $data['is_default'] ?? false;

            // Only the default flag needs the user-row lock; a plain field
            // edit that never touches it has nothing to serialize.
            if ($willBeDefault) {
                $this->clearDefault($this->lockUser($address->user_id));
            }

            $address->update($data);

            // Unsetting the only default address must not leave the user
            // with zero defaults — promote another one, mirroring delete().
            if ($wasDefault && ! $willBeDefault) {
                $this->promoteReplacementDefault($address->user_id, $address->id);
            }

            return $address;
        });
    }

    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $userId = $address->user_id;
            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $this->promoteReplacementDefault($userId);
            }
        });
    }

    public function setDefault(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $this->clearDefault($this->lockUser($address->user_id));
            $address->update(['is_default' => true]);
        });
    }

    private function lockUser(int $userId): User
    {
        return User::whereKey($userId)->lockForUpdate()->first();
    }

    private function clearDefault(User $user): void
    {
        // No separate lockForUpdate() here: MySQL takes the row lock as part
        // of the UPDATE itself, and the caller already holds the user-row
        // lock that serializes concurrent default changes.
        $user->addresses()
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function promoteReplacementDefault(int $userId, ?int $excludeId = null): void
    {
        Address::where('user_id', $userId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderByDesc('id')
            ->limit(1)
            ->update(['is_default' => true]);
    }
}
