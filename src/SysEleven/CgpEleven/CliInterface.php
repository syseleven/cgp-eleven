<?php
/**
 * Created by JetBrains PhpStorm.
 * User: markus
 * Date: 03.03.13
 * Time: 21:59
 * To change this template use File | Settings | File Templates.
 */
namespace SysEleven\CgpEleven;

use SysEleven\CgpEleven\Adapter\AdapterAbstract;
use SysEleven\CgpEleven\Serializer\SerializerInterface;

/**
 * Class CliInterface, defines the classic CLI methods for managing a CGPro
 * Installation
 *
 * @package Communigate
 */
interface CliInterface
{
    // 1. Global methods

    /**
     * Sets the Adapter
     *
     * @param AdapterAbstract $adapter
     *
     * @return mixed
     */
    public function setAdapter(AdapterAbstract $adapter);

    /**
     * @return AdapterAbstract
     */
    public function getAdapter();

    /**
     * Sets the serializer. Since the serializer is used in the adapter an
     * exception will be thrown if no adapter is set
     *
     * @param SerializerInterface $serializer
     *
     * @return mixed
     */
    public function setSerializer(SerializerInterface $serializer);

    /**
     * Returns the serializer from the adapter
     *
     * @return SerializerInterface
     */
    public function getSerializer();

    /**
     * Executes the command and returns the response.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    public function getResponse($command, array $parameters = array());

    /**
     * Sends the command.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    public function sendCommand($command, array $parameters = array());
}