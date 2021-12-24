<?php

namespace Spool\CrytpFileSystem\Hardware;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Spool\CrytpFileSystem\Exceptions\FileException\FileReadException;

class BlockGroup
{
    /**
     * 一个块组大小为64Mb
     */
    const BLOCK_MAX = 64 * 1024 / Block::SIZE;
    /**
     * 头部数据大小
     */
    const HEADER_SIZE = 0;
    /**
     * 读取的块
     *
     * @var array
     */
    protected array $blocks = [];
    protected int $mode = 1;
    /**
     * 脏块
     *
     * @var array
     */
    protected array $dirtyBlock = [];
    protected int $blockNumber = 0;
    protected int $pointer = 0;
    protected int $groupId = -1;
    protected int $valid;
    protected bool $loaded = false;
    protected string $rootFile = "";
    protected $fd = null;
    protected $cached = false;
    /**
     * 构造函数
     */
    public function __construct(int $mode = 1)
    {
        $block = new Block;
        $this->valid = self::BLOCK_MAX * $block->getValid();
        $this->mode = $mode;
    }
    /**
     * 如果还没有设置id, 且id>0, 则设置并返回该id, 否则返回-1, 
     * block不负责纠错, 如果设置错了, 可能会造成不可预测的后果
     */
    public function setId(int $id): int
    {
        if ($this->groupId < 0) {
            $this->groupId = $id;
            return $this->groupId;
        }
        return -1;
    }
    public function getId(): int
    {
        return $this->groupId;
    }
    public function load(): int
    {
        if (!$this->getId()) {
            throw new FileReadException('BlockGroup::load - groupId is empty');
        }
        if (!$this->rootFile) {
            throw new FileReadException('BlockGroup::load - rootFile is empty');
        }
        $mode = "rb";
        if (!is_file($this->rootFile)) {
            $mode = "x+";
        }
        if (!$this->fd) {
            $this->fd = \fopen($this->rootFile, $mode);
        }
        if ($mode === "x+") {
            return 0;
        }
        if (!$this->fd) {
            throw new FileReadException("BlockGroup::load - file not open");
        }
        if (!filesize($this->rootFile)) {
            return 0;
        }
        \flock($this->fd, \LOCK_SH);
        // 定位BlockGroup在文件中单位置
        fseek($this->fd, self::BLOCK_MAX * Block::SIZE * $this->groupId);
        // 读取文件内容
        $string = \fread($this->fd, self::BLOCK_MAX * Block::SIZE);
        if (!$string) {
            throw new FileReadException('BlockGroup::load - string is empty');
        }
        $loadLength = 0;
        $blockId = -1;
        $strlen = strlen($string);
        while ($strlen >= 0) {
            $blockId++;
            $loadString = substr($string, $loadLength, Block::SIZE);
            $block = new Block;
            $loadLength += $block->load($loadString);
            $block->setBlockId($blockId);
            $block->setGroupId($this->groupId);
            $this->blocks[$blockId] = $block;
            $strlen -= Block::SIZE;
        }
        $this->blockNumber = \count($this->blocks);
        $this->loaded = true;
        \flock($this->fd, \LOCK_UN);
        return $loadLength;
    }
    /**
     * 如果pointer>-1, 则会定位到pointer指向的block并返回
     * 
     * @param integer $pointer 定位指针
     * 
     * @return Block|null
     */
    public function current(int $pointer = -1): ?Block
    {
        $block = null;
        if (!$this->loaded) {
            return $block;
        }
        if ($pointer > -1 && $pointer < $this->blockNumber) {
            $this->pointer = $pointer;
        }
        if ($this->pointer >= 0 && isset($this->blocks[$this->pointer])) {
            $block = $this->blocks[$this->pointer];
        }
        return $block;
    }
    public function next(): ?Block
    {
        $block = $this->current();
        $this->pointer++;
        return $block;
    }
    public function prev(): ?Block
    {
        $block = $this->current();
        $this->pointer--;
        return $block;
    }
    public function end(): ?Block
    {
        $this->pointer = $this->blockNumber;
        $block = $this->current();
        return $block;
    }
    public function reset(): ?Block
    {
        $this->pointer = 0;
        $block = $this->current();
        return $block;
    }
    public function count(): int
    {
        return $this->blockNumber;
    }
    /**
     * 在组最后追加一个空的Block, 并返回追加的Block, 如果BlockGroup已满, 则返回-1
     * @return Block $block
     */
    public function add(): ?Block
    {
        if ($this->blockNumber >= self::BLOCK_MAX) {
            return null;
        }
        $block = new Block;
        $block->setGroupId($this->groupId);
        $pointer = $this->blockNumber;
        $block->setBlockId($pointer);
        $this->blocks[$pointer] = $block;
        $this->dirtyBlock[$pointer] = true;
        $this->cached = true;
        $this->blockNumber = $pointer;
        return $block;
    }
    /**
     * 回写Block, 必须是相同位置的Block才可以, 不能用新创建的替换
     * 回写只回写内容, 不回写块本身, 会有一些性能损耗,但是可以保证
     * 安全和兼容性
     * @param Block $block
     * @return boolean
     */
    public function writeBack(Block $block): bool
    {
        if ($block->getGroupId() !== $this->groupId) {
            return false;
        }
        $oldBlock = $this->current($block->getBlockId());
        if (!$oldBlock) {
            return false;
        }
        $oldBlock->write($block->read());
        $this->blocks[$block->getBlockId()] = $oldBlock;
        // 压入脏块队列中, 落盘时回写.
        $this->dirtyBlock[$block->getBlockId()] = true;
        $this->cached = true;
        return true;
    }
    /**
     * 落盘操作
     * 
     * @return integer
     */
    public function flush(): int
    {
        return $this->flushDisk($this->mode);
    }
    public function checkLoaded(): bool
    {
        return $this->loaded;
    }
    public function getDirytNumber(): int
    {
        return count($this->dirtyBlock);
    }
    protected function flushDisk(int $mode = 1): int
    {
        if (!$this->fd) {
            $this->fd = \fopen($this->rootFile, "wb");
        }
        if (!$this->fd) {
            return 0;
        }
        flock($this->fd, \LOCK_EX);
        // 定位BlockGroup在文件中的置
        $groupSeek = self::BLOCK_MAX * Block::SIZE * $this->groupId;
        $writeLength = $flashLength = 0;
        foreach ($this->dirtyBlock as $key => $value) {
            /**
             * @var Block
             */
            $block = $this->blocks[$key];
            // 定位Block在文件中的置
            $blockSeek = Block::SIZE * $block->getBlockId();
            fseek($this->fd, $groupSeek + $blockSeek);
            $writeLength = \fwrite($this->fd, $block->save($mode), Block::SIZE);
            if ($writeLength) {
                $flashLength += $writeLength;
                unset($this->dirtyBlock[$key]);
            }
        }
        if (!$this->dirtyBlock) {
            $this->cached = false;
        }
        \flock($this->fd, \LOCK_UN);
        return $flashLength;
    }

    /**
     * Get the value of rootFile
     *
     * @return  string
     */
    public function getRootFile(): string
    {
        return $this->rootFile;
    }

    /**
     * Set the value of rootFile
     *
     * @param string $rootFile 
     *
     * @return bool
     */
    public function setRootFile(string $rootFile): bool
    {
        if (!$this->rootFile) {
            $this->rootFile = $rootFile;
            return true;
        }
        return $this;
    }
    public function __destruct()
    {
        if ($this->cached) {
            $this->flushDisk($this->mode);
            $this->cached = false;
        }
        if ($this->fd) {
            fclose($this->fd);
        }
    }
}
