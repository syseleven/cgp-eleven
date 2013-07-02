<?php
/**
 * Pwd.php
 * @author M. Seifert <m.seifert@syseleven.de
 * @package 
 * @subpackage
 */

namespace SysEleven\CgpEleven\Adapter;

use SysEleven\CgpEleven\Adapter\AdapterAbstract;
use SysEleven\CgpEleven\Exception\CommunigateException;
use SysEleven\CgpEleven\Exception\CommunigateNotFoundException;

 
/**
 * Pwd
 * @author M. Seifert <m.seifert@syseleven.de
 * @package 
 * @subpackage
 */ 
class Pwd extends AdapterAbstract
{

    protected $_username = null;

    protected $_password = null;

    protected $_server = null;

    protected $_port = null;

    /**
     * Server socket
     * @type null
     */
    protected $_sp = null;

    /**
     * @param string $server
     * @param string $port
     * @param string $username
     * @param string $password
     *
     * @return $this
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    public function login($server = null, $port = null, $username = null, $password = null)
    {
        $username = (empty($username)) ? $this->_username : $username;
        $password = (empty($password)) ? $this->_password : $password;
        $server   = (empty($server))   ? $this->_server : $server;
        $port     = (empty($PeerPort)) ? $this->_port : $port;

        // Must have a login and password
        if (empty($username)) {
            throw new \BadMethodCallException('No valid username provided');
        }
        if (empty($password)) {
            throw new \BadMethodCallException('No password provided');
        }

        $errNo    = null;
        $errStr   = null;
        $this->_sp = @fsockopen($server, $port, $errNo, $errStr);
        if (!$this->_sp) {
            $this->sp = null;

            throw new \RuntimeException("Can't connect to host : "
                . $errStr . ' (' . $errNo . ') ');
        }

        // set our created socket for $sp to
        // non-blocking mode so that our fgets()
        // calls will return with a quickness
        // stream_set_blocking ( $this->sp,0);

        // get greeting
        $out = '';
        while ($out == '') {
            $out = fgets($this->_sp, 1024 * 1024);
        }

        $this->debug($out);

        // reset our socket pointer to blocking mode,
        // so we can wait for communication to finish
        // before moving on ...
        stream_set_blocking($this->_sp, 1);

        // secure login -- grab what we need from greeting
        $matches = array();
        preg_match('/(\<.*\@*\>)/', $out, $matches);
        $cmd = 'APOP ' . $username . ' ' . md5($matches[1] . $password);
        $this->executeCommand($cmd);

        // Set to INLINE mode
        $this->executeCommand('INLINE');

        return $this;
    }

    /**
     * Builds the command, $command has to be a string holding the name of
     * the command, $parameters is an array with the parameters to issue to it.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function buildCommand($command, array $parameters = array())
    {
        if (0 == strlen($command)) {
            throw new \BadMethodCallException('No valid command provided');
        }

        $_cmd = sprintf('_build%',ucfirst(strtolower($command)));

        if (method_exists($this, $_cmd)) {
            return $this->$_cmd($parameters);
        }

        $_cmd = sprintf('%s',$command);

        foreach($parameters AS $parameter) {
            if (is_string($parameter)) {
                $_cmd = sprintf('%s %s',$_cmd, $parameter);
                continue;
            }

            if (is_array($parameter) && 0 != count($parameter)) {
                $_cmd = sprintf('%s %s',$_cmd, $this->serialize($parameter));
            }
        }

        return $_cmd;
    }

    /**
     * Issues the prepared command to the server and parses the response
     *
     * @param string $command
     *
     * @throws CommunigateException
     * @return mixed
     */
    public function sendCommand($command)
    {
        $this->_send($command)->_parseResponse();

        if (!$this->isSuccess()) {
            throw $this->createException($this->_errMsg, $this->_errCode);
        }

        return $this->getSerializer()->deserialize($this->getLastResponse());
    }

    /**
     * Sends the to the server
     *
     * @throws CommunigateException
     */
    public function _send($command)
    {
        $this->_lastCommand = '';
        $this->_inlineResponse = '';

        $this->setLastCommand($command);

        $this->debug(sprintf('SENT: fputs("$this->sp,%s")',$command));

        if (!is_resource($this->_sp)) {
            $this->login();
        }

        fputs($this->_sp, $command."\n");

        return $this;
    }

    /**
     * Serializes the the given data into a communigate dictionary
     *
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data = null)
    {
        return $this->getSerializer()->serialize($data);
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function deserialize($data = null)
    {
        return $this->getSerializer()->deserialize($data);
    }

    /**
     * Reads the response from the input and tries to determine if
     * there was an error.
     *
     *
     * @throws \SysEleven\CgpEleven\Exception\CommunigateException
     * @return boolean true|false
     */
    protected function _parseResponse()
    {
        $line      = '';
        $lastChars = '';
        while ($lastChars != "\r\n") {
            $line .= fgets($this->_sp, (1024 * 1024));
            $lastChars = substr($line, -2);
        }

        $this->debug($line);

        $matches = array();
        if (!preg_match('/^(\d+)\s(.*)$/', $line, $matches)) {
            throw $this->createException(trim($line), 500);
        }
        $this->_errMsg  = rtrim($matches[2]);
        $this->_errCode = $matches[1];

        if (!in_array($matches[1], array(200, 201))) {
            throw $this->createException($this->_errMsg, $this->_errCode);
        }

        if ($matches[1] == 201) {
            $this->_inlineResponse = $matches[2];
            $this->_errMsg         = "OK";
        }

        return $this->isSuccess();
    }

}
