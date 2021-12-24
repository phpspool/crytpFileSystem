<?php

namespace Spool\CrytpFileSystem\Interfaces;

interface CryptInterface
{
    /**
     * 数据加密
     * 
     * @param string $data
     * @param string $cipher
     * @param string $passphrase
     * @param string $iv
     * @param [type] $option
     * 
     * @return string
     */
    public function encrypt(string $data, string $cipher, string $passphrase, string $iv = '', $option = null): string;
    /**
     * 数据解密
     * 
     * @param string $data
     * @param string $cipher
     * @param string $passphrase
     * @param string $iv
     * @param [type] $option
     * 
     * @return string
     */
    public function decrypt(string $data, string $cipher, string $passphrase, string $iv = '', $option = null): string;
}
