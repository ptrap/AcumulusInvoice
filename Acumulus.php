<?php

/*
 * Class for comminucating with Aculumus, financial software made by SIEL Consultancy
 *	- Information about Aculumus can be found at
 *		  http://www.aculumus.nl
 *	- Information about their API you can find at the following page:
 *		  http://www.siel.nl/api.php
 *
 * Copyright (c) 2012 Patrick Rombouts
 * AUTHORS:
 *   Patrick Rombouts
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
* Class for comminucating with acumulus, financial software made by SIEL Consultancy
*
* @package	 	acumulus Class
* @subpackage  Libraries
* @category		External calls
* @version	 	1.0
* @author	  	Patrick Rombouts
*/
class Acumulus_Invoice 
{
	private $APIurl = 'https://www.sielsystems.nl/acumulus/api/add_invoice.php';
	private $currentInvoice = array();
	public $errorLog = array();
	private $username = '';
	private $password = '';
	private $authcode = '';
	function __construct()
	{
		//if (extension_loaded('soap') === false)
		//	trigger_error('The SOAP extension is not installed on this server. This is required by the acumulus class to function', E_USER_ERROR);
		
		$this->resetClass();
	}
	
	public function setLoginDetails($username, $password, $authcode, $errorEmail = null)
	{
		$this->username = $username;
		$this->password = $password;
		$this->authcode = $authcode;
		
		$this->currentInvoice['contract']['code'] = $this->authcode;
		$this->currentInvoice['contract']['username'] = $this->username;
		$this->currentInvoice['contract']['password'] = $this->password;
		$this->currentInvoice['contract']['email_onerror'] = $errorEmail;
	}
	
	 /* Creates the basic array for making invoices */
	public function resetClass()
	{
		$this->currentInvoice = array();
		$this->currentInvoice['contract'] = array();
		$this->currentInvoice['contract']['code'] = $this->authcode;
		$this->currentInvoice['contract']['username'] = $this->username;
		$this->currentInvoice['contract']['password'] = $this->password;
		$this->currentInvoice['contract']['customer'] = array( );
	}
	
	/* Creates some values in case of an invoice when needed into our challenge array */
	private function initalizeInvoice()
	{
		if (!isset($this->currentInvoice['contract']['customer']['invoice']))
		{
			$this->currentInvoice['contract']['customer']['invoice'] = array();
			$this->currentInvoice['contract']['customer']['invoice']['line'] = array();
			return true;
		}	 
		return false;
	}
	
	private function lastError( $errorMessage )
	{
		$this->errorLog[] = $errorMessage;
		return false; /* This shall ALWAYS return false, ALWAYS. The result of this function is forwarded to the result of some functions */
	}

	public function setCustomerDetail($node, $value)
	{
		$possibleNodes = array('type' => true, 'companyname1' => true, 'companyname2' => true, 'fullname' => true, 'salutation' => true, 'address1' => true, 'address2' => true, 'postalcode' => true,
								'city' => true, 'locationcode' => true, 'countrycode' => true, 'vatnumber' => true, 'telephone' => true, 'fax' => true, 'email' => true, 
								'overwriteifexists' => true, 'bankaccountnumber' => true, 'mark' => true);
	
		if (!isset($possibleNodes[$node]))
			return $this->lastError('Incorrect field name');
			
		$this->currentInvoice['contract']['customer'][$node] = $value;
		return true;
	}
	
	public function setInvoiceDetail($node, $value)
	{
		$this->initalizeInvoice();
		$possibleNodes = array('concept' => true, 'number' => true, 'vattype' => true, 'issuedate' => true, 'costheading' => true, 'accountnumber' => true, 'paymentstatus' => true,
								'paymentdate' => true, 'description' => true, 'template' => true);
	
		if (!isset($possibleNodes[$node]))
			return $this->lastError('Incorrect field name');
		
		if ($node == 'number' and is_numeric($value))
			return $this->lastError('setInvoiceDetail: Incorrect number: needs to be numeric');
			
		if ($node == 'vattype' and ($value < 1 or $value > 5))
			return $this->lastError('setInvoiceDetail: Incorrect vattype, supposed to be a value between 0 and 6');
			
		if ($node == 'concept' and ($value != 1 and $value != 0))
			return $this->lastError('setInvoiceDetail: Incorrect concept, supposed to 0 or 1');
			
		if ($node == 'paymentstatus' and ($value != 1 and $value != 0))
			return $this->lastError('setInvoiceDetail: Incorrect paymentstatus, supposed to 0 or 1');
			
		if ($node == 'issuedate')
			if (!$this->convertDate($value))
				return $this->lastError('setInvoiceDetail: Incorrect issuedate');
			else
				$value = $this->convertDate($value);
				
		if ($node == 'paymentdate')
		{
			if (!$this->convertDate($value))
				return $this->lastError('setInvoiceDetail: Incorrect paymentdate');
			else
				$value = $this->convertDate($value);
		}	   
		$this->currentInvoice['contract']['customer']['invoice'][$node] = $value;
		return true;
	}
   
	
	public function addInvoiceLine($productName = null, $unitPrice = null, $vatRate = null , $quantity  = null, $itemNumber = null)
	{
		$this->initalizeInvoice();
		if ($productName and $unitPrice and $vatRate and $quantity) // Those are required by the API
		{
			if (!is_numeric($unitPrice))
				return $this->lastError('addInvoiceLine: unitPrice is not numeric');
		   
		   if (!is_numeric($quantity))
				return $this->lastError('addInvoiceLine: quantity is not numeric');
			
			if ($vatRate != -1 and $vatRate != 0 and $vatRate != 6 and $vatRate != 21)
				return $this->lastError('addInvoiceLine: Incorrect vat rate');
			
			$invoiceLine = array( ); 
			$invoiceLine['itemnumber'] = $itemNumber;
			$invoiceLine['product'] = $productName;
			$invoiceLine['unitprice'] = $unitPrice;
			$invoiceLine['vatrate'] = $vatRate;
			$invoiceLine['quantity'] = $quantity;
			
			$this->currentInvoice['contract']['customer']['invoice']['line'][] = $invoiceLine;
			
			return true;   
		}
		else
			return $this->lastError('addInvoiceLine: not all the required fields are filled');
	}
	
	private function convertDate( $inputDate )
	{
		$stamp = strtotime( $inputDate ); 
		   
		if (!is_numeric($stamp)) 
		{ 
			return false; 
		} 
		$month = date( 'm', $stamp ); 
		$day   = date( 'd', $stamp ); 
		$year  = date( 'Y', $stamp ); 
		   
		if (checkdate($month, $day, $year)) 
		{ 
			return $year.'-'.$month.'-'.$day; 
		} 
		   
		return false;
	}
	
	
	public function setInvoicePaid( $date = -1 )
	{
		$this->initalizeInvoice();
		if ($date == -1)
			$date = date("Y-m-d"); // today 
		else
		{
			$date = $this->convertDate( $date );
			if (!$date)
				return false;
		}
		
		$this->currentInvoice['contract']['customer']['invoice']['paymentstatus'] = 1;
		$this->currentInvoice['contract']['customer']['invoice']['paymentdate'] = $date;
		return true;
	}
	
	function generate_xml_from_array($array, $node_name) {
		$xml = '';
	
		if (is_array($array) || is_object($array)) {
			foreach ($array as $key=>$value) {
				//if (is_numeric($key)) {
				//	$key = $node_name;
				//}
				
				if (is_array($array) and isset($array[0]))
					$xml .= $this->generate_xml_from_array($value, $node_name);
				else
					$xml .= '<' . $key . '>'  . $this->generate_xml_from_array($value, $node_name) . '</' . $key . '>';//. "\n" . "\n";
			}
		} else {
			$xml = htmlspecialchars($array, ENT_QUOTES);
		}
	
		return $xml;
	}

	function generate_valid_xml_from_array($array, $node_block='nodes', $node_name='node') {
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
	
		$xml .= '<' . $node_block . '>';// . "\n";
		$xml .= $this->generate_xml_from_array($array, $node_name);
		$xml .= '</' . $node_block . '>';// . "\n";

		return $xml;
	}

	public function sendRequest($resetClass = true, $debug = false)
	{
		$generatedXML = $this->generate_valid_xml_from_array($this->currentInvoice, 'import');

		if ($debug)
		{
			$this->currentInvoice['contract']['password'] = '*********';
			$generatedXML = $this->generate_valid_xml_from_array($this->currentInvoice, 'import');
			print_r($generatedXML);
			if ($resetClass) $this->resetClass();
			return false;
		}

		$ageneratedXML = urlencode($generatedXML);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->APIurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, "xmlstring={$ageneratedXML}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		$requestResult = curl_exec($ch);
		curl_close($ch);
		$xmlResult = simplexml_load_string($requestResult);
		if (!$xmlResult)
			return array(false, false, $requestResult, array('100' => 'Failed to parse XML response'), array(), $generatedXML, $ageneratedXML );
		else
			if ($xmlResult->status == 0)
				return array(false, false, $requestResult, $xmlResult->error, $xmlResult->warning, $generatedXML, $aeneratedXML );
			else
			{
				if ($resetClass) $this->resetClass();
				return array(true, $xmlResult->invoicenumber, $requestResult, $xmlResult->error, $xmlResult->warning, $generatedXML, $ageneratedXML  );
			}
	}
}
