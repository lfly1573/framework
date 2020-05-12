<?php

/**
 * 密码运算类
 */

namespace lfly\util;

class Crypto
{
    /**
     * AES解密
     * @param string $data 要解密的数据
     * @param string $key  运算密钥
     * @return string
     */
    public static function decryptAES($data, $key)
    {
        return openssl_decrypt(hex2bin($data), 'aes-256-ecb', strtoupper(md5($key)), 1);
    }

    /**
     * AES加密
     * @param string $data 要加密的数据
     * @param string $key  运算密钥
     * @return string
     */
    public static function encryptAES($data, $key)
    {
        return strtoupper(bin2hex(openssl_encrypt($data, 'aes-256-ecb', strtoupper(md5($key)), 1)));
    }

    /**
     * RSA私钥解密
     * @param string $data           要解密的数据
     * @param string $privateKeyPath 私钥文件路径
     * @return string
     */
    public static function decryptRSA($data, $privateKeyPath)
    {
        $priKey = file_get_contents($privateKeyPath);
        $res = openssl_get_privatekey($priKey);
        $keyInfo = openssl_pkey_get_details($res);
        $dataAreaLenth = intval($keyInfo['bits'] / 8);
        $data = base64_decode($data);
        $return = '';
        for ($i = 0; $i < ceil(strlen($data) / $dataAreaLenth); $i++) {
            $tempdata = openssl_private_decrypt(substr($data, $i * $dataAreaLenth, $dataAreaLenth), $string, $res);
            if ($tempdata) {
                $return .= $string;
            } else {
                return '';
            }
        }
        return $return;
    }

    /**
     * RSA私钥加密
     * @param string $data           要加密的数据
     * @param string $privateKeyPath 私钥文件路径
     * @return string
     */
    public static function encryptRSA($data, $privateKeyPath)
    {
        $priKey = file_get_contents($privateKeyPath);
        $res = openssl_get_privatekey($priKey);
        $keyInfo = openssl_pkey_get_details($res);
        $dataAreaLenth = intval($keyInfo['bits'] / 8) - 11;
        $return = '';
        for ($i = 0; $i < ceil(strlen($data) / $dataAreaLenth); $i++) {
            $tempdata = openssl_private_encrypt(substr($data, $i * $dataAreaLenth, $dataAreaLenth), $string, $res);
            if ($tempdata) {
                $return .= $string;
            } else {
                return '';
            }
        }
        return ($return != '') ? base64_encode($return) : '';
    }

    /**
     * RSA签名
     * @param string $data           要签名的数据
     * @param string $privateKeyPath 私钥文件路径
     * @return string
     */
    public static function signRSA($data, $privateKeyPath)
    {
        $priKey = file_get_contents($privateKeyPath);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * RSA验证签名
     * @param string $data           要验签的数据
     * @param string $publicKeyPath  公钥文件路径
     * @param string $sign           签名内容
     * @return bool
     */
    public static function verifyRSA($data, $publicKeyPath, $sign)
    {
        $pubKey = file_get_contents($publicKeyPath);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }
}
