<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Shared helpers for member-facing API controllers: who is calling, ownership checks,
 * safe error responses (never leak exception text) and safe sorting / paging.
 */
trait ApiResponds
{
    /** Authenticated user placed on the request by the auth.jwt middleware. */
    protected function actor(Request $request): ?User
    {
        $u = $request->userModel ?? null;
        if ($u instanceof User) {
            return $u;
        }
        $id = auth('api')->id();
        return $id ? User::find($id) : null;
    }

    protected function actorIsAdmin(?User $user): bool
    {
        return $user && $user->isAdmin();
    }

    protected function canAccessUser(?User $actor, $ownerId): bool
    {
        return $actor && ($this->actorIsAdmin($actor) || (int) $actor->id === (int) $ownerId);
    }

    protected function ok($message, $data = null, int $http = 200, array $extra = [])
    {
        return response()->json(array_merge([
            'code' => 1,
            'status' => 1,
            'message' => $message,
            'data' => $data,
        ], $extra), $http);
    }

    protected function fail($message, int $http = 400, array $extra = [])
    {
        return response()->json(array_merge([
            'code' => 0,
            'status' => 0,
            'message' => $message,
            'data' => null,
        ], $extra), $http);
    }

    protected function forbidden($message = 'You are not allowed to access this record.')
    {
        return $this->fail($message, 403);
    }

    protected function validationFailed($validator)
    {
        return $this->fail($validator->errors()->first() ?: 'Validation failed.', 422, ['errors' => $validator->errors()]);
    }

    /**
     * Log the real exception, return a generic message to the client.
     * Exceptions that are meant for users are thrown as \DomainException and shown as-is.
     */
    protected function serverError(\Throwable $e, string $fallback = 'Something went wrong. Please try again.')
    {
        \Log::error(static::class . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        if ($e instanceof \DomainException) {
            return $this->fail($e->getMessage(), 400);
        }
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->fail('Record not found.', 404);
        }
        return $this->fail($fallback, 500);
    }

    /** Log an exception and return null so it can sit in an `error` field without leaking details. */
    protected function hideError(\Throwable $e)
    {
        \Log::error(static::class . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        return null;
    }

    /** Log the exception; show only user-meant (DomainException) text, otherwise the generic fallback. */
    protected function userMessage(\Throwable $e, string $fallback): string
    {
        \Log::error(static::class . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        return $e instanceof \DomainException ? $e->getMessage() : $fallback;
    }

    protected function sortColumn(Request $request, array $allowed, string $default = 'created_at'): string
    {
        $col = (string) $request->get('sort_by', $default);
        return in_array($col, $allowed, true) ? $col : $default;
    }

    protected function sortDirection(Request $request): string
    {
        return strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
    }

    protected function perPage(Request $request, int $default = 50, int $max = 100): int
    {
        $n = (int) $request->get('per_page', $default);
        return max(1, min($n, $max));
    }
}
