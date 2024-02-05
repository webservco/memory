<?php

declare(strict_types=1);

namespace WebServCo\Memory\Service\SharedMemory;

use OutOfBoundsException;
use Shmop;
use Throwable;
use UnexpectedValueException;
use WebServCo\Memory\Contract\SharedMemory\SharedMemoryInterface;

use function array_key_exists;
use function crc32;
use function mb_strlen;
use function shmop_delete;
use function shmop_open;
use function shmop_read;
use function shmop_write;
use function str_pad;
use function trim;

/**
 * A default SharedMemoryInterface implementation.
 */
final class DefaultSharedMemoryService implements SharedMemoryInterface
{
    /**
     * @param array<string,\Shmop> $shmopList
     */
    public function __construct(public int $shmopSize = 256, private array $shmopList = [])
    {
    }

    public function delete(string $identifier): bool
    {
        if (!array_key_exists($identifier, $this->shmopList)) {
            return false;
        }

        return shmop_delete($this->shmopList[$identifier]);
    }

    public function read(string $identifier): string
    {
        $shmop = $this->getShmop($identifier);

        $string = shmop_read($shmop, 0, 0);

        /**
         * String has the length of the shared memory.
         * Solution: trim string.
         */
        return trim($string);
    }

    public function write(string $data, string $identifier): bool
    {
        $shmop = $this->getShmop($identifier);

        // Pad string to avoid errors when reading back (data is not replaced but written over).
        $paddedString = str_pad($data, $this->shmopSize, ' ');
        $writtenDataSize = shmop_write($shmop, $paddedString, 0);
        if ($writtenDataSize !== mb_strlen($paddedString)) {
            throw new UnexpectedValueException('Error writing data');
        }

        return true;
    }

    private function getShmop(string $identifier): Shmop
    {
        if (!array_key_exists($identifier, $this->shmopList)) {
            try {
                $shmop = shmop_open(crc32($identifier), 'c', 0644, $this->shmopSize);
            } catch (Throwable $e) {
                throw new OutOfBoundsException($e->getMessage(), (int) $e->getCode(), $e);
            }
            if ($shmop === false) {
                throw new OutOfBoundsException('Error initializing shared memory.');
            }

            $this->shmopList[$identifier] = $shmop;
        }

        return $this->shmopList[$identifier];
    }
}
