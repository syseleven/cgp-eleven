<?php
/**
 * AdapterAbstract.php
 * @author M. Seifert <m.seifert@syseleven.de
 * @package 
 * @subpackage
 */
namespace SysEleven\CgpEleven\Adapter;

use SysEleven\CgpEleven\Exception\CommunigateException;
use SysEleven\CgpEleven\Exception\CommunigateNotFoundException;
use SysEleven\CgpEleven\Exception\CommunigateAlreadyExistsException;
use SysEleven\CgpEleven\Serializer\SerializerInterface;

 
/**
 *
 * @author M. Seifert <m.seifert@syseleven.de
 * @package SysEleven\CgpEleven\Adapter
 */ 
abstract class AdapterAbstract
{

    const CLI_OK                 = 200;
    const CLI_SUCCESS            = 201;
    const CLI_UNKNOWN            = 404;
    const CLI_ALREADY_EXISTS     = 412;

    const CLI_ACCOUNT_UNKNOWN    = 404;
    const CLI_ACCOUNT_EXISTS     = 412;
    const CLI_MAILINGLIST_EXISTS = 412;
    const CLI_FORWARDER_EXISTS   = 412;
    const CLI_GROUP_UNKNOWN      = 404;
    const CLI_FORWARDER_UNKNOWN  = 404;
    const CLI_DOMAIN_UNKNOWN     = 404;
    const CLI_DOMAIN_EXISTS      = 412;
    const CLI_SERVER_ERROR       = 500;

    /**
     * Debug flag
     * @type int
     */
    protected $_debug = 0;

    /**
     * Error message
     * @type string
     */
    protected $_errMsg = '';

    /**
     * Error code
     * @type int
     */
    protected $_errCode = 0;

        /**
     * Last response from the server
     *
     * @type null
     */
    protected $_inlineResponse = null;

    /**
     * Last command issued to the server
     *
     * @type null
     */
    protected $_currentCommand = null;

    /**
     * @type SerializerInterface
     */
    protected $_serializer = null;

    /**
     * @type null
     */
    protected $_logger = null;


    /**
     * Initializes the object and sets the connection parameters,
     * the serializer to use and an optional logger
     *
     * @param array               $connection
     * @param SerializerInterface $serializer
     * @param null                $logger
     */
    public function __construct(array $connection = array(),
                               SerializerInterface $serializer = null,
                               $logger = null)
    {
        $this->setOptions($connection);
        $this->setSerializer($serializer);
        $this->setLogger($logger);
    }

    /**
     * Takes the command and its parameters, prepares them, send them to the
     * server and returns the response if there is any.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getResponse($command, array $parameters = array())
    {
        $_command = $this->buildCommand($command, $parameters);

        return $this->sendCommand($_command);
    }

    /**
     * Takes the command and its parameters, prepares them, send them to the
     * server and returns true if successful.
     *
     * @param       $command
     * @param array $parameters
     *
     * @return bool
     */
    public function executeCommand($command, array $parameters = array())
    {
        $_command = $this->buildCommand($command, $parameters);

        $this->sendCommand($_command);

        return $this->isSuccess();
    }

    /**
     * Builds the command, $command has to be a string holding the name of
     * the command, $parameters is an array with the parameters to issue to it.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    abstract public function buildCommand($command, array $parameters = array());

    /**
     * Issues the prepared command to the server and parses the response
     *
     * @param string $command
     *
     * @return mixed
     */
    abstract public function sendCommand($command);

    /**
     * Sets an optional debug logger, the logger must implement an debug
     * method to set debug messages. eg Monolog or Zend\Log
     *
     * @param $logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * Gets the logger, or false if no logger is set
     *
     * @return null
     */
    public function getLogger()
    {
        return ($this->_logger == null)? false:$this->_logger;
    }

    /**
     * Returns the serializer
     *
     * @return SerializerInterface
     * @throws CommunigateException
     */
    public function getSerializer()
    {
        if (!($this->_serializer instanceof SerializerInterface)) {
            throw new CommunigateException('Cannot retrieve serializer, no serializer set or wrong type');
        }

        return $this->_serializer;
    }

    /**
     * Sets the serializer.
     *
     * @param SerializerInterface $serializer
     *
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->_serializer = $serializer;

        return $this;
    }


    /**
     * Sets the options for the adapter, it first checks if a dedicated setter
     * for the key is available if not not it checks if there is a protected
     * property _key and then tries to find a property key, if key is not a
     * property of the class it is skipped (no overloading permitted here)
     *
     * @param array $options
     *
     * @return $this
     * @todo switch to 5.4 and make this a trait
     */
    public function setOptions(array $options = array())
    {
        if (!is_array($options) || 0 == count($options)) {
            return $this;
        }

        $ref = new \ReflectionClass($this);
        foreach ($options AS $k => $v) {
            if (is_numeric($k) || 0 == strlen($k)) {
                continue;
            }

            // Look for a dedicated setter first, must be in the form
            // setCamelCasedKeyName
            $m = sprintf(
                'set%s'
                , str_replace(' ', '', ucwords(str_replace('_', ' ', $k)))
            );

            if ($ref->hasMethod($m)) {
                $this->$m($v);
                continue;
            }

            // Protected Variables are underscored by convention
            if ($ref->hasProperty('_' . $k)) {
                $name = '_' . $k;
                $this->$name = $v;
                continue;
            }

            // Standard vars
            if ($ref->hasProperty($k)) {
                $this->$k = $v;
                continue;
            }

            // camelcased

            $pbl = str_replace(' ', '', ucwords(str_replace('_', ' ', $k)));
            $pro = '_'.str_replace(' ', '', ucwords(str_replace('_', ' ', $k)));

            if ($ref->hasProperty($pbl)) {
                $this->$pbl = $v;
                continue;
            }

            if ($ref->hasProperty($pro)) {
                $this->$pro = $v;
                continue;
            }
        }


        return $this;
    }

    /**
     * Sets debug mode possible values are 0 (off) or 1 (on)
     *
     * @param int $mode
     *
     * @throws \BadMethodCallException
     * @return AdapterAbstract
     */
    public function setDebug($mode = 0)
    {
        $mode = intval($mode);
        if (!in_array($mode, array('0', '1'))) {
            throw new \BadMethodCallException('Not a valid value for debug expected 0,1 got: '.$mode);
        }
        $this->_debug = $mode;

        return $this;
    }

    /**
     * Gets the current value of debug
     *
     * @return int
     */
    public function getDebug()
    {
        return $this->_debug;
    }

    /**
     * Returns the current error code
     *
     * @return mixed
     */
    public function getErrCode()
    {
        return $this->_errCode;
    }

    /**
     * Returns the current error message
     *
     * @return string
     */
    public function getErrMsg()
    {
        return $this->_errMsg;
    }

    public function setLastCommand($command)
    {
        $this->_currentCommand = $command;

        return $this;
    }

    /**
     * Returns the last command issued to the server
     *
     * @return null
     */
    public function getLastCommand()
    {
        return $this->_currentCommand;
    }

    /**
     * Returns the last response received from the server
     *
     * @return null
     */
    public function getLastResponse()
    {
        return $this->_inlineResponse;
    }

    /**
     * Checks if the last response was an error or not
     *
     * @return boolean
     */
    public function isSuccess()
    {
        if ($this->_errCode == 200 || $this->_errCode == 201) {
            return true;
        }

        return false;
    }

    /**
     * Prints $line if debug is enabled
     *
     * @param $line
     *
     * @todo think about something more sophisticated
     * @return null
     */
    public function debug($line)
    {
        if ($logger = $this->getLogger()) {
            $logger->debug($line);

            return true;
        }

        if ($this->getDebug()) {
            print $line;
        }
    }

    /**
     * Creates a new Exception and returns it.
     *
     * @param $errMsg
     * @param $errCode
     *
     * @return CommunigateException
     */
    public function createException($errMsg, $errCode)
    {
        if (empty($errCode)) {
            $errCode = $this->_errCode;
        }

        $lastCommand  = $this->_currentCommand;
        $lastResponse = $this->_inlineResponse;

        if (empty($errMsg)) {
            $errMsg = $this->_errMsg;
        }

        if (false !== strpos($errMsg,'already exists')) {
            $errCode = self::CLI_ALREADY_EXISTS;
            return new CommunigateAlreadyExistsException($errMsg, $errCode, $lastCommand, $lastResponse);
        }

        if ($errMsg == 'unknown secondary domain name'
            || $errMsg == 'unknown user account'
            || $errMsg == 'forwarder is not found'
            || $errMsg == 'group is not found'
            || $errMsg == 'Unknown Mailing List') {
            $errCode = self::CLI_UNKNOWN;
            return new CommunigateNotFoundException($errMsg, $errCode, $lastCommand, $lastResponse);
        }

        return new CommunigateException($errMsg, $errCode, $lastCommand, $lastResponse);
    }
}
