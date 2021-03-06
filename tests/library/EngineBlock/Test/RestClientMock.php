<?php

class EngineBlock_Test_RestClientMock
{
    protected $_method = NULL;
    protected $_params = array();
    protected $_methods = array("getMetadata", "getIdpList", "getSpList", "findIdentifiersByMetadata", "isConnectionAllowed", "arp");
    
    public function __construct()
    {
        $this->_params = array("keys"=>NULL, "key"=>NULL, "value"=>NULL, "entityid"=>NULL, "spentityid"=>NULL, "idpentityid"=>NULL);
    }
    
    public function __call($call, $args)
    {
        if (array_key_exists($call, $this->_params)) {
            $this->_params[$call] = $args[0];
        } else if (in_array($call, $this->_methods)) {
            $this->_method = $call;
        }
        // fluent interface.
        return $this;
    }
    
    public function get()
    {
        // Mock a number of test scenarios
        if ($this->_method=="getMetadata" && $this->_params["keys"]==NULL) {
            return array("name:en"=>"Ivo's SP", "description:en"=>"A description", "certData"=>"aaaaabbbbb");
        }
        if ($this->_method=="getMetadata" && $this->_params["keys"]!=NULL && strpos($this->_params["keys"], ",")!==false) {
            return array("name:en"=>"Ivo's SP", "description:en"=>"A description");
        }
        if ($this->_method=="getMetadata" && $this->_params["keys"]!=NULL) {
            return array("certData"=>"aaaaabbbbb");
        }
        if ($this->_method=="isConnectionAllowed") {
            if ($this->_params["spentityid"]=="http://ivotestsp.local" && $this->_params["idpentityid"]=="http://ivoidp") {
                return array("allowed"=>"yes");
            }
            return array("allowed"=>"no");
        }         
        if ($this->_method=="arp") {
            return array("name"=>"someArp", "description"=>"This is a test arp", "attributes"=>array("sn", "url:en", "name:en", "description:en"));    
        }
        if ($this->_method=="findIdentifiersByMetadata") {
            return array("http://ivotestsp.local");
        }
        if ($this->_method=="getIdpList" && isset($this->_params["spentityid"])) {
            return array("http://ivotestidp.local"=>array("name:en"=>"Ivo's IDP", "description:en"=>"A description"));
        }
        if ($this->_method=="getIdpList") {
            return array("http://ivotestidp.local"=>array("name:en"=>"Ivo's IDP", "description:en"=>"A description"),
                         "http://ivotestidp2.local"=>array("name:en"=>"Another IDP", "description:en"=>"Another description"));
        }
        if ($this->_method=="getSpList") {
            return array("http://ivotestsp.local"=>array("name:en"=>"Ivo's SP", "description:en"=>"A description"),
                         "http://ivotestsp2.local"=>array("name:en"=>"Another SP", "description:en"=>"Another description"));
        }
 
        return NULL;
       }
}