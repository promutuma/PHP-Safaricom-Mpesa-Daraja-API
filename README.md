# PHP-Safaricom-Mpesa-Daraja-API

Information collected from [Daraja Website](https://developer.safaricom.co.ke/)

## Introduction
### 1. What Is MPESA
MPesa is a mobile money service owned by Safaricom PLC that allows users to send and receive money, pay for goods and services, and make utility payments. It is Africa's most popular mobile money service, with over 40 million active users. MPesa has been credited with helping to reduce poverty and boost economic growth in Kenya and other African countries.
MPESA supports a variety of transaction modes, including:
1. Person-to-person (P2P) transfers
2. Business-to-person (B2P) transfers (B2C)
3. Bill and Merchant payments (C2B) There is a need for a destination account for this.
4. Lipa Na M-PESA (Scan and Pay) (C2B) Where no account is needed
5. Agent to the customer (A2C) transfers (Cash Deposit)
6. Customer to Agent (C2B) transfers (Cash Withdrawal)

These transactions can be made using a variety of methods, including:
1. Mobile App and Sim Toolkit- All Transactions
2. USSD - All Transactions
3. ATM - Cash Withdrawal Only 
4. Lipa Na M-PESA (Scan and Pay) - B2C and B2B
5. Agent - Cash Withdrawal and Deposit

### 2. What is M-PESA API or DARAJA Api
Daraja API is a RESTful API that allows developers to link applications to M-Pesa services for businesses to Recieve and Make payments. It is built on REST principles and uses HTTP verbs to access data entities. API request parameters and responses are encoded in JSON. Businesses can request and receive payments using APIs developed by Safaricom, which include 
1. Mpesa Express or SDK Push
2. Dynamic QR
3. Customer To Business Register (C2B)


And Make payments using Business To Customer (B2C). Other APIs include
1. Transaction Status
2. Account Balance
3. Reversals
4. Tax Remittance


Each of the above APIs needs authorization thus the need for the Authorization API which should be called all the time.
Daraja API is a powerful tool that can be used to develop a variety of applications. It is easy to use and provides a wide range of features.

Here are some of the major uses of Daraja API:
- Businesses can use Daraja API to develop mobile applications that allow their customers to send and receive money, check their M-Pesa balance, recharge airtime, pay bills, and make purchases. This can help businesses to increase their sales and reach a wider customer base.
- NGOs can use Daraja API to develop mobile applications that allow their beneficiaries to receive funds, access information, and report on their progress. This can help NGOs to improve their efficiency and reach a wider audience.
- Governments can use Daraja API to develop mobile applications that allow citizens to pay taxes, access government services, and report on corruption. This can help governments to improve their transparency and efficiency.
- Individuals can use Daraja API to develop mobile applications that allow them to manage their finances, track their spending, and save money. This can help individuals to improve their financial literacy and reach their financial goals.

Overall, Daraja API is a powerful tool that can be used to develop a variety of applications that can benefit businesses, NGOs, governments, and individuals.




## Business Recieving C2B Payments Using API
All of these APIs require authorization, so you will need to create an account with Mpesa and obtain an API key. Once you have an API key, you can start using the APIs to send push notifications, generate QR codes, and register transactions.

All the codes under C2B in php are shared here: [Open this File](C2B_APIs/mpesaC2BFunctions.php)  

### Authorization API
This API authenticates other APIs. To generate an access token, select your app in the simulator and your keys will auto-populate. Your token expires in 3600 seconds. You can also get the Postman collection and paste your Consumer Key and Consumer Secret from Daraja.
```
        $consumerKey = ''; //Fill with your app Consumer Key
		$consumerSecret = ''; // Fill with your app Secret

		$headers = ['Content-Type:application/json; charset=utf8'];

		$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; //Production Url: 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'

    	$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_USERPWD, $consumerKey.':'.$consumerSecret);
		$result = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$result = json_decode($result);

		$access_token = $result->access_token;
	
		curl_close($curl);
    	return $access_token;

```
 

### Customer To Business (C2B)
The Customer To Business (C2B) API allows you to register transactions on your applications where you can further direct them to further processing



### Mpesa Express or STK Push API
Lipa na M-PESA online API also known as M-PESA express (STK Push/NI push) is a Merchant/Business initiated C2B (Customer to Business) Payment.

STK push simply means sim tool kit initiated push, this has now evolved to NI push (network initiated push).

#### STK Push process
1. Merchant captures and sets API parameters, sends request.
2. API receives, validates, sends acknowledgment.
3. STK Push trigger request sent to customer's M-PESA number.
4. Customer confirms with PIN.
5. M-PESA validates PIN, debits wallet, credits merchant.
6. Results sent to API Management system, forwarded to merchant.
7. Customer receives SMS confirmation.

There is STK Push and M-Pesa Express Query where you check for the transaction status.

```
        $access_token = $this->getAuthToken();
		$url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' ;//Production URL: 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
		$BusinessShortCode = '';
		$PartyA = '';
		$PassKey = ''; //
		$password = base64_encode($BusinessShortCode.$PassKey.$timestamp);
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
    		'Authorization: Bearer ' .$access_token,
    		'Content-Type: application/json'
		]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{
    		"BusinessShortCode":"'.$BusinessShortCode.'",
    		"Password": "'.$password.'",
    		"Timestamp": "'.$timestamp.'",
    		"TransactionType": "CustomerPayBillOnline",
    		"Amount": "'.$amount.'",
    		"PartyA": "'.$phone.'",
    		"PartyB": "'.$BusinessShortCode.'",
    		"PhoneNumber": "'.$phone.'",
    		"CallBackURL": "https://example.com/callbackURL.php",
    		"AccountReference": "'.$account.'",
    		"TransactionDesc": "Your Description" 
  		}');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response     = curl_exec($ch);
		$result = json_decode($response);
		curl_close($ch);

		//  After making the STK push save the data on the database Bellow is how to capture either the error or success
		if (empty($result->CheckoutRequestID)) {
			# code... Capture the error
			$responseData=[
				'ResponseCode'=> "ERROR",
				'CheckoutRequestID'=>"RequestCode:".$result->requestId,
				'MerchantRequestID'=>"ErrorCode:".$result->errorCode,
				'Timestamp'=>$timestamp,
				'Amount'=>$amount,
				'PhoneNumber'=>$phone,
				'AccountReference'=>$account,
				'ResponseDescription'=>$result->errorMessage,
			];
			$error = 1;
		} else {
			# code... Cupture success message
			$responseData=[
				'ResponseCode'=> "0",
				'CheckoutRequestID'=>$result->CheckoutRequestID,
				'MerchantRequestID'=>$result->MerchantRequestID,
				'Timestamp'=>$timestamp,
				'Amount'=>$amount,
				'PhoneNumber'=>$phone,
				'AccountReference'=>$account,
				'ResponseDescription'=>$result->ResponseDescription,
			];
			$error = 0;			
		}
		// save the data on your database the array $responseData
		$messageToOperator = [
			'error'=>$error,
			'message'=>$responseData['ResponseDescription']
		];
		return $messageToOperator;

```


### Dynamic QR API
The API can be used to generate a Dynamic QR code that can be used by Safaricom M-PESA customers to pay for goods and services at select LIPA NA M-PESA (LNM) merchant outlets. The QR code contains the till number and amount to be paid, and the customer can scan it with their My Safaricom App or M-PESA app to authorize the payment. This is a convenient and secure way to pay for goods and services, and it is also a great way to promote LNM merchants.
Here are some benefits of using the API:
- It is a convenient and secure way to pay for goods and services.
- It is a great way to promote LNM merchants.
- It is easy to use.
- It is affordable.
If you are a merchant who accepts LIPA NA M-PESA, then you should consider using this API to generate Dynamic QR codes for your customers. It is a convenient and secure way for your customers to pay for goods and services, and it is also a great way to promote your business.





