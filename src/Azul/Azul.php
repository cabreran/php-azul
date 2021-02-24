<?php
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
            'auth1'                 => 'testcert2',
            'auth2'                 => 'testcert2',
            'method'                => 'POST',
            'content_type'          => 'application/json',
            'certificate_path'      => '',
            'key_path'              => ''
        ];
        $this->defaultBody     = [
            "Channel"               => "EC",
            "Store"                 => "39038540035",
            "PosInputMode"          => "E-Commerce",
            "CurrencyPosCode"       => "$",
            "Payments"              => "1",
            "Plan"                  => "0",
            "OriginalTrxTicketNr"   => "",
            "AcquirerRefData"       => "1",
            "RRN"                   => null,
            "OrderNumber"           => "",
            "ECommerceUrl"          => "https://app.cashflow.do/",
            "ForceNo3DS"            => "0",
            "SaveToDataVault"       => "1"
        ];
        $this->client           = new Client();
    }

    public function sales(array $data = [], $hasToken = FALSE)
    {
        if(!empty($data))
        {
            $expectedData = ($hasToken == FALSE)? ['CardNumber', 'Expiration', 'CVC', 'CustomOrderId', "Amount", "Itbis"] : ['DataVaultToken', 'CustomOrderId', "Amount", "Itbis"];

            $valid = $this->validation($data, $expectedData, 'required');

            if($valid['Valid'] == FALSE)
            {
                $body             = $this->setBody($data);
                $body["TrxType" ] = "Sale";

                return $this->request($body);
            }
            else
            {
                return json_decode(json_encode($valid, JSON_FORCE_OBJECT));
            }
        }
        else
        {
            return json_decode(json_encode(array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data), JSON_FORCE_OBJECT));
        }
    }

    public function refund(array $data = [])
    {
        if(!empty($data))
        {
            $valid = $this->validation($data, ["AzulOrderId", "OriginalDate", "Amount", "Itbis"]);

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
            return json_decode(json_encode(array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data), JSON_FORCE_OBJECT));
        }
    }

    public function hold(array $data = [])
    {
        //validation

        if(!empty($data))
        {
            $body                     = $this->setBody($data);
            $body["TrxType"]          = "Hold";

            return $this->request($body);
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);
        }
    }

    public function post(array $data = [])
    {
        //validation

        if(!empty($data))
        {
            $body                     = $this->setBody($data, TRUE);
            $body["Store"]            = $this->defaultBody['Store'];
            $body["Channel"]          = $this->defaultBody['Channel'];

            return $this->request($body, '?ProcessPost');
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);
        }
    }

    public function cancel(array $data = [])
    {

        if(!empty($data))
        {
            $body                     = $this->setBody($data, TRUE);
            $body["Store"]            = $this->defaultBody['Store'];
            $body["Channel"]          = $this->defaultBody['Channel'];

            return $this->request($body, '?ProcessVoid');
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);

        }
    }

    public function verify(array $data = [])
    {
        //validation

        if(!empty($data))
        {
            $body                     = $this->setBody($data, TRUE);
            $body["Store"]            = $this->defaultBody['Store'];
            $body["Channel"]          = $this->defaultBody['Channel'];

            return $this->request($body, '?VerifyPayment');
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);
        }
    }

    public function createToken(array $data = [])
    {
        //validation

        if(!empty($data))
        {
            $body                     = $this->setBody($data, TRUE);
            $body["TrxType"]          = 'CREATE';
            $body["Store"]            = $this->defaultBody['Store'];
            $body["Channel"]          = $this->defaultBody['Channel'];

            return $this->request($body, '?ProcessDataVault');
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);
        }
    }

    public function deleteToken(array $data = [])
    {
        //validation

        if(!empty($data))
        {
            $body                     = $this->setBody($data, TRUE);
            $body["TrxType"]          = 'DELETE';
            $body["Store"]            = $this->defaultBody['Store'];
            $body["Channel"]          = $this->defaultBody['Channel'];

            return $this->request($body, '?ProcessDataVault');
        }
        else
        {
            return array('ErrorDescription' => 'INCORRECT_DATA', 'MsgError' => $this->msgError, 'Data' => $data);
        }
    }

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
            return array('error' => $this->msgError, 'data' => $data);
        }
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getDefaultBody()
    {
        return $this->defaultBody;
    }

    /* Prinvate Method ---------------------------------------------------------------------------------------------- */

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

    private function validation(array $data, array $expectedData, $action)
    {
        $action = explode('|', $action);
        $res    =  array('Valid' => FALSE, 'ErrorDescription' => '', 'MsgError' => '');

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

        return array('Valid' => $valid, 'ErrorDescription' => 'VALIDATE_REQUIRED', 'MsgError' => 'The following data is required ['.trim($fieldReq, ',').']');
    }
}