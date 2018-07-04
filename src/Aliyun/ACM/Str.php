<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 2018/7/4 1:59 PM
 */

namespace Aliyun\ACM;

class Str
{

    /**
     * String encoding convert
     *
     * @param        $data
     * @param string $from
     * @param string $to
     *
     * @return array|string
     */
    public static function encodeConvert($data, $from = 'gbk', $to = 'utf-8')
    {
        if (is_array($data)) {
            array_walk_recursive($data, function (&$data, $key) use ($from, $to) {
                $data = mb_convert_encoding($data, $to, $from);
            });
        } elseif (is_object($data)) {
            array_walk_recursive($data, function (&$data, $key) use ($from, $to) {
                $data = mb_convert_encoding($data, $to, $from);
            }, ['utf-8', 'gbk']);
        } else {
            $data = mb_convert_encoding($data, $to, $from);
        }

        return $data;
    }

    /**
     * String encoding convert from utf-8 to gbk
     *
     * @param $data
     *
     * @return array|string
     */
    public static function toGBK($data)
    {
        return static::encodeConvert($data, 'utf-8', 'gbk');
    }

    /**
     * String encoding convert from gbk to utf-8
     *
     * @param $data
     *
     * @return array|string
     */
    public static function toUTF8($data)
    {
        return static::encodeConvert($data, 'gbk', 'utf-8');
    }

}