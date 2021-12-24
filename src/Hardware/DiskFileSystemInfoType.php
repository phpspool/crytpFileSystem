<?php

namespace Spool\CrytpFileSystem\Hardware;

class DiskFileSystemInfoType
{
    const VERSION = 1;
    // 信息节点指向一个文件
    const MODE_TYPE_FILE = 0;
    // 信息节点指向一个目录
    const MODE_TYPE_DIR = 1;
    // 信息节点指向一个符号链接文件
    const MODE_TYPE_SYMLINK = 2;
    // 可以读
    const MODE_POWER_READ = 1;
    // 可以写
    const MODE_POWER_WRITE = 2;
    // 可以运行 - 保留
    const MODE_POWER_EXECUTIVE = 4;
    // 文件同步标志, 每次写入都会同步落盘
    const FLAGS_SYNC = 1;
    // 不更新atime
    const FLAGS_NOATIME = 2;
    // 文件内容追加写
    const FLAGS_APPEND = 4;
    // 文件内容不可变, 无法更改或删除
    const FLAGS_IMMUTABLE = 8;
    // 文件开始删除, 事务完成前可见
    const FLAGS_DEAD = 16;
    // 信息节点不计入配额
    const FLAGS_NOQUOTA = 32;
    // 目录同步, 所有写操作都会同步落盘
    const FLAGS_DIRSYNC = 64;
    // 不更新ctime和lmtime, 改善读写性能
    const FLAGS_NOCMTIME = 128;
    // 文件是否加密
    const FLASG_ENCRYPTED = 16384;
}
