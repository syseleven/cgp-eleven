<?php
/**
 * SysEleven Communigate
 *
 * @author M. Seifert <m.seifert@syseleven.de
 * @package communigate
 * @subpackage serializer
 */
namespace SysEleven\CgpEleven\Serializer;

/**
 * Defines minimal method set for communigate serializers
 *
 * @author M. Seifert <m.seifert@syseleven.de
 * @package communigate
 * @subpackage serializerr
 */
interface SerializerInterface
{
    /**
     * Serializes the given data into a string
     *
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data);

    /**
     * Deserializes the given string into a complex form
     *
     * @param string $str Data to unserialize
     *
     * @return mixed
     */
    public function deserialize($str);
}