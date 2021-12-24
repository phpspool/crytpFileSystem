<?php

namespace Tests\Feature;

use Spool\Formwork\Crypts\OpenSSLLib;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class OpensslTest extends TestCase
{
    public function testOpen()
    {
        $configFile = \CONFIG_PATH . 'config.yml';
        $config = Yaml::parseFile($configFile);
        $openSSL = new OpenSSLLib($config['openssl']);
        $string = "SL大法极乐世界老师看见了啥酒店发了啥剪短发了撒酒疯雷锋精神都放假了数据斐林试剂斐林试剂分类";
        $string = str_pad($string, 495, "非");
        $key = 'Type in a Password';
        $cipher = "AES-128-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = $openSSL->encrypt($string, $cipher, $key, $iv);
        $decrypted = $openSSL->decrypt($encrypted, $cipher, $key, $iv);
        var_dump(\base64_decode($encrypted), strlen(\base64_decode($encrypted)), $encrypted, $decrypted);
        $p = password_hash("123456", \PASSWORD_BCRYPT);
        \var_dump(\password_verify('123456', $p), \password_get_info($p));
        \var_dump(decbin(7) ^ \decbin(1));
    }
    public function Openssl()
    {
        $config = array(
            "private_key_bits" => 1024,                     //字节数    512 1024  2048   4096 等
            "private_key_type" => OPENSSL_KEYTYPE_RSA,     //加密类型
            "config" => "/etc/pki/tls/openssl.cnf"
        );

        $privkeypass = '123456789'; //私钥密码
        $numberofdays = 365;     //有效时长
        $cerpath = "./test.cer"; //生成证书路径
        $pfxpath = "./test.pfx"; //密钥文件路径

        $dn = array(
            "countryName" => "UK",                                  //所在国家
            "stateOrProvinceName" => "Somerset",                    //所在省份
            "localityName" => "Glastonbury",                        //所在城市
            "organizationName" => "The Brain Room Limited",         //注册人姓名
            "organizationalUnitName" => "PHP Documentation Team",   //组织名称
            "commonName" => "Wez Furlong",                          //公共名称
            "emailAddress" => "wez@example.com"                     //邮箱
        );

        // 生成公钥私钥资源
        $res = openssl_pkey_new($config);

        // 导出私钥 $priKey
        openssl_pkey_export($res, $priKey, null, $config);

        //  导出公钥 $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];
        //print_r($priKey); 私钥
        //print_r($pubKey); 公钥
        $string = "SL大法极乐世界老师看见了啥酒店发了啥剪短发了撒酒疯雷锋精神都放假了数据斐林试剂斐林试剂分类";
        $string = str_pad($string, 512, "非");

        //直接测试私钥 公钥
        var_dump('-------------------公私钥加解密-START---------------------');
        $data = '测试公私钥加解密成功!128';
        // $data = str_pad($data, 117, "非");
        $data = $string;
        // 公钥加密
        openssl_public_encrypt($data, $encrypted, $pubKey);
        // 私钥解密
        openssl_private_decrypt($encrypted, $decrypted, $priKey);
        \var_dump('-----------------------', strlen($data), strlen($encrypted), '-----------------------');
        var_dump('公钥加密：', base64_encode($encrypted), '私钥解密：', $decrypted);
        var_dump('-------------------公私钥加解密-END---------------------');

        //生成文件
        $csr = openssl_csr_new($dn, $priKey, $config); //基于$dn生成新的 CSR （证书签名请求）
        $sscert = openssl_csr_sign($csr, null, $priKey, 365, $config); //根据配置自己对证书进行签名
        openssl_x509_export($sscert, $csrkey); //将公钥证书存储到一个变量 $csrkey，由 PEM 编码格式命名。
        openssl_pkcs12_export($sscert, $privatekey, $priKey, $privkeypass); //将私钥存储到名为的出 PKCS12 文件格式的字符串。 导出密钥$privatekey

        //生成证书文件
        $fp = fopen($cerpath, "w");
        fwrite($fp, $csrkey);
        fclose($fp);
        //生成密钥文件
        $fp = fopen($pfxpath, "w");
        fwrite($fp, $privatekey);
        fclose($fp);


        // var_dump("\n";
        var_dump('----------------------自签名验证-START----------------------');
        // 测试私钥 秘钥
        $privkeypass = '123456789'; //私钥密码
        $pfxpath = "./test.pfx"; //密钥文件路径
        $priv_key = file_get_contents($pfxpath); //获取密钥文件内容
        $data = "测试数据！"; //加密数据测试test
        //私钥加密
        openssl_pkcs12_read($priv_key, $certs, $privkeypass); //读取公钥、私钥
        $prikeyid = $certs['pkey']; //私钥
        openssl_sign($data, $signMsg, $prikeyid, OPENSSL_ALGO_SHA1); //注册生成加密信息
        $signMsg = base64_encode($signMsg); //base64转码加密信息


        //公钥解密
        $unsignMsg = base64_decode($signMsg); //base64解码加密信息
        openssl_pkcs12_read($priv_key, $certs, $privkeypass); //读取公钥、私钥
        $pubkeyid = $certs['cert']; //公钥
        $res = openssl_verify($data, $unsignMsg, $pubkeyid); //验证
        var_dump($res ? '证书测试成功！' : '证书测试失败！');
        // var_dump("\n"); //输出验证结果，1：验证成功，0：验证失败

        var_dump('-----------------------签名验证-END------------------------');
    }
}
