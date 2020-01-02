<?php

// Klasa obiektu autoryzacyjnego

class AuthDataV1
{
	public $login;
	public $masterFid;
	public $password;
};

class PostalCodeV1
{
	public $countryCode;
	public $zipCode;
};

class SenderPlaceV1
{
	public $countryCode;
	public $zipCode;
};

$authData = new AuthDataV1();
$authData->login="*****";
$authData->masterFid="******";
$authData->password="********";


// Klient webservice

$client = new SoapClient("https://dpdservices.dpd.com.pl/DPDPackageXmlServicesService/DPDPackageXmlServices?WSDL",array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));
$clientOBJ = new SoapClient("https://dpdservices.dpd.com.pl/DPDPackageObjServicesService/DPDPackageObjServices?WSDL",array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));

// Generowanie numeru
echo "<br/>generowanie numeru<br/>";
$openUMLFV1="
<Packages>
    <Package>
        <PayerType>SENDER</PayerType>
        <Sender>
            <FID>62682</FID>
            <Company>DPD Polska Sp. z o.o.</Company>
            <Name>Jan Kowalski</Name>
            <Address>Ul. Mineralna 15</Address>
            <City>Warszawa</City>
            <CountryCode>PL</CountryCode>
            <PostalCode>02274</PostalCode>
            <Phone>022 577 55 003</Phone>
            <Email>dpd@dpd.com.pl</Email>
        </Sender>
        <Receiver>
            <Company>Oddzia³ Regionalny w Katowicach</Company>
            <Name>Jan Malinowski</Name>
            <Address>ul. Brzeziñska 59</Address>
            <City>Mys³owice</City>
            <CountryCode>PL</CountryCode>
            <PostalCode>41404</PostalCode>
            <Phone>32 202-40-11</Phone>
            <Email>ktos@pocztowy.pl</Email>
        </Receiver>
		<Reference>Atest1000</Reference>
        <Ref1>123456789	1</Ref1>
        <Ref2>abcdefgh</Ref2>
        <Ref3>ZZZZZZZZZ</Ref3>
        <Services>
        	<COD>
			<Amount>1234</Amount>
			<Currency>PLN</Currency>
		</COD>
        </Services>
        <Parcels>
            <Parcel>
                <Weight>12.20</Weight>
                <Content>telefon</Content>
                <CustomerData1>dane1</CustomerData1>
            </Parcel>
           
        </Parcels>
    </Package>
</Packages>
";
	
 // walidacja danych przesy³ek i nadawanie numerów listów przewozowych
	$params1 = new stdClass();
	$params1->pkgNumsGenerationPolicyV1 = 'IGNORE_ERRORS';
	$params1->openUMLXV1= $openUMLFV1;
	$params1->authDataV1 = $authData;
	
	$result = $client->generatePackagesNumbersXV1( $params1 );
	
	$xml = simplexml_load_string($result->return);
	
	$status = $xml->Status;
	$waybill = $xml->Packages->Package[0]->Parcels->Parcel[1]->Waybill;
	echo "<BR/>status operacji: " . $status . " wygenerowany numer listu " .  $waybill ."<BR/>";

//	przygotowanie danych do dalszego przetwarzania

	$sessionId = $xml->SessionId;
	$packageId = $xml->Packages->Package[0]->PackageId;
	$parcelId = $xml->Packages->Package[0]->Parcels->Parcel[1]->ParcelId;
	$reference = "Atest1000";
	$FID = ; // globalnie na sta³e

// Tworzenie etykiet na podstawie	package ref.
	echo "<br/>Tworzenie etykiet na podstawie	package ref.<br/>";
	$dpdServiceParam3 = 
	"<DPDServicesParamsV1>
		<Policy>STOP_ON_FIRST_ERROR</Policy>
    		<Session>
        		<SessionType>DOMESTIC</SessionType>
				<Packages>
					<Package>
						<Reference>"
						.  $reference .
						"</Reference>
					</Package>
				</Packages>
    		</Session>
	</DPDServicesParamsV1>	
		";
	$params4 = new stdClass();	
	$params4->dpdServicesParamsXV1 = $dpdServiceParam3;
	$params4->outputDocFormatV1 = "PDF";
	$params4->outputDocPageFormatV1="LBL_PRINTER";	
	$params4->authDataV1 = $authData;
	$result = $client->generateSpedLabelsXV1( $params4 );
	$xml = simplexml_load_string($result->return);
	$pdf3 = base64_decode($xml->DocumentData);
	echo "<BR/>status operacji: " . $xml->Session->StatusInfo->Status . "<BR/>"; 
file_put_contents('pdf/' .$reference. '1.pdf', $pdf3);

?>
<html>
<head></head>
<body>
<?php 
$imagick = new Imagick(); 
$imagick->setResolution(150, 150);
$imagick->readImage('AA1122334520.pdf'); 
$imagick->writeImages('AA1122334520.png', false); 
?> 
<img src="AA1122334520.png" />
</body>
</html>