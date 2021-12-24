<?php

namespace Spool\CrytpFileSystem\Crypts;

use Spool\CrytpFileSystem\Interfaces\CryptInterface;


class OpenSSLLib implements CryptInterface
{
    protected string $publicKey;
    protected string $privateKey;
    protected array $config;
    /**
     * 构造函数
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    public function getKeys(): bool
    {
        // echo \BASE_PATH . $this->config[''];
    }
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
    public function encrypt(string $data, string $cipher, string $passphrase, string $iv = '', $option = null): string
    {
        $encrypted = '';
        // \openssl_public_encrypt($data, $encrypted, $this->publicKey);
        $encrypted = \openssl_encrypt($data, $cipher, $passphrase, $option, $iv);
        return $encrypted;
    }
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
    public function decrypt(string $data, string $cipher, string $passphrase, string $iv = '', $option = null): string
    {
        $decrypted = '';
        // \openssl_private_decrypt($data, $decrypted, $this->privateKey);
        $decrypted = \openssl_decrypt($data, $cipher, $passphrase, $option, $iv);
        return $decrypted;
    }
}
