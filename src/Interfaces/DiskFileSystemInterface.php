<?php

namespace Spool\CrytpFileSystem\Interfaces;

interface DiskFileSystemInterface
{
    public function read(int $length = 0): string;
    public function write(string $string, int $length = 0): int;
    public function load(string $string): int;
    public function save(int $mode): string;
}
