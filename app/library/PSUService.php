

<?php

class PSUService
{
    private $_soapClient;
    private $_userDetails;

    public function __construct()
    {
        $this->_soapClient = new SoapClient("https://passport.psu.ac.th/authentication/authentication.asmx?wsdl", ["connection_timeout" => 3]);
    }

    public function getName()
    {
        return $this->_userDetails[1] . " " . $this->_userDetails[2];
    }

    public function getTitle()
    {
        return $this->_userDetails[12];
    }

    public function getUsername()
    {
        return $this->_userDetails[0];
    }

    public function checkLogin($username, $password)
    {
        $params = array('username' => $username, 'password' => $password);

        $obj = $this->_soapClient->Authenticate($params);

        if ($obj->AuthenticateResult)
        {
            $objUserDetails = $this->_soapClient->GetUserDetails($params);
            $this->_userDetails = $objUserDetails->GetUserDetailsResult->string;
            return true;
        }

        return false;
    }
}

