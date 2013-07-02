<?php
/**
 * CommunigateDictionary.php
 * @author M. Seifert <m.seifert@syseleven.de
 * @package 
 * @subpackage
 */
namespace SysEleven\CgpEleven\Serializer;

use SysEleven\CgpEleven\Serializer\SerializerInterface;
use SysEleven\CgpEleven\Serializer\SerializerException;
 
/**
 * Serializer for communigate pro objects. encodes and decodes objects into
 * communigate dictionaries or vice versa
 *
 *
 * @author M. Seifert <m.seifert@syseleven.de
 * @package SysEleven\CgpEleven\Serializer
 */ 
class CommunigateDictionary implements SerializerInterface
{
    
    /**
     * @type int
     */
    protected $translateStrings = 0;

    /**
     * @type string
     */
    protected $_data = '';

    /**
     * Current character
     * @type int
     */
    protected $_span = 0;

    /**
     * @type int
     */
    protected $_len = 0;

    /**
     * Serializes the given data into a string
     *
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data)
    {
        if (is_null($data)) {
            return '';
        }

        // serializing
        if (is_array($data)) {
            $firstKey = key($data);
            $aType = "num";
            if (preg_match('/\D/', $firstKey)) {
                $aType = "assoc";
            }

            if ($aType == 'assoc') {
                $out = array();
                foreach ($data AS $k => $v) {
                    $out[] = sprintf('%s=%s;',
                        $this->serialize($k, $this->translateStrings),
                        $this->serialize($v, $this->translateStrings));
                }

                return "{".implode('',$out)."}";
            }

            if ($aType == 'num') {
                $out = array();
                foreach ($data AS $v) {
                    $out[] = $this->serialize($v, $this->translateStrings);
                }

                return "(".implode(',',$out).")";
            }
        }

        // Sanitizing the string
        $matches = array();
        if (preg_match('/[\W_]/', $data, $matches) || $data == '') {
            if ($this->translateStrings) {
                $data = preg_replace(
                    '/\\((?![enr\d]))/', "\\\\"
                    . "$matches[1]", $data
                );
                $data = str_replace('\"', '\\\"', $data);
            }

            $data = preg_replace(
                "/([\\x00-\\x1F\\x7F])/e",
                "'\\' .
                    str_repeat('0',( 3 - strlen(ord('\\1')) ) ) .
                    ord('\\1')", $data
            );

            return '"' . $data . '"';
        }

        return $data;
    }

    /**
     * Deserializes the given string into a complex form
     *
     * @param string $str Data to unserialize
     *
     * @return mixed
     */
    public function deserialize($str)
    {
        $this->_data = $str;
        $this->_span = 0;
        $this->_len  = strlen($str);

        return $this->_readValue();
    }
    
    
    /**
     * tries to determine what kind of element comes next.
     *
     * @return mixed
     * @throws SerializerException
     */
    protected function _readValue()
    {
        $this->_skipSpaces();
        $ch = substr($this->_data, $this->_span, 1);
        if ($ch == '{') {
            ++$this->_span;

            return $this->_readDictionary();
        }

        if ($ch == '(') {
            ++$this->_span;

            return $this->_readArray();
        }

        return $this->_readWord();
    }

    /**
     * @return string
     */
    protected function _readWord()
    {
        $isQuoted = 0;
        $isBlock  = 0;
        $result   = '';
        $this->_skipSpaces();
        if (substr($this->_data, $this->_span, 1) == '"') {
            $isQuoted = 1;
            ++$this->_span;
        } elseif (substr($this->_data, $this->_span, 1) == '[') {
            $isBlock = 1;
        }

        while ($this->_span < $this->_len) {
            $ch = substr($this->_data, $this->_span, 1);
            if ($isQuoted) {
                if ($ch == '\\') {
                    if (preg_match(
                        '/^(?:\"|\\|\d\d\d)/',
                        substr($this->_data, $this->_span + 1, 3)
                    )
                    ) {
                        $ch = substr($this->_data, ++$this->_span, 3);
                        if (preg_match('/\d\d\d/', $ch)) {
                            $this->_span += 2;
                            $ch = chr($ch);
                        } else {
                            $ch = substr($ch, 0, 1);
                            if (!$this->translateStrings) {
                                $ch = '\\' . $ch;
                            }
                        }
                    }
                } elseif ($ch == '"') {
                    ++$this->_span;
                    break;
                }

            } elseif ($isBlock) {
                if ($ch == ']') {
                    $result .= $ch;
                    ++$this->_span;
                    break;
                }
            } elseif (preg_match('/[-a-zA-Z0-9\x80-\xff_\.\@\!\#\%\:]/', $ch)) {
                // do nothing
            } else {
                break;
            }
            $result .= $ch;
            ++$this->_span;
        }

        return $result;
    }

    /**
     *  Reads the current key
     */
    protected function _readKey()
    {
        return $this->_readWord();
    }

    /**
     * Parses a communigate array block
     *
     * @return array
     * @throws SerializerException
     */
    protected function _readArray()
    {
        $result = array();
        while ($this->_span < $this->_len) {
            $this->_skipSpaces();
            if (substr($this->_data, $this->_span, 1) == ')') {
                ++$this->_span;
                break;
            }

            $theValue = $this->_readValue();
            $this->_skipSpaces();
            array_push($result, $theValue);

            if (substr($this->_data, $this->_span, 1) == ',') {
                // comma break
                ++$this->_span;
                continue;
            }
            if (substr($this->_data, $this->_span, 1) == ')') {
                continue;
            }

            // Everything else is an error
            $msg = "CGPro output format error:" . substr($this->_data, $this->_span, 10);
            throw new SerializerException($msg, 500);

        }

        return ($result);
    }

    /**
     * Parses through a dictionary block
     *
     * @throws SerializerException
     * @return array
     */
    protected function _readDictionary()
    {
        $result = array();
        while ($this->_span < $this->_len) {
            $this->_skipSpaces();
            if (substr($this->_data, $this->_span, 1) == '}') {
                ++$this->_span;
                break;
            }

            $theKey = $this->_readKey();
            $this->_skipSpaces();

            // Every key must be followed by a "="
            if (substr($this->_data, $this->_span, 1) != '=') {
                $msg = "CGPro output format error at '=': " . substr($this->_data, $this->_span, 10);
                throw new SerializerException($msg, 500);
            }

            ++$this->_span;
            $result["$theKey"] = $this->_readValue();
            $this->_skipSpaces();

            // Every value must be terminated by a ";"
            if (substr($this->_data, $this->_span, 1) != ';') {
                $msg = "CGPro output format error while reading value: ". substr($this->_data, $this->_span, 10);
                throw new SerializerException($msg, 500);
            }
            ++$this->_span;
        }

        return ($result);
    }

    /**
     * Sets the $this->_len to the next position that does not contain a
     * whitespace
     *
     * @return boolean
     */
    protected function _skipSpaces()
    {
        while ($this->_span < $this->_len
            && preg_match('/\s/', substr($this->_data, $this->_span, 1))) {
            ++$this->_span;
        }

        return true;
    }
}
