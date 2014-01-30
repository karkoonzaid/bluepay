<?php
namespace Core3Net\Bluepay;

/**
 * Set up Credentials
 */
Bluepay::$ACCOUNT_ID = "My BluePay Account ID";
Bluepay::$ACCOUNT_USER_ID = "My Bluepay User ID";
Bluepay::$SECRET_KEY = "My bluepay Secret key";

/**
 * Set up Customer Data Array
 */
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

