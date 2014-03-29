<?php
require_once('Acumulus.php');
$Acumulus = new Acumulus_Invoice;
$Acumulus->setLoginDetails('<username>', '<password>', '<contractcode>');
$Acumulus->setCustomerDetail('companyname1','Technical Test BV');
$Acumulus->setCustomerDetail('fullname','John Smith');
$Acumulus->setCustomerDetail('address1','Veldsingel 1');
$Acumulus->setCustomerDetail('postalcode','1111AA');
$Acumulus->setCustomerDetail('city','Amsterdam');
$Acumulus->setCustomerDetail('overwriteifexists', '1');
$Acumulus->setInvoiceDetail('concept', '0'); // Real invoice or concept
$Acumulus->setInvoiceDetail('vattype', '1');
$Acumulus->setInvoiceDetail('description', 'Order 54'); // Reference
$Acumulus->setInvoiceDetail('accountnumber', 'INBG04724387123'); // Bank account which the payment is expected to be recieved
$Acumulus->setInvoiceDetail('costheading', 'WebshopSales');

// accountnumber
$Acumulus->setInvoicePaid( -1 ); // Mark invoice paid, date of today
$Acumulus->addInvoiceLine('Jar of Milk', (3.5/121*100), 21, 1, 'TSTART');
print_r($Acumulus->sendRequest( true ));
print_r($Acumulus->errorLog);