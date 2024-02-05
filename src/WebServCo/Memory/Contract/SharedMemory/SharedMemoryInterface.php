<?php

declare(strict_types=1);

namespace WebServCo\Memory\Contract\SharedMemory;

interface SharedMemoryInterface
{
    public function delete(string $identifier): bool;

    public function read(string $identifier): string;

    public function write(string $data, string $identifier): bool;
}
