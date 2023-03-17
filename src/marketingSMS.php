<?php

namespace Noopstudios\MarketingSMS;

/**
 * Class MarketingSMSMessage
 *
 * @author Noop <dev@noop.pt>
 * @version 1.0.0
 */

class marketingSMS {

    /**
     * The API base URL.
     */
    const API_URL = 'http://marketingsms.noop.pt/api/v1/messages';

    /**
     * API token.
     *
     * @var string
     */
    private $_apiToken;
    private $_sandboxUrl;

    private $_obj;
    private $_response;
    private $_notice;
    private $_respondeCode;

    /**
     * API Tokens can be generated at main dashboard in API section (as admin).
     *
     * @param $apiToken
     */
    public function __construct($apiToken, $_sandboxUrl = null) {
        $this->_apiToken = $apiToken;
        $this->_sandboxUrl = $_sandboxUrl;
    }

    /**
     * Make API curl request.
     *
     * @param string $function          The API function to call.
     * @param string $method            The HTTP method to use.
     * @param array $params             The parameters to send.
     *
     * @return mixed
     */
    public function _makeRequest($function, $method, $params = []){
        if(!empty($params)){
            $paramsString = http_build_query($params);
        }

        if(empty($this->_sandboxUrl)){
            $apiUrl = self::API_URL;
        }else{
            $apiUrl = $this->_sandboxUrl;
        }

        $apiCall = $apiUrl . $function .'?'. $paramsString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiCall);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'Authorization: Bearer ' .$this->_apiToken ));

        if ('POST' === $method) {
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        }

        $jsonData = curl_exec($ch);

        if (false === $jsonData) {
            throw new \Exception("Error: _makeRequest() - cURL error: " . curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        $this->_decodeResponse($httpcode, $jsonData);

        return $jsonData;
    }

    /**
     * Decode JSON response
     *
     * @param json  $jsonData   JSON encoded string returned from the API
     *
     * @return null
     */

    private function _decodeResponse($httpcode, $jsonData) {
        $this->_obj = json_decode( $jsonData );
        $this->_respondeCode = $httpcode;

        $response = '';

        switch ($httpcode) {
            case 200:
                $this->_response = 'Success';
                $this->_notice = $this->_response;
                break;
            case 402:
                $this->_response = 'Payment Required';
                $this->_notice = $this->_response;
                break;
            case 403:
                $this->_response = 'Forbidden';

                foreach($this->_obj->errors as $error) {
                    $response.= $error.'. ';
                }

                $this->_notice = rtrim(trim($response), '.').'.';
                break;
            case 405:
                $this->_response = 'Method Not Allowed';
                $this->_notice = $this->_response;
                break;
            case 406:
                $this->_response = 'Not Acceptable';
                $this->_notice = $this->_response;
                break;
            case 409:
                $this->_response = 'Conflict';
                $this->_notice = $this->_response;
                break;
            case 422:
                $this->_response = 'Bad Request';

                foreach($this->_obj->errors as $errors) {
                    foreach($errors as $error) {
                        $response.= join($error, ', ').'. ';
                    }
                }

                $this->_notice = rtrim(trim($response), '.').'.';
                break;
            default:
                $this->_response = 'Unhandled Response';
                $this->_notice = $this->_response;
                break;
        }
    }

    /**
     * Get human readable responses from the last API call
     *
     * @return string
     */
    public function getCleanResponse() {
        return $this->_notice;
    }

    /**
     * Get raw response from the last API call
     * @return mixed
     */
    public function getResponseCode() {
        return $this->_respondeCode;
    }

    /**
     * @param $infoArray
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage($infoArray = []) {
        return $this->_makeRequest('messages', 'POST', $infoArray);
    }

}