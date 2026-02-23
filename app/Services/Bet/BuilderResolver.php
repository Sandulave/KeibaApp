<?php

namespace App\Services\Bet;

use App\Services\Bet\Builders\BetBuilderInterface;
use RuntimeException;

class BuilderResolver
{
    public function resolve(string $betType, string $mode): BetBuilderInterface
    {
        $types = config('bets.types', []);
        $conf = $types[$betType]['modes'][$mode] ?? null;

        if (!$conf) {
            throw new RuntimeException("Unsupported betType/mode: {$betType}/{$mode}");
        }

        $class = $conf['builder'] ?? null;
        if (!$class || !class_exists($class)) {
            throw new RuntimeException("Builder not configured: {$betType}/{$mode}");
        }

        $builder = app($class);

        if (!$builder instanceof BetBuilderInterface) {
            throw new RuntimeException("Builder must implement BetBuilderInterface: {$class}");
        }

        return $builder;
    }
}
