<?php

declare(strict_types=1);

namespace CA\Log\Facades;

use CA\Log\Services\CaLogger;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void log(string $operation, string $level, string $message, array $context = [])
 * @method static void certificateIssued(string $serial, string $subject, array $context = [])
 * @method static void certificateRevoked(string $serial, string $reason, array $context = [])
 * @method static void keyGenerated(string $algorithm, int $keySize, array $context = [])
 * @method static void crlUpdated(string $caId, int $entriesCount, array $context = [])
 * @method static void ocspResponse(string $serial, string $status, array $context = [])
 * @method static void acmeOperation(string $action, array $context = [])
 * @method static void scepOperation(string $action, array $context = [])
 * @method static void estOperation(string $action, array $context = [])
 * @method static void critical(string $message, array $context = [])
 *
 * @see \CA\Log\Services\CaLogger
 */
class CaLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CaLogger::class;
    }
}
