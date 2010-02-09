<?php

/**
 * SOAP XML API Data Source.
 * 
 * Requires PHP version 5, compiled with SOAP.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @category   CakePHPComponents
 * @package    SmullinDesign
 * @author     Mike Smullin <mike@smullindesign.com>
 * @copyright  Copyright 2006-2010, Smullin Design and Development, LLC (http://www.smullindesign.com) 
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @version    SVN: $Id$
 * @link       http://www.mikesmullin.com/
 */

class SoapXmlApiSource extends DataSource {
  protected $soapClient        = null,
            $soapOptions       = array(),
            $soapInputHeaders  = null,
            $soapOutputHeaders = array(),
            $host              = null;

  /**
   * Instantiate new SoapClient.
   *
   * @return Boolean
   *   TRUE on success, otherwise FALSE.
   */
  public function connect() {
    if ($this->connected) {
      return $this->connected;
    }
    try {
      $this->host = empty($this->config['location'])? $this->config['wsdl'] : $this->config['location']; // used for debugging
      register_shutdown_function(array($this, 'disconnect'));
      $this->config['trace'] = Configure::read('debug') > 0? 1 : 0; // only enable when debugging
      $this->soapClient = new SoapClient($this->config['wsdl'], $this->config);
      $this->log(t('Connection to SOAP XML API %s successful.', $this->host), LOG_DEBUG);
      $this->connected = TRUE;
      return $this->login();
    } catch (SoapFault $e) {
    }
    $this->log(t('Connection to SOAP XML API %s failed: %s', $this->host, print_r(@$e, true)), LOG_ERROR);
    return false;
  }

  /**
   * Abstract method.
   * Override to customize as necessary in descendant source.
   *
   * @return Boolean
   *   TRUE on success, otherwise FALSE.
   */
  public function login() {
    return true;
  }

  /**
   * Destroy existing SoapClient.
   *
   * @return Boolean
   *   TRUE on success, otherwise FALSE.
   */
  public function disconnect() {
    if (!$this->connected) {
      return !$this->connected;
    }
    $success                 = $this->logout();
    $this->log(t('Disconnect of SOAP XML API %s successful.', $this->host), LOG_DEBUG);
    $this->soapClient        = null;
    $this->soapOptions       = array();
    $this->soapInputHeaders  = null;
    $this->soapOutputHeaders = array();
    $this->host              = null;
    $this->connected         = false;
    return $success;
  }

  /**
   * Abstract method.
   * Override to customize as necessary in descendant source.
   *
   * @return Boolean
   *   TRUE on success, otherwise FALSE.
   */
  public function logout() {
    return true;
  }

  /**
   * Abstract method.
   * Override to customize as necessary in descendant source.
   */
  public function close() {
    return $this->disconnect();
  }

  /**
   * Call SOAP XML API method.
   *
   * @param String $method
   *   API method name.
   * @param Array $args ...
   *   Arguments to pass to method.
   * @return Mixed
   *   Result.
   */
  public function call() {
    if ($this->connect()) {
      try {
        $args = func_get_args();
        $this->beforeCall($args); // execute callback
        $method = array_shift($args);
        return $this->soapClient->__soapCall($method, $args, $this->soapOptions, $this->soapInputHeaders, $this->soapOutputHeaders);
      } catch (SoapFault $e) {
        $this->log(t('SOAP XML API call %s(%s) to %s failed: %s', $method, print_r($args, true), $this->host, print_r($e, true)), LOG_ERROR);
      }
    }
  }

  /**
   * Callback method.
   * Override to manipulate API calls before they are transmitted.
   *
   * @param Array &$args
   *   Array of arguments passed to call() by reference.
   * @return Array
   *   Modified array of arguments.
   */
  public function beforeCall(&$args) {
    return $args;
  }

  /**
   * Required for public methods of this datasource to also be accessible via
   * the models utilizing this datasource.
   */
  public function query($method, $params, &$model) {
    if (!method_exists($this, $method)) {
      array_unshift($params, $method);
      $method = 'call';
    }
    return call_user_func_array(array(&$this, $method), $params);
  }

  /**
   * Returns the SOAP headers from the last request.
   * Note: This function only works if the SoapClient object was created with
   * the trace option set to TRUE.
   *
   * @return String
   */
  public function getLastRequestHeaders() {
    return $this->soapClient->__getLastRequestHeaders();
  }

  /**
   * Returns the XML sent in the last SOAP request.
   * Note: This function only works if the SoapClient object was created with
   * the trace option set to TRUE.
   *
   * @return String
   */
  public function getLastRequest() {
    return $this->soapClient->__getLastRequest();
  }

  /**
   * Returns the SOAP headers from the last response.
   * Note: This function only works if the SoapClient object was created with
   * the trace option set to TRUE.
   *
   * @return String
   */
  public function getLastResponseHeaders() {
    return $this->soapClient->__getLastResponseHeaders();
  }

  /**
   * Returns the XML sent in the last SOAP response.
   * Note: This function only works if the SoapClient object was created with
   * the trace option set to TRUE.
   *
   * @return String
   */
  public function getLastResponse() {
    return $this->soapClient->__getLastResponse();
  }
}
