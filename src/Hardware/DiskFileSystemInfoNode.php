<?php

namespace Spool\CrytpFileSystem\Hardware;

class DiskFileSystemInfoNode
{
    /**
     * 文件类型和访问权限
     *
     * @var integer
     */
    protected int $mode;
    /**
     * 文件操作标记
     *
     * @var integer
     */
    protected int $opflags;
    protected int $uid;
    protected int $gid;
    /**
     * 文件标记
     *
     * @var integer
     */
    protected int $flags;
}
