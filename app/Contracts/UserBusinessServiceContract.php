<?php

namespace App\Contracts;

use Paganini\UserAggregation\Contracts\BusinessServiceContract;

interface UserBusinessServiceContract extends BusinessServiceContract
{
    public function serviceKey(): string;

    public function supports(array $context): bool;

    /**
     * Return normalized business data object (already unwrapped from downstream `data` envelope).
     */
    public function fetch(array $baseUser, array $context): array;
}
