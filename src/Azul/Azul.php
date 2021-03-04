<?php

/**
 * AZUL web services PHP library
 *
 * Manage requests to the Azul API
 *
 * @package	    Cashflow
 * @subpackage	Cashflow
 * @category	library-API
 * @author	    Pulsar Dev Team
 * @copyright	Copyright (c) 2010 - 2021, Pulsar Technologies SRL (http://www.pulsar.com.do)
 * @link	    http://www.pulsar.com.do
 */

namespace Azul;

use GuzzleHttp\Client;

class Azul
{
    const LIVE_API_URL      = 'https://pagos.azul.com.do/webservices/JSON/Default.aspx';
    const SANDBOX_API_URL   = 'https://pruebas.azul.com.do/webservices/JSON/Default.aspx';
    protected $mode;
    protected $url;
    protected $settings;
    protected $defaultBody;
    protected $msgError;

    /* Public Method ------------------------------------------------------------------------------------------------ */

    public function __construct($mode)
    {
        $this->msgError         = 'The supplied data is incorrect';
        $this->mode             = $mode;
        $this->url              = ($mode == 'live')? self::LIVE_API_URL : self::SANDBOX_API_URL;
        $this->settings         = [
            'auth1'                 => '',
            'auth2'                 => '',
            'method'                => 'POST',
            'content_type'          => 'application/json',
            'certificate_path'      => '',
            'key_path'              => ''
        ];
        $this->defaultBody     = [
            "Channel"               => "EC",
            "Store"                 => "",
            "PosInputMode"          => "E-Commerce",
            "CurrencyPosCode"       => "$",
            "Payments"              => "1",
            "Plan"                  => "0",
            "OriginalTrxTicketNr"   => "",
            "AcquirerRefData"       => "1",
            "RRN"                   => null,
            "OrderNumber"           => "",
            "ECommerceUrl"          => "",
            "ForceNo3DS"            => "0",
            "SaveToDataVault"       => "1"
        ];
        $this->client           = new Client();
    }

    /**
     * Returns the response of the method sale the API of Azul.
     * @access	public
     * @param  array, boolean
     * @return object array
     */
    public function sale(array $data = [], $hasToken = FALSE)
    {
        if(!empty($data))
        {
            $expectedData   = ($hasToken == FALSE)? ['CardNumber', 'Expiration', 'CVC', 'CustomOrderId', "Amount"] : ['DataVaultToken', 'CustomOrderId', "Amount", "Itbis"];
            $valid          = $this->validation($data, $expectedData, 'required');

            if($valid['Valid'] == FALSE)
            {
                $body             = $this->setBody($data);
                $body["TrxType" ] = "Sale";

                if($hasToken == TRUE)
                {
                    unset($body["SaveToDataVault"]);
                }

                return $this->request($body);
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the method refund the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function refund(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ["AzulOrderId", "OriginalDate", "Amount"], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data);
                $body["TrxType"]          = "Refund";
                $body["AcquirerRefData"]  = "";
                $body["SaveToDataVault"]  = "";

                return $this->request($body);
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA','ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the method hold the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function hold(array $data = [], $hasToken = FALSE)
    {
        if(!empty($data))
        {
            $expectedData   = ($hasToken == FALSE)? ['CardNumber', 'Expiration', 'CVC', 'CustomOrderId', 'OriginalDate', "Amount"] : ['DataVaultToken', 'CustomOrderId', "Amount", "Itbis"];
            $valid          = $this->validation($data, $expectedData, 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                           = $this->setBody($data);
                $body["TrxType"]                = "Hold";

                if($hasToken == TRUE)
                {
                    unset($body["SaveToDataVault"]);
                }

                return $this->request($body);
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the method post the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function post(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ['AzulOrderId', "Amount"], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data, TRUE);
                $body["Store"]            = $this->defaultBody['Store'];
                $body["Channel"]          = $this->defaultBody['Channel'];

                return $this->request($body, '?ProcessPost');
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the method cancel the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function cancel(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ['AzulOrderId'], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data, TRUE);
                $body["Store"]            = $this->defaultBody['Store'];
                $body["Channel"]          = $this->defaultBody['Channel'];

                return $this->request($body, '?ProcessVoid');
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the verify cancel the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function verify(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ['CustomOrderId'], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data, TRUE);
                $body["Store"]            = $this->defaultBody['Store'];
                $body["Channel"]          = $this->defaultBody['Channel'];

                return $this->request($body, '?VerifyPayment');
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the verify createToken the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function createToken(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ['CardNumber', 'Expiration', 'CVC'], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data, TRUE);
                $body["TrxType"]          = 'CREATE';
                $body["Store"]            = $this->defaultBody['Store'];
                $body["Channel"]          = $this->defaultBody['Channel'];

                return $this->request($body, '?ProcessDataVault');
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Returns the response of the verify deleteToken the API of Azul.
     * @access	public
     * @param  array
     * @return object array
     */
    public function deleteToken(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ['DataVaultToken'], 'required');

            if($valid['Valid'] == FALSE)
            {
                $body                     = $this->setBody($data, TRUE);
                $body["TrxType"]          = 'DELETE';
                $body["Store"]            = $this->defaultBody['Store'];
                $body["Channel"]          = $this->defaultBody['Channel'];

                return $this->request($body, '?ProcessDataVault');
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Generates the request to the Azul API and returns the response of the same.
     * @access	public
     * @param  array, string
     * @return object array
     */
    public function request($body, $endpoint = '')
    {
        $headers    = [
            "Content-Type"          => $this->settings["content_type"],
            "Auth1"                 => $this->settings["auth1"],
            "Auth2"                 => $this->settings["auth2"]
        ];

        $body   = \GuzzleHttp\json_encode($body);
        $res    = $this->client->request($this->settings['method'], $this->url.$endpoint, ["headers" => $headers, "body" => $body, "cert" =>  $this->settings["certificate_path"], "ssl_key" => $this->settings["key_path"]]);

        return \GuzzleHttp\json_decode($res->getBody()->getContents());
    }

    /**
     * Set the configuration required by the Azul API.
     * @access	public
     * @param  array
     * @return object array
     */
    public function setSettings(array $data = [])
    {
        if(!empty($data))
        {
            foreach ($data as $key => $value)
            {
                if(isset($this->settings[$key]))
                {
                    $this->settings[$key] = ($data[$key] == "" || $data[$key] == null)? $this->settings[$key] : $data[$key];
                }
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseMessage' => $this->msgErro, 'Data' => $data)));
        }
    }

    /**
     * Set the initial or default body values of the Azul API request.
     * @access	public
     * @param  array
     * @return object array
     */
    public function setDefaultBody(array $data = [])
    {
        if(!empty($data))
        {
            foreach ($data as $key => $value)
            {
                if(isset($this->defaultBody[$key]))
                {
                    $this->defaultBody[$key] = ($data[$key] == "" || $data[$key] == null)? $this->defaultBody[$key] : $data[$key];
                }
            }
        }
        else
        {
            return json_decode(json_encode(array('ResponseCode' => 'error', 'ErrorDescription' => 'INCORRECT_DATA', 'ResponseCode' => $this->msgError, 'Data' => $data)));
        }
    }

    /**
     * Returns the configuration values.
     * @access	public
     * @param  array
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Returns the default values of the request body.
     * @access	public
     * @param  array
     * @return array
     */
    public function getDefaultBody()
    {
        return $this->defaultBody;
    }

    /* Prinvate Method ---------------------------------------------------------------------------------------------- */

    /**
     * Set the initial or default body values of the Azul API request.
     * @access	private
     * @param  array, array
     * @return array
     */
    private function setBody($data, $customData = FALSE)
    {
        $res = $this->defaultBody;

        if($customData == TRUE)
        {
            $res = $data;
        }
        else
        {
            foreach ($data as $key => $value)
            {
                if(isset($res[$key]))
                {
                    $res[$key] = ($data[$key] == "" || $data[$key] == null)? $res[$key] : $data[$key];
                }
                else
                {
                    $res[$key] = $data[$key];
                }
            }
        }

        return $res;
    }

    /**
     * Validation
     * @access	private
     * @param  array, array, string
     * @return array
     */
    private function validation(array $data, array $expectedData, $action)
    {
        $action = explode('|', $action);
        $res    =  array('Valid' => FALSE, 'ResponseCode' => 'error', 'ErrorDescription' => '', 'ResponseMessage' => '');

        foreach ($action as $key => $value)
        {
            switch ($value)
            {
                case 'required' :
                    $res = $this->required($data, $expectedData);
                    break;
            }
        }

        return $res;
    }

    /**
     * Required
     * @access	private
     * @param  array, array
     * @return array
     */
    private function required(array $data, array $expectedData)
    {
        $fieldReq  = '';
        $valid     = 0;

        foreach ($expectedData as $key => $value)
        {
            if(array_key_exists($expectedData[$key], $data) == FALSE)
            {
                $valid       += 1;
                $fieldReq   .= ",".$expectedData[$key];
            }
        }

        $valid = ($valid > 0)? TRUE : FALSE;

        return array('Valid' => $valid, 'ResponseCode' => 'error',  'ErrorDescription' => 'VALIDATE_REQUIRED', 'ResponseMessage' => 'The following data is required ['.trim($fieldReq, ',').']');
    }
}