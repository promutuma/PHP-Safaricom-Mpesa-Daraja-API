<?php
use SupportFunctions;
class mpesaC2BFunctions {    
    private function getAuthToken(){
        /* access token */
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
    }

	public function urlC2BRegister(){
		$url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl'; //Production Url: https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl';

		$access_token = $this->getAuthToken(); // check the getAuthToken() function.
		$shortCode = ''; // provide the short code obtained from your test credentials or your production one
	
		/* This two files are provided in the project. */
		$confirmationUrl = 'https://example.com/c2b/confirmation.php'; // path to your confirmation url. can be IP address that is publicly accessible or a url
		$validationUrl = 'https://example.com/c2b/validation.php'; // path to your validation url. can be IP address that is publicly accessible or a url
	
	
	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$access_token)); //setting custom header
	
	
		$curl_post_data = array(
		  //Fill in the request parameters with valid values
		  'ShortCode' => $shortCode,
		  'ResponseType' => 'Completed', // This is the URL that is only used when a Merchant (Partner) requires to validate the details of the payment before accepting. For example, a bank would want to verify if an account number exists in their platform before accepting a payment from the customer.
		  'ConfirmationURL' => $confirmationUrl, //This is the URL that receives payment notification once payment has been completed successfully on M-PESA.
		  'ValidationURL' => $validationUrl
		);
	
		$data_string = json_encode($curl_post_data);
	
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
	
		$curl_response = curl_exec($curl);
	
		echo $curl_response;
	}

	public function camptureTransaction(){ //This receives payment notification from Mpesa once payment has been completed successfully on M-PESA. Can work only when urls are working and registered
		header("Content-Type: application/json");

		$response = '{
			"ResultCode": 0, 
			"ResultDesc": "Confirmation Received Successfully"
		}';
	
		// Response from M-PESA Stream
		$mpesaResponse = file_get_contents('php://input');
	
		// log the response
		$logFile = //WRITEPATH."M_PESAConfirmationResponse.txt";
	
		$jsonMpesaResponse = json_decode($mpesaResponse, true); // We will then use this to save to database
	
		$transaction = [
				'TransactionType'      => $jsonMpesaResponse['TransactionType'],
				'TransID'              => $jsonMpesaResponse['TransID'],
				'TransTime'            => $jsonMpesaResponse['TransTime'],
				'TransAmount'          => $jsonMpesaResponse['TransAmount'],
				'BusinessShortCode'    => $jsonMpesaResponse['BusinessShortCode'],
				'BillRefNumber'        => $jsonMpesaResponse['BillRefNumber'],
				'InvoiceNumber'        => $jsonMpesaResponse['InvoiceNumber'],
				'OrgAccountBalance'    => $jsonMpesaResponse['OrgAccountBalance'],
				'ThirdPartyTransID'    => $jsonMpesaResponse['ThirdPartyTransID'],
				'MSISDN'               => $jsonMpesaResponse['MSISDN'],
				'FirstName'            => $jsonMpesaResponse['FirstName']
		];

		// save the data on the database Add your code here
		return true;
	
	}

	public function stkPush($phone,$amount,$account){ // you should have registered your urls so that the payment is captured on your database
		$SupportFuntions = new SupportFunctions();
        $getTime = $SupportFuntions->getTime();
        $timestamp= $getTime['timestamp'];
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
	}
}
?>