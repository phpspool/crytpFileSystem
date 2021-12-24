<?php

namespace Spool\CrytpFileSystem\Hardware;


use Spool\CrytpFileSystem\Exceptions\FileException\FileReadException;
use Spool\CrytpFileSystem\Interfaces\DiskFileSystemInterface;


/**
 * 扇区虚拟类, 读快写慢
 */
class Sector implements DiskFileSystemInterface
{
    /**
     * Sector size = 512Bytes
     */
    const SIZE = 512;
    /**
     * 头部数据大小
     */
    const HEADER_SIZE = 20;

    /**
     * 版本号
     */
    const VERSION = 1;
    /**
     * 扇区实际可用于存储的字节数
     *
     * @var int
     */
    protected int $valid = self::SIZE - self::HEADER_SIZE;

    /**
     * 元数据
     *
     * @var string
     */
    protected string $string = '';
    /**
     * 元数据大小
     *
     * @var integer
     */
    protected int $used = 0;
    /**
     * 从磁盘载入数据, 并返回载入的字符串长度
     * 
     * @param string $string 要载入的数据
     * 
     * @return int
     */
    public function load(string $string): int
    {
        if (!$string) {
            throw new FileReadException('Sector::load - string is empty');
        }
        $header = $this->getHeader($string);
        if (!$header['size']) {
            return 0;
        }
        $headerSize = self::HEADER_SIZE;
        if ($header['mode']) {
            $headerSize -= 4;
        }
        $loadString = substr($string, $headerSize, $header['size']);
        $crc = \crc32($loadString);
        $loadString = $this->decrypt($loadString, $header['mode']);
        if ($crc != $header['crc']) {
            throw new FileReadException("Sector CRC error");
        }
        if (self::VERSION < $header['version']) {
            throw new FileReadException("Sector version is " . self::VERSION . " less then file version {$header['version']}", 505);
        }
        $this->string = $loadString;
        $this->used = $header['size'];
        return self::SIZE;
    }
    /**
     * 从扇区中读出储存的元数据
     * 
     * @return string
     */
    public function read(int $length = 0): string
    {
        if ($this->string && $length) {
            return substr($this->string, 0, $length);
        }
        return $this->string;
    }
    /**
     * 将元数据编码写入扇区, 并返回未写入的元数据, 如果有点话. 
     * 返回空字符串代表元数据已全部写入.
     * 
     * @param string $string 要写入的元数据
     * @param int    $length 要写入的长度
     * 
     * @return int
     */
    public function write(string $string = '', int $length = 0): int
    {
        if ($length <= 0 || $length > $this->getValid()) {
            $length = $this->getValid();
        }
        $this->string = \substr($string, 0, $length);
        $this->used = $length;
        return $this->getValid();
    }
    /**
     * 获取已编码数据
     * 
     * @return string
     */
    public function save(int $mode): string
    {
        $strlen = strlen($this->string);
        $string = $this->encrypt($this->string, $mode);
        if ($strlen == 0) {
            $sectorString = \str_pad($this->string, self::SIZE, \chr(0));
        }
        $sectorString = $this->makeHeader($string, strlen($string), $mode)
            . $string;
        return \str_pad($sectorString, self::SIZE, \chr(0));
    }
    public function makeHeader(string $string, int $length, int $mode): string
    {
        $crc = \crc32($string);
        $header = \pack("LLLL", $length, $crc, self::VERSION, $mode);
        if (!$mode) {
            $header .= pack("L", 0);
        }
        return $header;
    }
    public function getHeader(string $string): array
    {
        if (strlen($string) < self::HEADER_SIZE) {
            throw new FileReadException("Sector getHeader strlen less " . self::HEADER_SIZE);
        }
        $headerStr = substr($string, 0, self::HEADER_SIZE - 4);
        $header = \unpack('Lsize/Lcrc/Lversion/Lmode', $headerStr);
        return $header;
    }
    /**
     * 解密数据
     * 
     * @param string $string 加密的数据
     * 
     * @return string
     */
    public function decrypt(string $string, int $mode): string
    {
        if (!$mode) {
            $string = trim($string);
            return $string;
        }
        return $string;
    }
    /**
     * 加密数据
     * 
     * @param string $string 元数据
     * 
     * @return string
     */
    public function encrypt(string $string, int $mode): string
    {
        if (!$mode) {
            return $string;
        }
        return $string;
    }
    public function getValid(): int
    {
        return $this->valid;
    }
    public function getUsed(): int
    {
        return $this->used;
    }
}
