<?php

namespace App\Services;

class RideStatusValidator
{
    /**
     * Allowed status transitions
     */
    private const ALLOWED_TRANSITIONS = [
        'requested' => ['accepted', 'cancelled'],
        'accepted' => ['arrived', 'cancelled'],
        'arrived' => ['started'],
        'started' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Validate if transition is allowed
     */
    public function isTransitionAllowed(string $currentStatus, string $newStatus): bool
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus]);
    }

    /**
     * Get allowed transitions for a status
     */
    public function getAllowedTransitions(string $status): array
    {
        return self::ALLOWED_TRANSITIONS[$status] ?? [];
    }

    /**
     * Validate and throw exception if invalid
     */
    public function validate(string $currentStatus, string $newStatus): void
    {
        if (!$this->isTransitionAllowed($currentStatus, $newStatus)) {
            throw new \App\Exceptions\InvalidRideTransitionException(
                "Invalid status transition from '{$currentStatus}' to '{$newStatus}'. " .
                "Allowed transitions: " . implode(', ', $this->getAllowedTransitions($currentStatus))
            );
        }
    }
}
