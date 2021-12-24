<?php

namespace Spool\CrytpFileSystem\Hardware;

use Spool\CrytpFileSystem\Exceptions\FileException\FileReadException;
use Spool\CrytpFileSystem\Exceptions\FileException\FileWriteException;
use Spool\CrytpFileSystem\Interfaces\DiskFileSystemInterface;


class Block implements DiskFileSystemInterface
{
    const SECTOR_NUMBER = 8;
    /**
     * Block size = 4096Bytes
     */
    const SIZE = Sector::SIZE * self::SECTOR_NUMBER;
    /**
     * 头部数据大小
     */
    const HEADER_SIZE = 0;

    protected int $valid;

    protected int $blockId = -1;
    protected int $groupId = -1;

    protected string $string = '';

    protected array $sectors = [];
    /**
     * 构造函数
     */
    public function __construct()
    {
        $sector = new Sector;
        $this->valid = $sector->getValid() * self::SECTOR_NUMBER - self::HEADER_SIZE;
    }
    /**
     * 如果还没有设置id, 且id>0, 则设置并返回该id, 否则返回-1, 
     * block不负责纠错, 如果设置错了, 可能会造成不可预测的后果
     */
    public function setBlockId(int $id): int
    {
        if ($this->blockId < 0) {
            $this->blockId = $id;
            return $this->blockId;
        }
        return -1;
    }
    public function getBlockId(): int
    {
        return $this->blockId;
    }
    /**
     * 如果还没有设置id, 且id>0, 则设置并返回该id, 否则返回-1, 
     * block不负责纠错, 如果设置错了, 可能会造成不可预测的后果
     */
    public function setGroupId(int $id): int
    {
        if ($this->groupId < 0) {
            $this->groupId = $id;
            return $this->groupId;
        }
        return -1;
    }
    public function getGroupId(): int
    {
        return $this->groupId;
    }
    public function load(string $string): int
    {
        if (!$string) {
            throw new FileReadException('Block::load - string is empty');
        }
        $loadLength = 0;
        $strlen = strlen($string);
        while ($strlen >= 0) {
            $loadString = substr($string, $loadLength, Sector::SIZE);
            $sector = new Sector;
            $loadLength += $sector->load($loadString);
            $this->sectors[] = $sector;
            $strlen -= Sector::SIZE;
        }
        return $loadLength;
    }
    public function read(int $length = 0): string
    {
        $length = $length ?: strlen($this->string);
        if ($this->string) {
            return substr($this->string, 0, $length);
        }
        $string = '';
        foreach ($this->sectors as $value) {
            $string .= $value->read();
        }
        $this->string = $string;
        if (!$length) {
            return $string;
        }
        return substr($string, 0, $length);
    }
    public function write(string $string, int $length = 0): int
    {
        $strlen = $length ?: strlen($string);
        if (!$strlen) {
            throw new FileWriteException('Block::write - string is empty');
        }
        $writeStr = substr($string, 0, $strlen);
        $writeLength = 0;
        $count = 0;
        while ($count < self::SECTOR_NUMBER) {
            $sector = $this->sectors[$count] ?? new Sector;
            $toWrite = $sector->getValid();
            if ($strlen < $sector->getValid()) {
                $toWrite = $strlen;
            }
            $toWriteStr = substr($writeStr, $writeLength, $toWrite);
            $len = $sector->write($toWriteStr);
            $this->sectors[$count] = $sector;
            $strlen -= $sector->getValid();
            $count++;
            $writeLength += $toWrite;
            if ($strlen <= 0) {
                break;
            }
        }
        $this->string = substr($string, 0, $writeLength);
        return $writeLength;
    }
    public function save(int $mode): string
    {
        $string = "";
        $count = 0;
        while ($count < self::SECTOR_NUMBER) {
            $sector = $this->sectors[$count] ?? new Sector;
            $string .= $sector->save($mode);
            $count++;
        }
        return $string;
    }
    public function getValid(): int
    {
        return $this->valid;
    }
}
