<?php
/**
 * SysEleven SMAPI 2 Project
 *
 * @author     Markus Seifert <m.seifert@syseleven.de>
 * @package    package
 * @subpackage subpackage
 */

namespace SysEleven\CgpEleven;

use SysEleven\CgpEleven\CliInterface;
use SysEleven\CgpEleven\Adapter\AdapterAbstract;
use SysEleven\CgpEleven\Serializer\SerializerInterface;


/**
 * Cli
 *
 * @author     Markus Seifert <m.seifert@syseleven.de>
 * @package    Communigate
 */ 
class Cli implements CliInterface
{
    /**
     * @var \SysEleven\CgpEleven\Adapter\AdapterAbstract $adapter
     */
    protected $_adapter;


    /**
     * Initializes the object.
     *
     * @param AdapterAbstract     $adapter
     */
    public function __construct(AdapterAbstract $adapter = null)
    {
        $this->_adapter = $adapter;
    }

    /**
     * Sets the Adapter used for the connection
     *
     * @param AdapterAbstract $adapter
     *
     * @return mixed
     */
    public function setAdapter(AdapterAbstract $adapter)
    {
        $this->_adapter = $adapter;

        return $this;
    }

    /**
     * Returns the current Adapter
     *
     * @throws \RuntimeException
     * @return AdapterAbstract
     */
    public function getAdapter()
    {
        if (!($this->_adapter instanceof AdapterAbstract)) {
            throw new \RuntimeException('Connection Adapter is not set');
        }

        return $this->_adapter;
    }

    /**
     * Sets the serializer. Since the serializer is used in the adapter an
     * exception will be thrown if no adapter is set
     *
     * @param SerializerInterface $serializer
     *
     * @return mixed
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->getAdapter()->setSerializer($serializer);

        return $this;
    }

    /**
     * Returns the serializer from the adapter
     *
     * @throws \RuntimeException
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->getAdapter()->getSerializer();
    }

    /**
     * Executes the command and returns the response.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getResponse($command, array $parameters = array())
    {
        return $this->getAdapter()->getResponse($command, $parameters);
    }

    /**
     * Sends the command.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @return mixed
     */
    public function sendCommand($command, array $parameters = array())
    {
        return $this->getAdapter()->sendCommand($this->getAdapter()->buildCommand($command, $parameters));
    }

    /**
     * Sets the debugging flag for the adapter
     *
     * @param int $mode
     * @return $this
     */
    public function setDebug($mode = 0)
    {
        $this->getAdapter()->setDebug($mode);

        return $this;
    }

    /**
     * Sets the
     * @param $logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->getAdapter()->setLogger($logger);

        return $this;
    }
}
