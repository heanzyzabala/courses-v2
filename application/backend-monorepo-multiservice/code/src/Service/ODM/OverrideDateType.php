<?php

declare(strict_types=1);

namespace Galeas\Api\Service\ODM;

use Doctrine\ODM\MongoDB\Types\DateType;

/**
 * Mongo does not allow for DateTimeImmutable, nor does it allow for microseconds.
 * Native mongo date stores up to milliseconds precision.
 *
 * This overriden type forces \DateTimeImmutable for properties, and it stores dates in UTC,
 */
class OverrideDateType extends DateType
{
    /**
     * @param null|\DateTimeImmutable|string $value
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public static function getUTCDateString($value)
    {
        if (null === $value) {
            return null;
        }

        if (\is_string($value)) {
            $value = \DateTimeImmutable::createFromFormat(
                'Y-m-d\TH:i:s.uP',
                $value
            );
        }

        if ($value instanceof \DateTimeImmutable) {
            $valueUTC = \DateTimeImmutable::createFromFormat(
                'Y-m-d\TH:i:s.uP',
                $value->format('Y-m-d\TH:i:s.uP')
            );

            if (\is_bool($valueUTC)) {
                throw new \Exception('Doctrine Mapping Exception on OverrideDateType::getUTCDateString');
            }
            $valueUTC = $valueUTC->setTimezone(new \DateTimeZone('UTC'));

            return $valueUTC->format('Y-m-d\TH:i:s.uP');
        }

        throw new \Exception('Doctrine Mapping Exception on OverrideDateType::getUTCDateString');
    }

    /**
     * @param null|\DateTimeImmutable|string $value
     *
     * @return null|\DateTimeImmutable
     *
     * @throws \Exception
     */
    public static function getDateTimeImmutable($value)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (\is_string($value)) {
            $return = \DateTimeImmutable::createFromFormat(
                'Y-m-d\TH:i:s.uP',
                $value
            );

            if ($return instanceof \DateTimeImmutable) {
                return $return;
            }

            throw new \Exception('Doctrine Mapping Exception on OverrideDateType::getDateTimeImmutable');
        }
    }

    /**
     * Always stores in UTC for sortability.
     *
     * @param null|\DateTimeImmutable|string $value
     *
     * @return null|bool|string
     */
    public function convertToDatabaseValue($value)
    {
        try {
            return self::getUTCDateString($value);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param null|\DateTimeImmutable|string $value
     *
     * @return null|bool|\DateTimeImmutable
     */
    public function convertToPHPValue($value)
    {
        try {
            return self::getDateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; } else { $return = \\'.static::class.'::getUTCDateString($value); }';
    }

    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } else { $return = \\'.static::class.'::getDateTimeImmutable($value); }';
    }
}
