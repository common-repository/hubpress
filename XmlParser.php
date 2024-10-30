<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class XmlParser {

    private $_simpleXml;

    public function __construct($source) {

        if (is_object($source)) {
            /* subpart of the whole XML file */
            $this->_simpleXml = $source;
        } else {
            /* first load */
            $source = str_replace('content:encoded','contentencoded',$source);
            $this->_simpleXml = simplexml_load_string($source, 'SimpleXMLElement', LIBXML_NOCDATA); //simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
    }

    public function getNodes($nodeName) {
        /* returns array of simple_xmls */
        $return = array();
        
       
            foreach (@$this->_simpleXml->$nodeName as $category) {
                $return[] = new XmlParser($category);
            }
       
        return $return;
    }

    public function getNode($nodeName) {
        /* returns array of simple_xmls */
        $return = array();
       
        return new XmlParser($this->_simpleXml->$nodeName);

        return false;
    }

    public function getValue() {
        return (string) $this->_simpleXml[0];
    }

    public function getAttribute($name) {

      
        
        if (isset($this->_simpleXml->$name)) {
            return (string) $this->_simpleXml->$name;
        } else if (isset($this->_simpleXml[$name])) {
            return (string) $this->_simpleXml[$name];
        } else {
            return false;
        }
    }

    public function asArray() {
        $json = json_encode($this->_simpleXml);
        return json_decode($json, TRUE);
    }

}
