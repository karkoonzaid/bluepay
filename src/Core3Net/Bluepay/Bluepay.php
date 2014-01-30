<?php
/**
 * Bluepay POST v2.0 SDK for Laravel 4.1.x
 *
 * @author chorne@core3networks.com
 */

namespace Core3Net\Bluepay;
use Exception;

class Bluepay
{
    static public $ACCOUNT_ID;
    static public $ACCOUNT_USER_ID;
    static public $SECRET_KEY;
    static public $mode = "TEST";
    static public $URL = 'https://secure.bluepay.com/interfaces/bp20post'; 
    
    /*
     * Transaction Type
     * Refund, Sale, Pre-Auth, or Capture
     * 
     * @var string $transType
     */
    protected $transType;
    
    /**
     * Customer Information. This is either an array
     * or a masterid for transactions
     * 
     * @var string|array $customerInfo 
     */
    protected $customerInfo;
    
    /**
     * Account Type (Credit or ACH) This allows you to
     * change which type of account you are charging
     * 
     * @var string $accountType [CREDIT/ACH] 
     */
    protected $accountType;
    /**
     * Amount to Charge/Authorize. This is in decimal format. 
     * You charge $5.35 as 5.35. Not 535 pennies.
     * 
     * @var $amount
     */
    protected $amount;
    
    /**
     * Enable Automatic Recurring Billing?
     * I personally have not tested this as I use Freshbooks
     * to generate invoices and Bluepay to process them.
     * 
     * @var boolean $rebill
     */
    protected $rebill = false;
    
    /**
     * MasterID (AKA Previous Transaction ID) is used to
     * process based on the credentials of a previous transaction.
     * 
     * @var integer $masterId
     */
    protected $masterId;
    
    /**
     * SSN (optional)
     * This is used for ACH processing on certain accounts.
     * 
     * @var string $ssn
     */
    protected $ssn;
    
    /**
     * Birthdate (optional)
     * This is used for ACH processing on certain accounts.
     *
     * @var string $birthdate
     */
    protected $birthdate;
    /**
     * The memo field is used for reporting. Generally you would
     * store the invoice number or what this transaction was for.
     * If you use Bluepay's auto-receipt feature this will be
     * put as the "reason" for the transaction
     * @var unknown
     */
    protected $memo;
    
    /**
     * Not sure what these are really for, or if they are used
     * anymore.
     * @var unknown
     */
    protected $customid1;
    protected $customid2;
    protected $orderId;
    protected $invoiceId;
   
    /**
     * Return a new instance of the biller.
     * 
     * @return \Core3Net\Bluepay\Bluepay
     */
    static public function init()
    {
        return new Bluepay;
    }
    
    /**
     * Transmits the request to bluepay to get
     * authorization
     * 
     * @param array $fields
     * @return object
     */
    
    public function transmit(array $fields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$URL); 
        curl_setopt($ch, CURLOPT_USERAGENT, "BluepayPHP SDK/2.0"); // Cosmetic
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $response = curl_exec($ch);
        parse_str($response);
        if ($STATUS == "1")
        {
        	$r = new \StdClass();
        	$r->transId = $TRANS_ID;
            $r->bpStatus = $STATUS;
            $r->avs = $AVS;
            $r->cvv = $CVV2;
            $r->auth = $AUTH_CODE;
            $r->reason = $MESSAGE;
            $r->rebid = $REBID;
            $r->amount = $fields['AMOUNT'];
            return $r;
        }
        else
            throw new Exception($MESSAGE);
    }
    
    /**
     * Set this payment method as a refund.
     * 
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function isRefund()
    {
      $this->transType = "REFUND";
      return $this;
    }
    /**
     * Set this payment method as a sale.
     *
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function isSale()
    {
    	$this->transType = "SALE";
    	return $this;
    }

    /**
     * Set this payment method as an authorization.
     * You would use this to preauth a transaction
     * before actually turning it into a sale.
     *
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function isAuth()
    {
    	$this->transType = "AUTH";
    	return $this;
    }
    
    /**
     * Set this payment method as a capture.
     * Use this if you need to save credentials and
     * get a master id without actually billing the customer
     *
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function isCapture()
    {
    	$this->transType = "CAPTURE";
    	return $this;
    }
    
    /**
     * Internal function for the TPS function
     * @return Ambigous <NULL, string, multitype:>
     */
    
    private function getName()
    {
        return (isset($this->customerInfo['first'])) ? $this->customerInfo['first'] : null;
    }
    /**
     * Set Memo property for Processing
     * 
     * @param unknown $memo
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function memo($memo)
    {
        $this->memo = $memo;
        return $this;
    }
    /**
     * Set the secret key based on your API credentials
     * @param array $payload
     * @return string
     */
    public function tps(array &$payload)
    {
        if (!isset($payload['PAYMENT_ACCOUNT'])) $payload['PAYMENT_ACCOUNT'] = null;
        $hash = self::$SECRET_KEY. self::$ACCOUNT_ID. $this->transType .
        $this->amount . $this->masterId . $this->getName() . $payload['PAYMENT_ACCOUNT'];
        return bin2hex( md5($hash, true) );
    }

    /**
     * Set the OrderID Property
     * @param unknown $orderid
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function orderId($orderid)
    {
        $this->orderId = $orderid;
        return $this;
    }
    
    /**
     * Set the recurring billing properties. 
     * 
     * -- Not tested --
     * @param string $amount
     * @param string $date
     * @param string $cycle
     * @param number $cycles
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function setRecurring($amount = null, $date = null, $cycle = '1 MONTH', $cycles = 0)
    {
        if (!$date)
            $date = date("Y-m-d H:i:s", time());
        if ($amount)
            $this->rebill['amount'] = $amount;
        else
            $this->rebill['amount'] = $this->amount;
        $this->rebill['date'] = $date;
        $this->rebill['cycle'] = $cycle;
        $this->rebill['cycles'] = $cycles;
        return $this;
    }
    /**
     * Set the customer details. If array is used as the property
     * then set all of the customer fields, otherwise set the 
     * transaction ID to use.
     * 
     * @param array|string $details
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function setCustomer($details)
    {
        if (is_array($details))
        {
            $this->customerInfo['first'] 		= $details['first'];
            $this->customerInfo['last']			= $details['last'];
            $this->customerInfo['address']		= $details['address'];
            $this->customerInfo['address2']     = (isset($details['address2'])) ? $details['address2'] : null;
            $this->customerInfo['city']			= $details['city'];
            $this->customerInfo['state']		= $details['state'];
            $this->customerInfo['zip']			= $details['zip'];
            $this->customerInfo['country']		= (isset($details['country'])) ? $details['country'] : "US";
            $this->customerInfo['phone']		= $details['phone'];
            $this->customerInfo['email']		= $details['email'];
        }
        else
        {
            $this->customerInfo = $details;
            $this->masterId = $details;
        }
        return $this;
    }

    /**
     * Set the credit card details and set transaction
     * type to Credit
     * 
     * @param integer $card
     * @param integer $cvv 
     * @param integer $exp 4-digit (ie. 0114)
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function setCard($card, $cvv, $exp)
    {
        $this->customerInfo['card']         = $card;
        $this->customerInfo['cvv']          = $cvv;
        $this->customerInfo['exp']          = $exp;
        $this->accountType                  = 'CREDIT';
        return $this;
    }
    
    /**
     * Set the Routing/Banking information for Check Processing
     * and set the type to ACH
     * 
     * @param integer $route - 9 digit routing number
     * @param unknown $account - bank account number
     * @param unknown $type - (C)hecking or (S)avings
     * @param string $id - No clue why they have this.
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function setACH($route, $account, $type, $id = null)
    {
        $this->customerInfo['route'] 		= $route;
        $this->customerInfo['account']		= $account;
        $this->customerInfo['type']			= $type;
        $this->customerInfo['id']           = $id;
        $this->accountType                  = 'ACH';
        return $this;
    }
    
    /**
     * How much to charge? in decimals. $200.00 would be entered as
     * 200.00.
     * 
     * @param decimal $amount
     * @return \Core3Net\Bluepay\Bluepay
     */
    public function setAmount($amount)
    {
        $this->amount =  $amount;
        return $this;
    }
    
    /**
     * Create the transaction to be submitted.
     * 
     * @return object|exception
     */
    public function create()
    {
        $payload = [];
        
        /*
         * Transaction Details
         */
        $payload['CUSTOMER_IP'] = $_SERVER['REMOTE_ADDR'];
        $payload['ACCOUNT_ID'] = self::$ACCOUNT_ID;
        $payload['USER_ID'] = self::$ACCOUNT_USER_ID;
        $payload['TRANS_TYPE'] = $this->transType;
        $payload['PAYMENT_TYPE'] = $this->accountType;
	    $payload['MODE'] = self::$mode;
	    $payload['MASTER_ID'] = $this->masterId;
	    $payload['AMOUNT'] = $this->amount;
	    if (!$this->masterId)
	    {
    	    /*
    	     * Customer Information
    	     */
    	    if (is_array($this->customerInfo))
    	    {
        	    $payload['NAME1'] = $this->customerInfo['first'];
                $payload['NAME2'] = $this->customerInfo['last'];
                $payload['ADDR1'] = $this->customerInfo['address'];
                $payload['ADDR2'] = $this->customerInfo['address2'];
                $payload['CITY']  = $this->customerInfo['city'];
                $payload['STATE'] = $this->customerInfo['state'];
                $payload['ZIP']   = $this->customerInfo['zip'];
                $payload['PHONE'] = $this->customerInfo['phone'];
                $payload['EMAIL'] = $this->customerInfo['email'];
                $payload['COUNTRY'] = $this->customerInfo['country'];
    	    }
            /*
             * Accessory Items
             */
    	    $payload['SSN'] = $this->ssn;
    	    $payload['BIRTHDATE'] = $this->birthdate;	    
    	    $payload['MEMO'] = $this->memo;
    	    $payload['CUSTOM_ID'] = $this->customid1;
    	    $payload['CUSTOM_ID2'] = $this->customid2;
    	    $payload['ORDER_ID'] = $this->orderId;
    	    $payload['INVOICE_ID'] = $this->invoiceId;
    	    $payload['AMOUNT_TIP'] = null;
            $payload['AMOUNT_TAX'] = null;
    	
            /*
             * Rebill Information
             * 
             */
            if ($this->rebill)
            {
                $payload['DO_REBILL'] = ($this->rebill) ? 1 : 0;
            	$payload['REB_FIRST_DATE'] = $this->rebill['start'];
            	$payload['REB_EXPR'] = $this->rebill['expires'];
            	$payload['REB_CYCLES'] = $this->rebill['cycles'];
            	$payload['REB_AMOUNT'] = $this->rebill['amount'];
            }
            else
                $payload['DO_REBILL'] = 0;
    	 
          /*
           * Determine Payment Account To Submit.
           */
            if ($this->accountType == "CREDIT")
            {
              $payload['PAYMENT_ACCOUNT'] = $this->customerInfo['card'];
              $payload['CARD_CVV2'] = $this->customerInfo['cvv'];
              $payload['CARD_EXPIRE'] = $this->customerInfo['exp'];
            }
            else
            {
                $payload['PAYMENT_ACCOUNT'] =  $this->customerInfo['type'].":".$this->customerInfo['route'].":".$this->customerInfo['account'];
                $payload['CUST_ID'] = $this->customerInfo['id'];
                $payload['CUST_ID_STATE'] = $this->customerInfo['state'];
            }
        
	    } // if no masterid.
	    
                $payload['TAMPER_PROOF_SEAL'] = $this->tps($payload);
        
	    return $this->transmit($payload);
    }

}


