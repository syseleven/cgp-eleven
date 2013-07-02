<?php
/**
*	Macnews Core Klassen.
*	
 *	@package  smapi
 *  @subpackage cli
*/
namespace SysEleven\CgpEleven\Exception;


use SysEleven\CgpEleven\Adapter\AdapterAbstract AS Adapter;
/**
*	Fehlerklasse fuer CG-Command Line Interface Fehler.
*	@author M. Seifert
*	@version 0.9 
 *	@package  smapi
 *  @subpackage cli
*/
class CommunigateException extends \Exception{
	
	/**
	*	@type string $lastCommand, letzes CG Kommando
	*/
	public $lastCommand = null;
	
	/**
	*	@type string $lastResponse, Letzte Ausgabe
	*/
	public $lastResponse = null;
	
	
	/**
	*	constructor();
	*	@param string $errMsg, Errormessage
	*	@param integer $errCode, Fehlercode
	*	@param mixed $lastCommand, Letztes Kommando
	*	@param mixed $lastResponse, Letzte Anwort
	*	@type integer $errMode, Pear_Error Mode
	*/
	function __construct($errMsg = null,
							  $errCode = null,
							  $lastCommand = null,
							  $lastResponse = null){

		$this->lastCommand = $lastCommand;
		$this->lastResponse = $lastResponse;
		
		if ($errMsg == 'domain with this name already exists'){
		    $errCode = Adapter::CLI_DOMAIN_EXISTS;
		}

        if ($errMsg == 'unknown secondary domain name') {
            $errCode = Adapter::CLI_DOMAIN_UNKNOWN;
        }

        $errCode = intval($errCode);
		
		parent::__construct($errMsg, $errCode, null);
	}
	
	/**
	*	Liefert den Wert von $this->lastCommand zurueck
	*	@return string 
	*/
	function getCommand(){
		return $this->lastCommand;
	}
	
	/**
	*	Liefert den Wert von $this->lastResponse zurueck
	*	@return string 
	*/
	function getResponse(){
		return $this->lastResponse;
	}
}
?>
