# Bluepay Package for Laravel 4.1.x

## Step 1: 
    Add to composer.json
    "core3net/bluepay" : "dev-master"
## Step 2: 
    Add Provider and Facades
    Service Provider: 'Core3Net\Bluepay\BluepayServiceProvider'
    Facade: 'Bluepay'           => 'Core3Net\Bluepay\Facades\Bluepay'
## Step 3: Integrate your Bluepay credentials
    Bluepay::$ACCOUNT_ID = "My BluePay Account ID";
    Bluepay::$ACCOUNT_USER_ID = "My Bluepay User ID";
    Bluepay::$SECRET_KEY = "My bluepay Secret key";

## Step 4: Prepare a transaction

    $customer = [];
    $customer['first'] = "Chris";
    $customer['last'] = "Horne";
    $customer['address'] = "11555 Medlock Bridge Road";
    $customer['address2'] = "Suite 100";
    $customer['city'] = "Johns Creek";
    $customer['state'] = "GA";
    $customer['zip'] = "30097";
    $customer['country'] = "US";
    $customer['phone'] = "4049732312";
    $customer['email'] = "chorne@core3networks.com";

    // For Credit Cards
    $number = "4242424242424242";
    $cvv = "413";
    $exp = "0114";
    try 
    {
      $result = Bluepay::init()->setCustomer($customer)->isSale()->setAmount(55.00)->setCard($number, $cvv, $exp)->memo("Your $55.00 bill!")->create();
      $transactionID = $result->transId;
      // Save TransactionID for future charges.
    }
    catch (Exception $e)
    {
      print("Credit Card Transaction Failed with Message: ". $e->getMessage());
    }

    // With Checking Account
    $routing = "190492323";
    $account = "1004928372891";
    $type = "C"; 

    try
    {
      $result = Bluepay::init()->setCustomer($customer)->isSale()->setAmount(124.00)->setACH($routing, $account, $type)->memo("$124 charge to your checking account")->create();
    }                   
    catch (Exception $e)
    {
        print("Checking account transaction failed with message: ".$e->getMessage());
    }
## Step 5: Rebill with Old Transaction ID

Use same steps as before except for setCustomer($transactionID) instead of an array of customer data.

That's it! 



