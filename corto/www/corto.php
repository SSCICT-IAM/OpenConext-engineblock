<?php

/**
* Corto SAML2 Proxy Server
*
* Corto is a SAML2 proxy server that supports:
* - Single Sign On
* - Metadata
* - Virtual IdPs (make 2 or more IdPs act as one)
* - Custom assertion attribute filtering
*
* @package    Corto
* @module     Main
* @author     Mads Freek Petersen, <freek@ruc.dk>
* @licence    MIT License, see http://www.opensource.org/licenses/mit-license.php
* @copyright  2009-2010 WAYF.dk
* @version    $Id:$
*/

$baseUrl = selfUrl();

/**
 * Our location
 *
 * @example http://localhost/corto.php
 */
define('CORTO_BASE_URL', $GLOBALS['baseUrl']);

/**
 * Corto override directory
 */
//define('CORTO_APPLICATION_OVERRIDES_DIRECTORY', dirname(__FILE__).'/../../');

/**
 * Where Corto is located
 */
define('CORTO_APPLICATION_DIRECTORY', dirname(__FILE__).'/../');

/**
 * @var $ALLOWED_SERVICES array Services that Corto will execute (prevents executing random functions)
 */
$ALLOWED_SERVICES = array(
    'demoApp',
    'singleSignOnService',
    'assertionConsumerService',
    'unsolicitedAssertionConsumerService',
    'sendUnsolicitedAssertion',
    'artifactResolutionService',
    'attributeService',
    'assertionService',
    'metaDataService',
    'continue2idp',
    'continue2sp',
);

require CORTO_APPLICATION_DIRECTORY . 'lib/Corto/XmlToHash.php';
require CORTO_APPLICATION_DIRECTORY . 'lib/Corto/Demo.php';

cortoRequire('configs/config.inc.php');
cortoRequire('configs/metadata.inc.php');
cortoRequire('configs/certificates.inc.php');
cortoRequire('configs/attribute_names.inc.php');

if (!class_exists('XMLWriter')) {
    die('XMLWriter class does not exist! Please install libxml extension for php.');
}

/**
 * @var $entityCode string Entity code (array key in metadata['hosted']), default: 'main'
 */
$entityCode = '';
$service = '';
if (isset($_SERVER['PATH_INFO'])) {
    // From corto.php/hostedEntity/requestedService get the hosted entity code and the requested service
    $entityCodeAndService = preg_split('/\//', $_SERVER['PATH_INFO'], 0, PREG_SPLIT_NO_EMPTY);
    if (isset($entityCodeAndService[0])) {
        $entityCode = $entityCodeAndService[0];
    }
    if (isset($entityCodeAndService[1])) {
        $service = $entityCodeAndService[1];
    }
}

if (!$entityCode) {
    $entityCode = 'main';
}
if (!$service) {
    $service = 'demoApp';
}

// From the hosted entity name like entity name_myidp, get a hosted IDP identifier (myIdp in the example).
$entityComponents = preg_split('/_/', $entityCode, 0, PREG_SPLIT_NO_EMPTY);
$entityCode = $entityComponents[0];
$idp = '';
if (isset($entityComponents[1])) {
    $idp = $entityComponents[1];
}

/**
 * @var $entityID string URL to currently hosted entity, example: http://localhost/corto.php/main_myidp/Single
 */
$entityID = $baseUrl . $entityCode;
if (isset($metabase['hosted'][$entityID])) {
    $meta = $metabase['hosted'][$entityID];
}
else {
    // Unregistered entity... that's cool with us.
    $meta = array();
}

if ($idp) {
    $meta['idp'] = $baseUrl . $idp;
}
$meta['EntityID']   = $entityID;
$meta['entitycode'] = $entityCode;

session_set_cookie_params(0, CORTO_COOKIE_PATH, '', CORTO_USE_SECURE_COOKIES);
session_name($entityCode);
session_start();

prepareParameters();

if (in_array($service, $ALLOWED_SERVICES)) {
    $service();
}
else {
    die("Unsupported service '$service' called!");
}

function prepareParameters()
{
    if (isset($_REQUEST['SAMLArt'])) {
        handleArtifact();
    }
    else {
        convertAndVerifyMessages();
    }

    if (isset($_REQUEST['hSAMLRequest'])) {
        if (isset($_REQUEST['hSAMLResponse']['saml:EncryptedAssertion']) &&
            $encryptedAssertion = $_REQUEST['hSAMLResponse']['saml:EncryptedAssertion']) {
            if (!isset($GLOBALS['meta']['privatekey'])) {
                die("Encrypted assertion found, but private key for {$GLOBALS['meta']['EntityID']} is not registered, ".
                    "unable to decrypt it to enrich assertion.");
            }

            $_REQUEST['hSAMLResponse']['saml:Assertion'] = decryptElement(
                $GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['private'],
                $encryptedAssertion
            );
        }
    }

    if (isset($_REQUEST['hSAMLResponse'])) {
        prepareForSLO($_REQUEST['hSAMLResponse'], 'received');
    }

    checkDestinationAudienceAndTiming();
}

function convertAndVerifyMessages()
{
    if (isset($_GET['Signature'])) {
        $rawRequest = array();
        foreach(explode("&", $_SERVER['QUERY_STRING']) as $parameter) {
            if (preg_match("/^(.+)=(.*)$/", $parameter, $keyAndValue))  {
                $rawRequest[$keyAndValue[1]] = $keyAndValue[2];
            }
        }
    }
    
    foreach(array('SAMLRequest', 'SAMLResponse') as $req) {
        $message = "";
        $messageHashKey = 'h'.$req;
        if (isset($_POST[$req])) {
            // HTTP-POST binding
            $message = base64_decode($_POST[$req]);
        }
        if (isset($_GET[$req])) {
            // HTTP-Redirect binding
            $message = gzinflate(base64_decode($_GET[$req]));
        }
        if ($message) {
            $_REQUEST[$messageHashKey] = Corto_XmlToHash::xml2hash($message);
        }
        if (isset($_GET['j'.$req])) {
            $_REQUEST[$messageHashKey] = json_decode(gzinflate(base64_decode($_GET['j'.$req])), 1);
        }
        if (!isset($_REQUEST[$messageHashKey])) {
            continue;
        }
        if (isset($_REQUEST['RelayState'])) {
            $_REQUEST[$messageHashKey]['__']['RelayState'] = $_REQUEST['RelayState'];
        }
        $remoteMeta = array();
        if (isset($GLOBALS['metabase']['remote'][$_REQUEST[$messageHashKey]['saml:Issuer']['__v']])) {
            $remoteMeta = $GLOBALS['metabase']['remote'][$_REQUEST[$messageHashKey]['saml:Issuer']['__v']];
        }

        $verify = ($req == 'SAMLRequest' && ((isset($remoteMeta['AuthnRequestsSigned']) && $remoteMeta['AuthnRequestsSigned']) || isset($GLOBALS['meta']['WantAuthnRequestsSigned']) && $GLOBALS['meta']['WantAuthnRequestsSigned']))
                  ||
                  ($req == 'SAMLResponse' && isset($GLOBALS['meta']['WantAssertionsSigned']) && $GLOBALS['meta']['WantAssertionsSigned']);
        if ($verify) {
            if (isset($remoteMeta['sharedkey']) && $sharedKey = $remoteMeta['sharedkey']) {
                if (isset($_GET['Signature']) && $_GET['Signature']) {
                    $message = "j$req=" . $rawRequest['j'.$req] . (($relayState = $rawRequest['RelayState']) ? '&RelayState=' . $relayState : '');
                }
                else {
                    $message = $_POST['j'.$req];
                }
                if (base64_encode(sha1($sharedKey . sha1($message))) != $_REQUEST['Signature']) {
                    die('Integrity check failed (Sharedkey)');
                }
            } elseif (isset($_GET['Signature']) && $signature = $_GET['Signature']) {
                $message = "$req=" . $rawRequest[$req];
                $message .= (($relayState = $rawRequest['RelayState']) ? '&RelayState=' . $relayState : '');
                $message .= '&SigAlg=' . $rawRequest['SigAlg'];
                $verified = openssl_verify($message, base64_decode($signature), $GLOBALS['certificates'][$_REQUEST[$messageHashKey]['saml:Issuer']['__v']]['public']);
                if ($verified != 1) {
                    die('Integrity check failed (PKI)');
                }
            } else {
                if (!isset($GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['public'])) {
                    die("No public key found for {$GLOBALS['meta']['EntityID']}");
                }

                $verified = verify($GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['public'], $message, $_REQUEST[$messageHashKey])
                            ||
                            verify($GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['public'], $message, $_REQUEST[$messageHashKey]['saml:Assertion']);
                if (!$verified) {
                    die("Could not validate " . print_r($_REQUEST[$messageHashKey],1));
                }
            }
        }
        if ($req == 'SAMLRequest') {
            $forceAuthentication = &$_REQUEST[$messageHashKey]['_ForceAuthn'];
            $forceAuthentication = $forceAuthentication == 'true' || $forceAuthentication == '1';
            $isPassive  = &$_REQUEST[$messageHashKey]['_IsPassive'];
            $isPassive  = $isPassive == 'true' || $isPassive == '1';
        }
    }
}

function demoApp()
{
    Corto_Demo::demoApp();
}

/**
 * Handle a Single Sign On request (Authentication Request)
 */
function singleSignOnService()
{
    $request = $_REQUEST['hSAMLRequest'];

    // Add the hosted IdP as a scoped IdP
    $scopedIDPs = array();
    if ($GLOBALS['meta']['idp']) {
        $scopedIDPs[] = $GLOBALS['meta']['idp'];
    }

    // If ForceAuthn attribute is on, then remove cached responses and cached IDPs
    if ($request['_ForceAuthn']) {
        unset($_SESSION['CachedResponses']);
    }

    // Add scoped IdPs (allowed IDPs for reply) from request to allowed IdPs for responding
    if ($IDPList = $request['samlp:Scoping']['samlp:IDPList']['samlp:IDPEntry']) {
        foreach($IDPList as $IDPEntry) {
            $scopedIDPs[] = $IDPEntry['_ProviderID'];
        }
    }

    // If one of the scoped IDP has a cache entry, return that
    $cachedIDPs = array_keys((array)$_SESSION['CachedResponses']);
    if ($commonIDPs = array_intersect($cachedIDPs, $scopedIDPs) || (sizeof($scopedIDPs) == 0 && $commonIDPs = $cachedIDPs)) {
        sendResponse($request, createResponse($request, null, null, $_SESSION['CachedResponses'][$commonIDPs[0]]));
    }

    // If the scoped proxycount = 0, respond with a ProxyCountExceeded error
    if (isset($request['samlp:Scoping']['_ProxyCount']) && $request['samlp:Scoping']['_ProxyCount'] == 0) {
        sendResponse($request, createResponse($request, 'ProxyCountExceeded'));
    }

    // If we configured an allowed IDPList then we ignore the original scoping rules
    // and add that one IDP as allowed IDP and send the new authnrequest
    if ($scope = $GLOBALS['meta']['IDPList']) {
        sendAuthnRequest($GLOBALS['meta']['idp'], $scope);
    }

    // If we have an IdP configured then we send the authentication request to that IdP
    if ($idp = $GLOBALS['meta']['idp']) {
        sendAuthnRequest($idp);
    }

    // If we have a virtual IdP defined (multiple IdPs that Corto merges into one), use that.
    if (isset($GLOBALS['meta']['virtual'])) {
        handleVirtualIDP();
    }

    // Get all registered Single Sign On Services
    $candidateIDPs = array();
    foreach($GLOBALS['metabase']['remote'] as $idp => $metaData) {
        if ($metaData['SingleSignOnService']) {
            $candidateIDPs[] = $idp;
        }
    }

    // Filter out the hosted entity and if we have scoping, filter out every non-scoped IdP
    $candidateIDPs = array_diff($candidateIDPs, array($GLOBALS['meta']['EntityID']));
    $candidateIDPs = sizeof($scopedIDPs) > 0 ? array_intersect($scopedIDPs, $candidateIDPs) : $candidateIDPs;

    // More than 1 candidate found, send authentication request to the first one
    if (count($candidateIDPs) === 1) {
        sendAuthnRequest($candidateIDPs[0]);
    }

    // No IdPs found! Send an error response back.
    if (count($candidateIDPs) === 0) {
        sendResponse($request, createResponse($request, 'NoSupportedIDP'));
    }

    // discover should take are of IsPassive ...
    discover($candidateIDPs);
}

function assertionConsumerService()
{
    $response = $_REQUEST['hSAMLResponse'];
    inFilter($response);
    if (isset($GLOBALS['meta']['keepsession']) && $GLOBALS['meta']['keepsession']) {
        $_SESSION['CachedResponses'][$response['saml:Issuer']['__v']] = $response; 
    }

    $id = isset($_POST['target']) ? $_POST['target'] : $response['_InResponseTo'];
    if (!$id) {
        unsolicitedAssertionConsumerService();
    }

    if (!isset($_SESSION[$id])) {
        echo "Unknown id ($id) in InResponseTo attribute?!?";

        if (CORTO_TRACE) {
            echo "<br /><br />SESSION:<br /><pre>";
            var_dump($_SESSION);
            echo "</pre>";
        }
        exit;
    }

    if (!isset($_SESSION[$_SESSION[$id]['_InResponseTo']]['hSAMLRequest'])) {
        die('No origRequest: ' . $_SESSION[$id]['_InResponseTo']);
    }
    $originRequest = $_SESSION[$_SESSION[$id]['_InResponseTo']]['hSAMLRequest'];

    #unset($_SESSION[$id]['_InResponseTo']);
    $response = createResponse($originRequest, null, null, $response);

    sendResponse($originRequest, $response);
}

function unsolicitedAssertionConsumerService()
{
    # just return to either the RelayState or the default unsolicited service
    $relayState = $_REQUEST['hSAMLResponse']['__']['RelayState'];
    redirect($relayState);
}

function sendUnsolicitedAssertion()
{
    $originRequest['__']['RelayState'] = CORTO_BASE_URL."/main/demoapp";
    $originRequest['saml:Issuer']['__v'] = CORTO_WAYF_URL;

    $response = createResponse($originRequest, null, array('unsolicitedresponse' => array('yes')));

    sendResponse($originRequest, $response);
}

function artifactResolutionService()
{
    $postData = Corto_XmlToHash::xml2hash(file_get_contents("php://input"));
    $artifact = $postData['SOAP-ENV:Body']['samlp:ArtifactResolve']['saml:Artifact']['__v'];
    newSession(sha1($artifact), 'artifact');
    $message = $_SESSION['message'];
    session_destroy();
    $element = $message['__t'];
    $artifactResponse = array(
        'samlp:ArtifactResponse' => array(
            'xmlns:samlp'   => 'urn:oasis:names:tc:SAML:2.0:protocol',
            'xmlns:saml'    => 'urn:oasis:names:tc:SAML:2.0:assertion',
            'ID'            => ID(),
            'Version'       => '2.0',
            'IssueInstant'  => timeStamp(),
            'InResponseTo'  => $postData['SOAP-ENV:Body']['samlp:ArtifactResolve']['_ID'],
            'saml:Issuer'   => array('__v' => $GLOBALS['meta']['EntityID']),
            $element        => $message,
        ),
    );
    soapResponse($artifactResponse);
}

function attributeService()
{
    $postData = Corto_XmlToHash::xml2hash(file_get_contents("php://input"));
    $response = createResponse($postData['SOAP-ENV:Body']['samlp:AttributeQuery']);
    soapResponse(array('saml:Response' => $response));
}

function assertionService()
{
    newSession($_GET['ID'], 'assertion');
    header('Content-Type: application/samlassertion+xml');
    $assertion = $_SESSION['assertion'];
    if ($assertion) {
        print Corto_XmlToHash::hash2xml($assertion, 'saml:Assertion');
    }
}

function metaDataService()
{
    $entitiesDescriptor = array(
        '_xmlns:md'=>'urn:oasis:names:tc:SAML:2.0:metadata',
        'md:EntityDescriptor'=>array()
    );
    foreach ($GLOBALS['metabase']['remote'] as $entityID => $remoteService) {
        if (!isset($remoteService['SingleSignOnService'])) continue;

        $entityDescriptor = array(
                '_validUntil'=>timeStamp(strtotime('tomorrow')-time()),
                '_entityID' => $entityID,
                'md:IDPSSODescriptor'=>array(
                    '_protocolSupportEnumeration'=>"urn:oasis:names:tc:SAML:2.0:protocol",
                    'md:NameIDFormat'=>array('__v'=>'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'),
                    'md:SingleSignOnService'=>array(
                        '_Binding' =>$remoteService['SingleSignOnService']['Binding'],
                        '_Location'=>$remoteService['SingleSignOnService']['Location'],
                    ),
                ),
        );

        if (isset($GLOBALS['certificates'][$entityID]['public'])) {
            $entityDescriptor['md:IDPSSODescriptor']['md:KeyDescriptor']=array(
                        array(
                            '_xmlns:ds'=>'http://www.w3.org/2000/09/xmldsig#',
                            '_use'=>'signing',
                            'ds:KeyInfo'=>array(
                                'ds:X509Data'=>array(
                                    'ds:X509Certificate'=>array(
                                        '__v' => $GLOBALS['certificates'][$entityID]['public'],
                                    ),
                                ),
                            ),
                        ),
                        array(
                            '_xmlns:ds'=>'http://www.w3.org/2000/09/xmldsig#',
                            '_use'=>'encryption',
                            'ds:KeyInfo'=>array(
                                'ds:X509Data'=>array(
                                    'ds:X509Certificate'=>array(
                                        '__v' => $GLOBALS['certificates'][$entityID]['public'],
                                    ),
                                ),
                            ),
                        ),
            );
        }

        $entitiesDescriptor['md:EntityDescriptor'][] = $entityDescriptor;
    }

    $xml = Corto_XmlToHash::hash2xml($entitiesDescriptor, 'md:EntitiesDescriptor', true);
    if (CORTO_DEBUG) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        if (!$dom->schemaValidate('http://docs.oasis-open.org/security/saml/v2.0/saml-schema-metadata-2.0.xsd')) {
            throw new Exception('Metadata XML doesnt validate against XSD at Oasis-open.org?!');
        }
    }
    header('Content-Type: application/xml');
    //header('Content-Type: application/samlmetadata+xml');
    print $xml;
}

function sendAuthnRequest($idp, $scope = null)
{
    $id = $_REQUEST['hSAMLRequest']['_ID'];
    $_SESSION[$id]['hSAMLRequest'] = $_REQUEST['hSAMLRequest'];
    $newRequest = createRequest($idp, $scope);
    $_SESSION[$newRequest['_ID']]['_InResponseTo'] = $id;

    send($newRequest, $GLOBALS['metabase']['remote'][$idp]);
}

function sendResponse($request, $response)
{
    if ($response['samlp:Status']['samlp:StatusCode']['_Value'] == 'urn:oasis:names:tc:SAML:2.0:status:Success') {
        outFilter($response);
        prepareForSLO($response, 'sent');

        if (!defined('CORTO_USE_CONSENT') || !CORTO_USE_CONSENT) {
            return send($response, $GLOBALS['metabase']['remote'][$request['saml:Issuer']['__v']]);
        }

        $attributes = Corto_XmlToHash::attributes2hash($response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute']);

        if (defined('CORTO_CONSENT_DB_DSN') && CORTO_CONSENT_DB_DSN!=='') {
            try {
                $dbh = new PDO(CORTO_CONSENT_DB_DSN, CORTO_CONSENT_DB_USER, CORTO_CONSENT_DB_PASSWORD);
                $table = CORTO_CONSENT_DB_TABLE;
                $query = "SELECT * FROM {$table} WHERE hashed_user_id = ? AND service_id = ? AND attribute = ?";
                $statement = $dbh->prepare($query);
                $statement->execute(array(
                    hash('sha1', $attributes['uid'][0]),
                    $request['saml:Issuer']['__v'],
                    _getAttributesID($attributes)
                ));
                $rows = $statement->fetchAll();
                if (count($rows)===1) {
                    $dbh->query('UPDATE {$table} SET usage_date = NOW() WHERE attribute = ' . $attributesID);

                    send($response, $GLOBALS['metabase']['remote'][$request['saml:Issuer']['__v']]);
                }

            } catch (PDOException $e) {
                print "Error!: " . $e->getMessage() . "<br/>";
                die();
            }
        }

        $id = $response['_ID'];
        $_SESSION['consent'][$id]['request'] = $request;
        $_SESSION['consent'][$id]['response'] = $response;

        print render(
            'consent',
            array(
                'action'     => selfUrl() . 'continue2sp',
                'ID'         => $id,
                'attributes' => $attributes,
                'c' => $GLOBALS['c'],
        ));
        exit;
    }
    unset($response['saml:Assertion']);

    send($response, $GLOBALS['metabase']['remote'][$request['saml:Issuer']['__v']]);
}


function _getAttributesID($attributes)
{
    $hashBase = NULL;
    if (CORTO_CONSENT_STORE_VALUES) {
        ksort($attributes);
        $hashBase = serialize($attributes);
    } else {
            $names = array_keys($attributes);
            sort($names);
            $hashBase = implode('|', $names);
    }
    return hash('sha1', $hashBase);
}

function continue2sp()
{
    $request  = $_SESSION['consent'][$_POST['ID']]['request'];
    $response = $_SESSION['consent'][$_POST['ID']]['response'];
    unset($_SESSION['consent'][$_POST['ID']]);

    $attributes = Corto_XmlToHash::attributes2hash($response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute']);

    if (defined('CORTO_USE_CONSENT') && CORTO_USE_CONSENT) {
        if (!isset($_POST['consent']) || $_POST['consent'] !== 'yes') {
            print render(
                'noconsent',
                array()
            );
            return;
        }

        if (defined('CORTO_CONSENT_DB_DSN') && CORTO_CONSENT_DB_DSN!=='') {
            $dbh = new PDO(CORTO_CONSENT_DB_DSN, CORTO_CONSENT_DB_USER, CORTO_CONSENT_DB_PASSWORD);
            $statement = $dbh->prepare("INSERT INTO consent (usage_date, hashed_user_id, service_id, attribute) VALUES (NOW(), ?, ?, ?)");
            $statement->execute(array(
                hash('sha1', $attributes['uid'][0]),
                $request['saml:Issuer']['__v'],
                _getAttributesID($attributes)
            ));
        }
    }

    send($response, $GLOBALS['metabase']['remote'][$request['saml:Issuer']['__v']]);
}

function discover($candidateIDPs)
{
    $request = $_REQUEST['hSAMLRequest'];
    if ($request['_IsPassive'] == 'true') {
        sendResponse($request, createResponse($request, 'NoPassive'));
    }

    $action = selfUrl() . 'continue2idp';
    $id = $request['_ID'];

    $_SESSION[$id]['hSAMLRequest'] = $request;

    print render(
        'discover',
        array(
            'action'  => $action,
            'ID'      => $id,
            'idpList' => $candidateIDPs,
            'metaDataSP'=> $GLOBALS['metabase']['remote'][$request['saml:Issuer']['__v']],
            'metaDataRemote' => $GLOBALS['metabase']['remote'],
    ));
}

function continue2idp()
{
    $_REQUEST['hSAMLRequest'] = $_SESSION[$_POST['ID']]['hSAMLRequest'];
    sendAuthnRequest($_REQUEST['idp']);
}

/**
 * Virtual IdPs are multiple IdPs is a feature that makes Corto pass the authentication request to multiple
 * IdPs and when they respond, combine the attributes into 1 assertion, making it appear as though only 1 IdP was
 * used.
 *
 * @todo So doesn't this always send back a response?
 * Like:
 *   SP -> Proxy (sends response to SP) -> IdP1
 *                                      -> idP2
 *
 *   IdP1 -> Proxy -> SP
 *   IdP2 -> Proxy -> SP
 *
 *   So all in all the SP gets 3 responses, of which only the last one is the one we intended...
 */
function handleVirtualIDP()
{
    if ($request = $_REQUEST['hSAMLRequest']) {
        unset($_SESSION['virtual']);
        $_SESSION['virtual']['hSAMLRequest'] = $request;
        $_SESSION['virtual']['idp']  = $GLOBALS['meta']['EntityID'];
        $_SESSION['virtual']['idps'] = $GLOBALS['meta']['virtual'];

    } elseif ($res = $_REQUEST['hSAMLResponse']) {
        inFilter($res);
        $aa = $res['saml:Assertion']['saml:AuthnStatement']['saml:AuthnContext']['saml:AuthenticatingAuthority'][1];
        print_r($aa);
        $_SESSION['virtual']['hSAMLResponses'][$aa['__v']] = $res;

    } else die("What! No Kissing?");

    foreach((array)$_SESSION['virtual']['idps'] as $idp) {
        if (!$_SESSION['virtual']['hSAMLResponses'][$idp]) {
            $newRequest = createRequest(CORTO_WAYF_URL, $idp);
            $newRequest['_AssertionConsumerServiceURL'] = $_SESSION['virtual']['idp'] . "/" . __FUNCTION__;

            send($newRequest, $GLOBALS['metabase']['remote'][CORTO_WAYF_URL]);
        }
    }

    $combinedAttributes = array();
    foreach((array)$_SESSION['virtual']['hSAMLResponses'] as $idp => $response) {
        $attributes = Corto_XmlToHash::attributes2hash($response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute']);
        foreach($attributes as $name => $values) {
            foreach($values as $value) {
                $combinedAttributes[$name][] = $value;
            }
        }
    }

    $originRequest = $_SESSION['virtual']['hSAMLRequest'];
    $finalResponse = createResponse($originRequest, null, $combinedAttributes);
    unset($_SESSION['virtual']);

    send($finalResponse, $GLOBALS['metabase']['remote'][$originRequest['saml:Issuer']['__v']]);
}

function handleArtifact()
{
    $artifacts = unpack('ntypecode/nendpointindex/H40sourceid/H40messagehandle', base64_decode($_REQUEST['SAMLArt']));
    $artifactResolve = array(
        'samlp:ArtifactResolve' => array(
            '_xmlns:samlp'  => 'urn:oasis:names:tc:SAML:2.0:protocol',
            '_xmlns:saml'   => 'urn:oasis:names:tc:SAML:2.0:assertion',
            '_ID'           => ID(),
            '_Version'      => '2.0',
            '_IssueInstant' => timeStamp(),
            'saml:Artifact' => array('__v' => $_REQUEST['SAMLArt']),
            'saml:Issuer'   => array('__v' => $GLOBALS['meta']['EntityID']),
        ),
    );

    $artifactResponse = soapRequest($GLOBALS['artifactResolutionServices'][$artifacts['sourceid']], $artifactResolve);
    if ($_REQUEST['hSAMLResponse'] = $artifactResponse['samlp:ArtifactResponse']['samlp:Response']) {
        $_REQUEST['hSAMLResponse']['__t'] = 'samlp:Response';
    }
    if ($_REQUEST['hSAMLRequest'] = $artifactResponse['samlp:ArtifactResponse']['samlp:AuthnRequest']) {
        $_REQUEST['hSAMLRequest']['__t'] = 'samlp:AuthnRequest';
    }
}

function soapRequest($soapService, $element)
{
    $soapEnvelope = array(
        '__t' => 'SOAP-ENV:Envelope', 
        'xmlns:SOAP-ENV' => "http://schemas.xmlsoap.org/soap/envelope/",
        'SOAP-ENV:Body' => $element,
    );

    $curlHandler = curl_init(); 
    $curlOptions = array(
        CURLOPT_URL                 => $soapService,
        CURLOPT_HTTPHEADER          => array('SOAPAction: ""'),
        CURLOPT_RETURNTRANSFER      => 1,
        CURLOPT_SSL_VERIFYPEER      => FALSE,
        CURLOPT_POSTFIELDS          => Corto_XmlToHash::hash2xml($soapEnvelope),
        CURLOPT_HEADER              => 0,
    );
    curl_setopt_array($curlHandler, $curlOptions);
    $curlResult = curl_exec($curlHandler);
    $soapResponse = Corto_XmlToHash::xml2hash($curlResult);
    return $soapResponse['SOAP-ENV:Body'];
}

function soapResponse($element)
{
    $soapResponse = array(
        '__t' => 'SOAP-ENV:Envelope', 
        'xmlns:SOAP-ENV' => "http://schemas.xmlsoap.org/soap/envelope/",
        'SOAP-ENV:Body' => $element,
    );
    print Corto_XmlToHash::hash2xml($soapResponse);
}

function send($message, $metaData)
{
    $bindings = array(
        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'        => 'sendHTTPRedirect',
        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'            => 'sendHTTPPost',
        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact'        => 'sendHTTPArtifact',
        'urn:oasis:names:tc:SAML:2.0:bindings:URI'                  => 'sendURI',
        'urn:oasis:names:tc:SAML:2.0:bindings:SOAP'                 => 'sendSOAP',
        'INTERNAL'                                                  => 'sendInternal',
        'JSON-Redirect'                                             => 'sendHTTPRedirect',
        'JSON-POST'                                                 => 'sendHTTPPost',
        null                                                        => 'sendHTTPRedirect',

        'urn:oasis:names:tc:SAML:1.0:profiles:browser-post'         => 'sendbrowserpost',
        'urn:oasis:names:tc:SAML:1.0:profiles:browser-artifact-01'  => 'sendbrowserartifact01',
        'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding'         => 'xxxx',
        'urn:mace:shibboleth:1.0:profiles:AuthnRequest'             => 'sendShibAuthnRequest',
    );
    $function = $bindings[$message['__']['ProtocolBinding']];
    $function($message, $metaData);
}

function sendHTTPRedirect($message, $metaData)
{
    $name = $message['__']['paramname'];
    $sign = $name == 'SAMLRequest' && ($metaData['WantAuthnRequestsSigned'] || $GLOBALS['meta']['AuthnRequestsSigned']);
    if (!$sign) {
        unset($message['ds:Signature']);
    }
    $location = $message['_Destination'] . $message['_Recipient']; # shib remember ...
    $newMessage = $metaData['json'] ? json_encode($message) : Corto_XmlToHash::hash2xml($message);
    $newMessage = urlencode(base64_encode(gzdeflate($newMessage)));
    $newMessage = ($message['__']['ProtocolBinding'] == 'JSON-Redirect' ? "j"  : "") . "$name=" . $newMessage;
    $newMessage .= $message['__']['RelayState'] ? '&RelayState=' . urlencode($message['__']['RelayState']) : "";
    $newMessage .= $message['__']['target'] ? '&target=' . urlencode($message['__']['target']) : "";
    if ($sharedKey = $metaData['sharedkey']) {
        $newMessage .= "&Signature=" . urlencode(base64_encode(sha1($sharedKey . sha1($newMessage))));
    } elseif ($sign) {
        $newMessage .= '&SigAlg=' . urlencode(CORTO_SIGNING_ALGORITHM);
        $key = openssl_pkey_get_private($GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['private']);
        openssl_sign($newMessage, $signature, $key);
        openssl_free_key($key);
        $newMessage .= '&Signature=' . urlencode(base64_encode($signature));
    }
    $location .=  "?" . $newMessage;
    redirect($location, $message);
}

function sendHTTPPost($message, $metaData)
{
    $name = $message['__']['paramname'];
    $action = $message['_Destination'] . $message['_Recipient'];
    if ($message['__']['ProtocolBinding'] == 'JSON-POST') {
        if ($rs = $message['__']['RelayState']) {
            $rs = "&RelayState=$rs";
        }
        $name = 'j' . $name;
        $encodedMessage = json_encode($message);
        $signatureHTMLValue = htmlspecialchars(base64_encode(sha1($metaData['sharedkey'] . sha1("$name=$message$rs"))));
        $extra .= '<input type="hidden" name="Signature" value="' . $signatureHTMLValue . '">';

    } else {
        if ($name == 'SAMLRequest' && ($metaData['WantAuthnRequestsSigned'] || $GLOBALS['meta']['AuthnRequestsSigned'])) {
            $message = sign(
                $GLOBALS['certificates'][$GLOBALS['meta']['entitycode']]['private'],
                $message
            );
        }
        else if ($name == 'SAMLResponse' && $metaData['WantAssertionsSigned']) {
            $message['saml:Assertion']['__t'] = 'saml:Assertion';
            $message['saml:Assertion']['_xmlns:saml'] = "urn:oasis:names:tc:SAML:2.0:assertion";
            unset($message['saml:Assertion']['ds:Signature']);
            ksort($message['saml:Assertion']);

            $message['saml:Assertion'] = sign(
                $GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['private'],
                $message['saml:Assertion']
            );
            ksort($message['saml:Assertion']);
            #$enc = docrypt(certs::$server_crt, $message['saml:Assertion'], 'saml:EncryptedAssertion');

        }
        else if ($name == 'SAMLResponse' && $metaData['WantResponsesSigned']) {
            $message = sign(
                $GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['private'],
                $message
            );
        } 
        $encodedMessage = Corto_XmlToHash::hash2xml($message);
    }

    $extra = $message['__']['RelayState'] ? '<input type="hidden" name="RelayState" value="' . htmlspecialchars($message['__']['RelayState']) . '">' : '';
    $extra .= $message['__']['target'] ? '<input type="hidden" name="target" value="' . htmlspecialchars($message['__']['target']) . '">' : '';
    $encodedMessage = htmlspecialchars(base64_encode($encodedMessage));
    print render(
        'form',
        array(
            'action'    => $action,
            'message'   => $encodedMessage,
            'xtra'      => $extra,
            'name'      => $name,
            'trace'     => debugRequest($action, $message),
    ));
    exit;
}

function sendHTTPArtifact($message, $metadata, $artifactType = 4)
{
    if ($artifactType == 1) {
        $initial = pack('n', 1);
    }
    else {
        $initial = pack('n', 4) . pack('n', 0);
    }
    $artifact = base64_encode($initial . sha1($message['saml:Issuer']['__v'], true) . ID());
    if ($keyfile = $GLOBALS['meta']['key']) {
       // @todo not implemented yet ... 
    }
    newSession(sha1($artifact), 'artifact');
    $_SESSION['message'] = $message; 
    $location = $message['_Destination'] . "?SAMLArt=" . urlencode($artifact);
    $location .= $message['__']['RelayState'] ? '&RelayState=' . urlencode($message['__']['RelayState']) : "";
    $location .= $message['__']['target'] ? '&target=' . urlencode($message['__']['target']) : "";

    redirect($location);
}

function sendURI($message, $metadata)
{
    $id = ID();
    newSession($id, 'assertion');
    $_SESSION['assertion'] = $message['saml:Assertion'];
    unset($message['saml:Assertion']);
    $message['saml:AssertionURIRef']['__v'] = $GLOBALS['meta']['EntityID'] . '/assertionService?ID=' . urlencode($id);
    $location .= $message['__']['RelayState'] ? '&RelayState=' . urlencode($message['__']['RelayState']) : "";

    redirect($location);
}

function sendBrowserPost($message, $metadata)
{
    saml2shib($message);
    sendHTTPPost($message, $metadata);
}

function sendBrowserArtifact01($message, $metadata)
{
    saml2shib($message);
    sendHTTPArtifact($message, $metadata, 1);
}

function sendShibAuthnRequest($message, $metadata)
{
    $location = $message['_Destination'];
    $location .= '?shire='      . urlencode($message['_AssertionConsumerServiceURL']);
    $location .= '&providerId=' . urlencode($message['saml:Issuer']['__v']);
    $location .= '&target='     . urlencode($message['_ID']);

    redirect($location, $message);
}

 function sendInternal($message, $metadata)
 {
    $name = $message['__']['paramname'];
    $_REQUEST['h'.$name] = $message;
    $GLOBALS['meta'] = $GLOBALS['metabase']['hosted'][$message['__']['destinationid']];
    preg_match("/([^\/]+)$/", $message['__']['destinationid'], $dollar);
    $GLOBALS['meta']['entitycode'] = $dollar[1];
    preg_match("/([^\/]+)$/", $message['_Destination'], $dollar);
    $dollar[1]();
    exit;
}

function shibSingleSignOnService()
{
    $request = array(
        '__t' => 'samlp:AuthnRequest',
        '__target' => $_GET['target'],
        '_xmlns:samlp' => 'urn:oasis:names:tc:SAML:2.0:protocol',
        '_ID' => ID(),
        '_Version' => '1.0',
        '_IssueInstant' => $_GET['time'],
        '_AssertionConsumerServiceURL' =>  $_GET['shire'],
        'saml:Issuer' => array('__v' => $_GET['providerId']),
    );
    $assertionConsumerService = $GLOBALS['metabase']['remote'][$_GET['providerId']]['AssertionConsumerService'];
    // @todo prepare for multi AssertionConsumerServices in the future ...
    if ($_GET['shire'] == $assertionConsumerService['Location']) {
        $request['_ProtocolBinding'] = $assertionConsumerService['Binding'];
    }
    if (!$request['_ProtocolBinding']) {
        sendResponse($request, createResponse($request, 'Requester'));
    }
    $_REQUEST['hSAMLRequest'] = $request;
    singleSignOnService();
}

function saml2shib(&$message)
{
    // @todo more to come ...
    $message['_xmlns:samlp'] = 'urn:oasis:names:tc:SAML:1.0:protocol';
    $message['_xmlns:saml'] = 'urn:oasis:names:tc:SAML:1.0:assertion';
    $message['_MajorVersion'] = "1";
    $message['_MinorVersion'] = "1";
    $message['_ResponseID'] = $message['_ID'];
    $message['_Recipient'] = $message['_Destination'];
    unset($message['_Version'], $message['_ID'], $message['_Destination']);
}

/**
 * @todo Shouldn't $scoping contain multiple IdPs?
 *
 * @param  $idp
 * @param  $scoping
 * @return
 */
function createRequest($idp, $scoping = null)
{
    $remoteMetaData = $GLOBALS['metabase']['remote'][$idp];
    $metaData   = $GLOBALS['metabase']['remote'][$GLOBALS['meta']['EntityID']];
    $origRequest = $_REQUEST['hSAMLRequest'];
     $request = array(
        '__t' => 'samlp:AuthnRequest',
        '__' => array(
            'paramname' => 'SAMLRequest',
            'destinationid' => $idp,
            'ProtocolBinding' => $remoteMetaData['SingleSignOnService']['Binding'],
        ),
        '_xmlns:saml'   => 'urn:oasis:names:tc:SAML:2.0:assertion',
        '_xmlns:samlp'  => 'urn:oasis:names:tc:SAML:2.0:protocol',
        '_ID'           => ID(),
        '_Version'      => '2.0',
        '_IssueInstant' => timeStamp(),
        '_Destination'  => $remoteMetaData['SingleSignOnService']['Location'],
        '_ForceAuthn'   => ($origRequest['_ForceAuthn'] == 'true') ? 'true' : 'false',
        '_IsPassive'    => ($origRequest['_IsPassive'] == 'true') ? 'true' : 'false',
        '_AssertionConsumerServiceURL'      => $metaData['AssertionConsumerService']['Location'],
        '_ProtocolBinding'                  => $metaData['AssertionConsumerService']['Binding'],
        '_AttributeConsumingServiceIndex'   => $origRequest['_AttributeConsumingServiceIndex'],
        'saml:Issuer' => array('__v' => $GLOBALS['meta']['EntityID']),
        'ds:Signature' => '__placeholder__',
        'samlp:NameIDPolicy' => array (
            '_Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            '_AllowCreate' => 'true',
        ),
    );

    if ($scoping) {
        $scoping = (array)$scoping;
        foreach ($scoping as $scopedIdP) {
            $request['samlp:Scoping']['samlp:IDPList']['samlp:IDPEntry'][] = array('_ProviderID' => $scoping);
        }
        return $request;
    }
    $request['samlp:Scoping'] = $origRequest['samlp:Scoping'];
    $request['samlp:Scoping']['_ProxyCount'] = 3;
    if ($proxyCount = $origRequest['samlp:Scoping']['_ProxyCount']) {
        $request['samlp:Scoping']['_ProxyCount'] = $proxyCount - 1;
    }
    $request['samlp:Scoping']['samlp:RequesterID'][] = array('__v' => $origRequest['saml:Issuer']['__v']);
    return $request;
}

function createResponse($request, $status = null, $attributes = null, $sourceResponse = null)
{
    $now        = timeStamp();
    $soon       = timeStamp(300);
    $sessionEnd = timeStamp(60*60*12);
    $response = array (
        '__t' => 'samlp:Response',
        '__' => array(
            'paramname' => 'SAMLResponse',
            'RelayState' => $request['__']['RelayState'],
            'target' => $request['__']['target'],
        ),
        '_xmlns:samlp' => 'urn:oasis:names:tc:SAML:2.0:protocol',
        '_xmlns:saml' => 'urn:oasis:names:tc:SAML:2.0:assertion',
        '_ID' => ID(),
        '_Version' => '2.0',
        '_IssueInstant' => $now,
        '_InResponseTo' => $request['_ID'],
        'saml:Issuer' => array('__v' => $GLOBALS['meta']['EntityID']),
        'samlp:Status' => array (
            'samlp:StatusCode' => array (
            '_Value' => 'urn:oasis:names:tc:SAML:2.0:status:Success',
            ),
        ),
    );
    
    $destinationID = $request['saml:Issuer']['__v'];
    $response['__']['destinationid'] = $destinationID;

    if ($acsUrl = $request['_AssertionConsumerServiceURL']) {
        $response['_Destination']          = $acsUrl;
        $response['__']['ProtocolBinding'] = $request['_ProtocolBinding'];
    } else {
        $remoteAcs = $GLOBALS['metabase']['remote'][$destinationID]['AssertionConsumerService'];
        $acsIndex = $request['_AssertionConsumerServiceIndex']; # can be 0
        if ($acsIndex == null) {
            $acsIndex = $remoteAcs['default'];
        }
        #$response['_Destination']             = $remoteAcs[$acsIndex]['Location'];
        #$response['__']['ProtocolBinding']    = $remoteAcs[$acsIndex]['Binding'];
        $response['_Destination']             = $remoteAcs['Location'];
        $response['__']['ProtocolBinding']    = $remoteAcs['Binding'];
    }

    if (!$response['_Destination']) die("No Destination in request or metadata for: $destinationID");

    if ($status) {
        $errorCodePrefix = 'urn:oasis:names:tc:SAML:2.0:status:';
        $response['samlp:Status'] = array(
            'samlp:StatusCode' => array (
            '_Value' => 'urn:oasis:names:tc:SAML:2.0:status:Responder',
                'samlp:StatusCode' => array (
                    '_Value' => $errorCodePrefix.$status,
                ),
            ),
        );
        return $response;
     }
    
    if ($sourceResponse) {
        $response['samlp:Status']   = $sourceResponse['samlp:Status'];
        $response['saml:Assertion'] = $sourceResponse['saml:Assertion'];

        // remove us from the list otherwise we will as a proxy be there multiple times
        // as the assertion passes through multiple times ???
        $authenticatingAuthorities = &$response['saml:Assertion']['saml:AuthnStatement']['saml:AuthnContext']['saml:AuthenticatingAuthority'];
        foreach((array)$authenticatingAuthorities as $key => $authenticatingAuthority) {
            if ($authenticatingAuthority['__v'] == $GLOBALS['meta']['EntityID']) {
                unset($authenticatingAuthorities[$key]);
            }
        }
        if ($GLOBALS['meta']['EntityID'] != $sourceResponse['saml:Issuer']['__v']) {
            $authenticatingAuthorities[] = array('__v' => $sourceResponse['saml:Issuer']['__v']);
        }

        $subjectConfirmation = &$response['saml:Assertion']['saml:Subject']['saml:SubjectConfirmation']['saml:SubjectConfirmationData'];
        $subjectConfirmation['_Recipient']       = $request['_AssertionConsumerServiceURL'];
        $subjectConfirmation['_InResponseTo']    = $request['_ID'];

        $response['saml:Assertion']['saml:Conditions']['saml:AudienceRestriction']['saml:Audience']['__v'] = $request['saml:Issuer']['__v'];

        return $response;
    }
  
    $response['saml:Assertion'] = array (
        '_xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        '_xmlns:xs' => 'http://www.w3.org/2001/XMLSchema',
        '_xmlns:samlp' => 'urn:oasis:names:tc:SAML:2.0:protocol',
        '_xmlns:saml' => 'urn:oasis:names:tc:SAML:2.0:assertion',
        '_ID' => ID(),
        '_Version' => '2.0',
        '_IssueInstant' => $now,
        'saml:Issuer' => array('__v' => $GLOBALS['meta']['EntityID']),
        'ds:Signature' => '__placeholder__',
        'saml:Subject' => array (
            'saml:NameID' =>  array (
                '_SPNameQualifier' => $GLOBALS['meta']['EntityID'],
                '_Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
                '__v' => ID(),
            ),
            'saml:SubjectConfirmation' => array (
                '_Method' => 'urn:oasis:names:tc:SAML:2.0:cm:bearer',
                'saml:SubjectConfirmationData' => array (
                    '_NotOnOrAfter' => $soon,
                    '_Recipient'    => $request['_AssertionConsumerServiceURL'], # req issuer
                    '_InResponseTo' => $request['_ID'],
                ),
            ),
        ),
        'saml:Conditions' => array (
            '_NotBefore' => $now,
            '_NotOnOrAfter' => $soon,
            'saml:AudienceRestriction' => array (
                'saml:Audience' => array('__v' => $request['saml:Issuer']['__v']),
            ),
        ),
        'saml:AuthnStatement' => array (
            '_AuthnInstant' => $now,
            '_SessionNotOnOrAfter' => $sessionEnd,
#            '_SessionIndex' => ID(),
            'saml:SubjectLocality' => array(
                '_Address' => $_SERVER['REMOTE_ADDR'],
                '_DNSName' => $_SERVER['REMOTE_HOST'],
            ),
            'saml:AuthnContext' => array (
                'saml:AuthnContextClassRef' => array('__v' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:Password'),
            ),
        ),
    );

    $attributes['binding'][] = $response['__']['ProtocolBinding'];
    foreach((array)$attributes as $key => $vs) {
        foreach($vs as $v) {
            $attributeStatement[$key][] = $v;
        }
    }
    
    $attributeConsumingServiceIndex = $request['_AttributeConsumingServiceIndex'];
    if ($attributeConsumingServiceIndex) {
        $attributeStatement['AttributeConsumingServiceIndex'] = "AttributeConsumingServiceIndex: $attributeConsumingServiceIndex";
    }
    else {
        $attributeStatement['AttributeConsumingServiceIndex'] = '-no AttributeConsumingServiceIndex given-';
    }
    
    $response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute'] = Corto_XmlToHash::hash2attributes($attributeStatement);
    $extraAttributes = Array(
        '_Name' => 'xuid',
        '_NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
        'saml:AttributeValue' => Array(
            Array(
                '_xsi:type' => 'xs:string',
                '__v' => 'abc@xxx',
            ),
            Array(
                '_xsi:type' => 'xs:string',
                '__v' => 'def@yyy',
            ),
        ),
    );
    $extraEncryptedAttributes = encryptElement($GLOBALS['certificates'][$GLOBALS['meta']['EntityID']]['public'], $extraAttributes, 'saml:EncryptedAttribute');
    $response['saml:Assertion']['saml:AttributeStatement']['saml:EncryptedAttribute'][] = $extraEncryptedAttributes;

    #$e = $response['saml:Assertion'];
    #$e['__t'] = 'saml:EncryptedAssertion';
    #$response['saml:EncryptedAssertion'] = docrypt(certs::$server_crt, $response['saml:Assertion'], 'saml:EncryptedAssertion');
    
    return $response;
}

function sign($privateKey, $element)
{
    $signature = Array(
        '__t' => 'ds:Signature',
        '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
        'ds:SignedInfo' => Array(
            '__t' => 'ds:SignedInfo',
            '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'ds:CanonicalizationMethod' => Array(
                '_Algorithm' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
            ),
            'ds:SignatureMethod' => Array(
                '_Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
            ),
            'ds:Reference' => Array(
                '_URI' => '__placeholder__',
                'ds:Transforms' => Array(
                    'ds:Transform' => Array(
                        '_Algorithm' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
                    ),
                ),
                'ds:DigestMethod' => Array(
                    '_Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                ),
                'ds:DigestValue' => Array(
                    '__v' => '__placeholder__',
                ),
            ),
        ),
    );

    $key = openssl_pkey_get_private($privateKey);
    $canonicalXml = DOMDocument::loadXML(Corto_XmlToHash::hash2xml($element))->firstChild->C14N(true, false);

    $signature['ds:SignedInfo']['ds:Reference']['ds:DigestValue']['__v'] = base64_encode(sha1($canonicalXml, TRUE));
    $signature['ds:SignedInfo']['ds:Reference']['_URI'] = "#" . $element['_ID'];

    $canonicalXml2 = DOMDocument::loadXML(Corto_XmlToHash::hash2xml($signature['ds:SignedInfo']))->firstChild->C14N(true, false);

    openssl_sign($canonicalXml2, $signatureValue, $key);

    openssl_free_key($key);
    $signature['ds:SignatureValue']['__v'] = base64_encode($signatureValue);
    foreach($element as $tag => $item) {
        if ($tag == 'ds:Signature') {
            continue;
        }

        $newElement[$tag] = $item;

        if ($tag == 'saml:Issuer') {
            $newElement['ds:Signature'] = $signature;
        }
    }
    
    return $newElement;
}

function verify($publicKey, $xml, $element)
{
    $signatureValue = base64_decode($element['ds:Signature']['ds:SignatureValue']['__v']);
    $digestValue = base64_decode($element['ds:Signature']['ds:SignedInfo']['ds:Reference']['ds:DigestValue']['__v']);
    $id = $element['_ID'];

    $document = DOMDocument::loadXML($xml);
    $xp = new DomXPath($document); 
    $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    
    $signedElement = $xp->query("//*[@ID = '$id']")->item(0);
    $signature = $xp->query(".//ds:Signature", $signedElement)->item(0);
    $signedInfo = $xp->query(".//ds:SignedInfo", $signature)->item(0)->C14N(true, false);
    $signature->parentNode->removeChild($signature);
    $canonicalXml = $signedElement->C14N(true, false);
    
    return sha1($canonicalXml, TRUE) == $digestValue && openssl_verify($signedInfo, $signatureValue, $publicKey) == 1;
}

function encryptElement($publicKey, $element, $tag = null)
{
    if ($tag) $element['__t'] = $tag;
    $data = Corto_XmlToHash::hash2xml($element);
    $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($cipher), MCRYPT_DEV_URANDOM);
    $sessionkey = mcrypt_create_iv(mcrypt_enc_get_key_size($cipher), MCRYPT_DEV_URANDOM);
    mcrypt_generic_init($cipher, $sessionkey, $iv);
    $encryptedData = $iv . mcrypt_generic($cipher,$data);
    mcrypt_generic_deinit($cipher);
    mcrypt_module_close($cipher);

    $publicKey = openssl_pkey_get_public($publicKey);
    openssl_public_encrypt($sessionkey, $encryptedKey, $publicKey, OPENSSL_PKCS1_PADDING);
    openssl_free_key($publicKey);

    $encryptedElement = array(
        'xenc:EncryptedData' => array(
            '_xmlns:xenc' => 'http://www.w3.org/2001/04/xmlenc#',
            '_Type' =>  'http://www.w3.org/2001/04/xmlenc#Element',
            'ds:KeyInfo' => array(
                '_xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#",
                'xenc:EncryptedKey' => array(
                    '_Id' => ID(),
                    'xenc:EncryptionMethod' => array(
                        '_Algorithm' => "http://www.w3.org/2001/04/xmlenc#rsa-1_5"
                    ),
                    'xenc:CipherData' => array(
                        'xenc:CipherValue' => array(
                            '__v' => base64_encode($encryptedKey),
                        ),
                    ),
                ),
            ),
            'xenc:EncryptionMethod' => array (
                '_Algorithm' =>  'http://www.w3.org/2001/04/xmlenc#aes128-cbc',
            ),
            'xenc:CipherData' => array(
                'xenc:CipherValue' => array(
                    '__v' => base64_encode($encryptedData),
                ),
            ),
        ),
    );
    return $encryptedElement;
}

function decryptElement($privateKey, $element, $asXML = false)
{
    $encryptedKey = base64_decode($element['xenc:EncryptedData']['ds:KeyInfo']['xenc:EncryptedKey']['xenc:CipherData']['xenc:CipherValue']['__v']);
    $encryptedData = base64_decode($element['xenc:EncryptedData']['xenc:CipherData']['xenc:CipherValue']['__v']);

    $privateKey = openssl_pkey_get_private($privateKey);
    openssl_private_decrypt($encryptedKey, $sessionKey, $privateKey, OPENSSL_PKCS1_PADDING);
    openssl_free_key($privateKey);

    $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
    $ivSize = mcrypt_enc_get_iv_size($cipher);
    $iv = substr($encryptedData, 0, $ivSize);

    mcrypt_generic_init($cipher, $sessionKey, $iv);

    $decryptedData = mdecrypt_generic($cipher, substr($encryptedData, $ivSize));
    mcrypt_generic_deinit($cipher);
    mcrypt_module_close($cipher);    
    return $asXML ? $decryptedData : Corto_XmlToHash::xml2hash($decryptedData);
}

function prepareForSLO($response, $sentorreceived)
{
    return;
    # add the session id to a file 'indexed' by a hash of the nameId
    # add the sending/receiving entities to a to a session indexed by the hash
    # remember to keep the full nameID.
    $nameId = $response['saml:Assertion']['saml:Subject']['saml:NameID'];
    ksort($nameId); # 'cannolization' !!!
    $nidId = sha1(join("", array_values($nameId)));
    file_put_contents("/tmp/".$GLOBALS['meta']['entitycode'].'-'.$nidId, session_id());
    $_SESSION['slo'][$sentorreceived][$nidId]['nameID'] = $nameId;
    $entityid = $sentorreceived == 'received' ? $response['saml:Issuer']['__v'] : $response['__']['destinationid'];
    $_SESSION['slo'][$sentorreceived][$nidId]['entities'][$entityid]++;
};

function singleLogoutService()
{
    if ($req = $_REQUEST['hSAMLRequest']) {
        ksort($req['saml:NameID']);
        $nidIdfile  = "/tmp/".$GLOBALS['meta']['entitycode'].'-'.sha1(join("", array_values($req['saml:NameID'])));
        if (!file_exists($nidIdfile)) {
            
        } else {
            $sessionid = file_get_contents($nidIdfile);
            newSession($sessionid, $GLOBALS['meta']['entitycode']);
        }

    } elseif ($res = $_REQUEST['hSAMLResponse']) {
        # this is for frontend ...
        # if there is a 2nd level status save it - we need to pass it on ...

    } else die("What! No Kissing?");

    foreach(array('received', 'sent') as $sentOrReceived) {
        foreach($_SESSION['slo'][$sentOrReceived] as $nidId => $entities) {
            foreach($entities['entities'] as $entity => $dummy) {
                unset($_SESSION['slo']['received'][$nidId]['entities'][$entity]);
                # send logoutrequest to $entityid using $nameId ...
            }
            
        }
    }
    # send logoutresponse
}

function checkDestinationAudienceAndTiming()
{
    $message = '';
    if (isset($_REQUEST['hSAMLRequest'])) {
        $message = $_REQUEST['hSAMLRequest'];
    }
    else if (isset($_REQUEST['hSAMLResponse'])) {
        $message = $_REQUEST['hSAMLResponse'];
    }
    if (!$message) {
        return true;
    }

    // just use string cmp all times in ISO like format without timezone (but everybody appends a Z anyways ...)
    $skew = CORTO_MAX_AGE_SECONDS;
    $aShortWhileAgo = timeStamp(-$skew);
    $inAShortWhile  = timeStamp($skew);
    $issues = array();

    if (isset($message['saml:Assertion']['saml:Subject']['saml:SubjectConfirmation']['saml:SubjectConfirmationData']['_NotBefore'])) {
        if ($inAShortWhile < $message['saml:Assertion']['saml:Subject']['saml:SubjectConfirmation']['saml:SubjectConfirmationData']['_NotBefore']) {
            $issues[] = "SubjectConfirmation not valid yet";
        }
    }

    if (isset($message['saml:Assertion']['saml:Subject']['saml:SubjectConfirmation']['saml:SubjectConfirmationData']['_NotOnOrAfter'])) {
        if ($aShortWhileAgo > $message['saml:Assertion']['saml:Subject']['saml:SubjectConfirmation']['saml:SubjectConfirmationData']['_NotOnOrAfter']) {
            $issues[] = "SubjectConfirmation too old";
        }
    }

    if (isset($message['saml:Assertion']['saml:Conditions']['_NotBefore'])) {
        if ($inAShortWhile < $message['saml:Assertion']['saml:Conditions']['_NotBefore']) {
            $issues[] = "Assertion Conditions not valid yet";
        }
    }

    if (isset($message['saml:Assertion']['saml:Conditions']['_NotOnOrAfter'])) {
        if ($aShortWhileAgo > $message['saml:Assertion']['saml:Conditions']['_NotOnOrAfter']) {
            $issues[] = "Assertions Condition too old";
        }
    }

    if (isset($message['saml:Assertion']['saml:AuthnStatement']['_SessionNotOnOrAfter'])) {
        if ($aShortWhileAgo > $message['saml:Assertion']['saml:AuthnStatement']['_SessionNotOnOrAfter']) {
            $issues[] = "AuthnStatement Session too old";
        }
    }

    $destination = $message['_Destination'];
    if ($destination) {
        if (strpos($GLOBALS['meta']['EntityID'], $destination) != 0) {
            $issues[] = "Destination: '$destination' is not here";
        }
    }
#    if ($audience = $message['saml:Assertion']['saml:Conditions']['saml:AudienceRestriction']['saml:Audience']['__v'])
#            if ($audience !== $GLOBALS['meta']['EntityID']) $issues[] = "Assertion Conditions Audience: '$audience' is not here";

    if (!empty($issues)) {
        die(print_r($issues,1));
    }
    return true;
}

/**
 * When we get an assertion from the IdP we run it through the registered filters.
 *
 * @param  $response
 * @return void
 */
function inFilter(&$response)
{
    $hostedMetaData = $GLOBALS['meta'];
    $remoteMetaData = $GLOBALS['metabase']['remote'][$response['saml:Issuer']['__v']];

    if (isset($remoteMetaData['filter'])) {
        callAttributeFilter($remoteMetaData, $remoteMetaData['filter']  , $response);
    }
    if (isset($hostedMetaData['infilter'])) {
        callAttributeFilter($hostedMetaData, $hostedMetaData['infilter'], $response);
    }
}

/**
 * Before we send the assertion to the SP we run it through the registered 'outfilters' again.
 *
 * @param  $response
 * @return void
 */
function outFilter(&$response)
{
    $hostedMetaData = $GLOBALS['meta'];
    $remoteMetaData = $GLOBALS['metabase']['remote'][$response['__']['destinationid']];

    if (isset($remoteMetaData['filter'])) {
        callAttributeFilter($remoteMetaData, $remoteMetaData['filter']   , $response);
    }
    if ($hostedMetaData['outfilter']) {
        callAttributeFilter($hostedMetaData, $hostedMetaData['outfilter'], $response);
    }
}

function callAttributeFilter($metaData, $callback, &$response)
{
    if (!$callback) {
        return;
    }

    // Take the attributes out
    $attributes = Corto_XmlToHash::attributes2hash($response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute']);

    // Pass em along
    if (is_callable($callback)) {
        $callback($metaData, $response, $attributes);
    }
    else {
        die("userfunc::$callback isn't callable");
    }

    // Put em back
    $response['saml:Assertion']['saml:AttributeStatement']['saml:Attribute'] = Corto_XmlToHash::hash2attributes($attributes);
}

function redirect($location, $message = null)
{
    $x = debugRequest($location, $message);
    if (!CORTO_TRACE) {
        header('Location: ' . $location);
    }
    print "<a href=\"{$location}\">GO</a><br><pre>{$x}</pre>";
    exit;
}

function cortoRequire($file)
{
    if (defined('CORTO_APPLICATION_OVERRIDES_DIRECTORY') && file_exists(CORTO_APPLICATION_OVERRIDES_DIRECTORY . $file)) {
        require CORTO_APPLICATION_OVERRIDES_DIRECTORY . $file;
    }
    else {
        require CORTO_APPLICATION_DIRECTORY . $file;
    }
}

/**
 * Generate a SAML datetime with a given delta in seconds.
 *
 * Delta 0 gives current date and time, delta 3600 is +1 hour, delta -3600 is -1 hour.
 *
 * @param int $deltaSeconds
 * @return string
 */
function timeStamp($deltaSeconds = 0)
{
    return gmdate('Y-m-d\TH:i:s\Z', time() + $deltaSeconds);
}

function ID()
{
    return sha1(uniqid(mt_rand(), true));
}

function render($templateName, $vars = array(), $parentTemplates = array())
{
    if (is_array($vars)) {
        extract($vars);
    }
    else {
        $content = $vars;
    }

    ob_start();

    $defaultTemplatePath = CORTO_APPLICATION_DIRECTORY.'templates/'.$templateName.'.tpl.php';
    $overrideTemplatePath = "";
    if (defined('CORTO_APPLICATION_OVERRIDES_DIRECTORY')) {
        $overrideTemplatePath = CORTO_APPLICATION_OVERRIDES_DIRECTORY . 'templates/' . $templateName . '.tpl.php';
    }

    if ($overrideTemplatePath && file_exists($overrideTemplatePath)) {
        include($overrideTemplatePath);
    }
    else if (file_exists($defaultTemplatePath)){
        include($defaultTemplatePath);
    }

    $content = ob_get_contents();
    ob_end_clean();

    foreach ($parentTemplates as $parentTemplate) {
        $content = render(
            $parentTemplate,
            array(
                'content' => $content,
        ));
    }
    return $content;
} 

/**
 * Close the current session and start a new one with a given ID and name.
 *
 * @param  $id   string ID for the session
 * @param  $name string Name for the session
 */
function newSession($id, $name)
{
    session_write_close();

    session_id($id);
    session_name($name);
    session_start();
}

function selfUrl($entityID = null)
{
    return  'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . selfPath($entityID);
}

function selfPath($entityID = null)
{
    if (!$entityID && isset($GLOBALS['meta']['entitycode'])) {
        $entityID = $GLOBALS['meta']['entitycode'];
    }

    return $_SERVER['SCRIPT_NAME'] . '/' . ($entityID ? $entityID."/" : "");
}

function debug($name, $text, $force = false)
{
    if (CORTO_DEBUG || $force) {
        file_put_contents(CORTO_DEBUG_LOG, "$name:\n". print_r($text, 1) . "\n+++\n", FILE_APPEND);
    }
}

function ddebug($name, $message, $force = false)
{
    if (CORTO_DEBUG || $force) {
        print "<pre>$name:\n". print_r($message, 1) . "\n+++\n</pre>";
    }
}

function debugRequest($url, $message)
{
    if (!CORTO_TRACE) return;

    $displayMessage = print_r($message, 1);
    
    $displayRequest = parse_url($url);
    foreach(explode("&", $displayRequest['query']) as $property) {
        if (preg_match("/^(.+)=(.*)$/", $property, $keyAndValue))  {
            $rawRequest[$keyAndValue[1]] = urldecode($keyAndValue[2]);
        }
    }
    $displayRequest['query'] = $rawRequest;
    
    $xmlMessage = htmlspecialchars(Corto_XmlToHash::hash2xml($message));
    return print_r($displayRequest, 1) .
            $xmlMessage .
            $displayMessage .
            print_r($_SESSION, 1);
}
