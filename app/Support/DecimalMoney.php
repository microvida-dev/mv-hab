<?php

namespace App\Support;

use InvalidArgumentException;

final class DecimalMoney
{
    public const SCALE = 2;

    /**
     * @return numeric-string
     */
    public static function normalize(int|string|null $value, int $scale = self::SCALE): string
    {
        return bcadd(self::numeric($value), '0', $scale);
    }

    /**
     * @return numeric-string
     */
    public static function add(int|string|null $left, int|string|null $right, int $scale = self::SCALE): string
    {
        return bcadd(self::numeric($left), self::numeric($right), $scale);
    }

    /**
     * @return numeric-string
     */
    public static function subtract(int|string|null $left, int|string|null $right, int $scale = self::SCALE): string
    {
        return bcsub(self::numeric($left), self::numeric($right), $scale);
    }

    /**
     * @return numeric-string
     */
    public static function multiply(int|string|null $left, int|string|null $right, int $scale = self::SCALE): string
    {
        $raw = bcmul(self::numeric($left), self::numeric($right), $scale + 4);

        return self::round($raw, $scale);
    }

    /**
     * @return numeric-string
     */
    public static function divide(int|string|null $amount, int|string $divisor, int $scale = self::SCALE): string
    {
        if (bccomp(self::numeric($divisor), '0', $scale + 4) === 0) {
            throw new InvalidArgumentException('Não é possível dividir um valor monetário por zero.');
        }

        $raw = bcdiv(self::numeric($amount), self::numeric($divisor), $scale + 4);

        return self::round($raw, $scale);
    }

    /**
     * @return numeric-string
     */
    public static function percentage(int|string|null $amount, int|string|null $percentage, int $scale = self::SCALE): string
    {
        return self::divide(
            bcmul(self::numeric($amount), self::numeric($percentage), $scale + 6),
            100,
            $scale,
        );
    }

    /**
     * @return numeric-string|null
     */
    public static function ratioPercentage(int|string|null $part, int|string|null $total, int $scale = 4): ?string
    {
        if (! self::isPositive($total)) {
            return null;
        }

        return self::multiply(
            bcdiv(self::numeric($part), self::numeric($total), $scale + 6),
            100,
            $scale,
        );
    }

    /**
     * @return numeric-string
     */
    public static function min(int|string|null $left, int|string|null $right, int $scale = self::SCALE): string
    {
        return self::compare($left, $right, $scale) <= 0
            ? self::normalize($left, $scale)
            : self::normalize($right, $scale);
    }

    /**
     * @return numeric-string
     */
    public static function max(int|string|null $left, int|string|null $right, int $scale = self::SCALE): string
    {
        return self::compare($left, $right, $scale) >= 0
            ? self::normalize($left, $scale)
            : self::normalize($right, $scale);
    }

    /**
     * @return numeric-string
     */
    public static function negate(int|string|null $amount, int $scale = self::SCALE): string
    {
        return bcsub('0', self::numeric($amount), $scale);
    }

    public static function compare(int|string|null $left, int|string|null $right, int $scale = self::SCALE): int
    {
        return bccomp(self::numeric($left), self::numeric($right), $scale);
    }

    public static function isPositive(int|string|null $amount): bool
    {
        return self::compare($amount, '0') === 1;
    }

    /**
     * @param  iterable<int|string|null>  $values
     * @return numeric-string
     */
    public static function sum(iterable $values, int $scale = self::SCALE): string
    {
        $total = self::normalize(0, $scale);

        foreach ($values as $value) {
            $total = self::add($total, $value, $scale);
        }

        return $total;
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private static function round(string $value, int $scale): string
    {
        $increment = self::numeric('0.'.str_repeat('0', $scale).'5');

        return bccomp($value, '0', $scale + 1) >= 0
            ? bcadd($value, $increment, $scale)
            : bcsub($value, $increment, $scale);
    }

    /**
     * @return numeric-string
     */
    private static function numeric(int|string|null $value): string
    {
        $normalized = $value === null ? '0' : (string) $value;

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('O valor monetário tem de ser numérico.');
        }

        return $normalized;
    }
}
