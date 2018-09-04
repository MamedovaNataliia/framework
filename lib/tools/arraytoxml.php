<?php

namespace Aniart\Main\Tools;

/**
 * ArrayToXml: A class to convert array in PHP to XML
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 *
 * Usage:
 *       $xml = ArrayToXml::createXML('root_node_name', $php_array);
 *       echo $xml->saveXML();
 */

class ArrayToXml {

    private static $xml = null;
	private static $encoding = 'UTF-8';

    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new \DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     */
    public static function &createXML($node_name, $arr=array()) {
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($node_name, $arr));

        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $xml;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     */
    private static function &convert($node_name, $arr=array()) {

        //print_arr($node_name);
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);

        if(is_array($arr)){
            // get the attributes first.;
            if(isset($arr['@attributes'])) {
                foreach($arr['@attributes'] as $key => $value) {
                    if(!self::isValidTagName($key)) {
                        throw new \Exception('[ArrayToXml] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if(isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if(isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if(is_array($arr)){
        	
        	
        	
            // recurse to get the node for that key
            foreach($arr as $key=>$value){
            	
                if(!self::isValidTagName($key)) {
                    throw new \Exception('[ArrayToXml] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                
                if($key == "ЗначениеСвойства" && count($value['Значение'])>1){
                	if(is_array($value) && !$value['Сериализовано']){
                		
                		foreach($value['Значение'] as $k => $val_value){	
                			
                			$element2 = $xml->createElement('Значение', $val_value);
                			$element = $xml->createElement('ЗначениеСвойства');
                			//foreach($val_value as $key_val => $val){
	                			$element3 = $xml->createElement('Значение', $val_value);
	                			$element->appendChild($element3);
                			//}
                			$node->appendChild($element2);
                			$node->appendChild($element);
                		}
                		
                	}else{
                		$node->appendChild(self::convert($key, $value));
                	}
                }elseif(is_array($value) && is_numeric(key($value))) { 
                	
                	if($key == "ВариантыЗначений" && count($value)>1 && $arr["БитриксТипСвойства"] == "L"){
                		
                		$element = $xml->createElement('ВариантыЗначений');
                		foreach($value as $key_value => $val_value){
	                		foreach($val_value as $key_val => $val){
	                			if(is_array($val)){
	                				$element2 = $xml->createElement('Вариант');
	                				foreach($val as $k =>$v){
	                					$element3 = $xml->createElement($k, $v);
	                					$element2->appendChild($element3);
	                				}
	                				$element->appendChild($element2);
	                			}else{
	                				$element2 = $xml->createElement($key_val, $val);
	                				$element->appendChild($element2);
	                			}
	                		}
                		}
                		$node->appendChild($element);
                	}else{
	                    // MORE THAN ONE NODE OF ITS KIND;
	                    // if the new array is numeric index, means it is array of nodes of the same kind
	                    // it should follow the parent key name
	                    foreach($value as $k=>$v){
	                        $node->appendChild(self::convert($key, $v));
	                    }
                	}
                } else {
                	
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if(!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }

    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v){
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag){
        //$pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
		$pattern = '/^[a-zа-я_]+[a-zа-я0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}
?>