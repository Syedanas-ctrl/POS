<?php
namespace App\Services\Zatca;

use App\Services\Zatca\CertificateBuilder;
use App\Services\Zatca\Exceptions\CertificateBuilderException;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use App\Services\Zatca\InvoiceSigner;
use App\Services\Zatca\Helpers\Certificate;
use App\Transaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Services\Zatca\Exceptions\ZatcaException;
use App\Services\Zatca\Helpers\InvoiceExtension;
use DB;
use DateTime;
use App\Currency;
use Ramsey\Uuid\Uuid;

class Zatca
{
    /**
     * Get the CSR for the Zatca API
     * @param array 
     * $data = [
     *   "portal_mode" => "developer",
     *   "otp" => "121212",
     *   "common_name" => "TSTCO",
     *   "country_code" => "SA",
     *   "organization_unit_name" => "TSTCO-SA",
     *   "organization_name" => "TSTCO-SA",
     *   "egs_serial_number" => "1-SDSA|2-FGDS|3-SDFG",
     *   "vat_number" => "300000000000003",
     *   "business_category" => "Transportations",
     *   "crn" => "CRN123456",
     *   "address" => "Location Address",
     *  ];
     * @return array
     */
    public function getCsr(array $data) : array
    {
        try {
            $address  =  $data['address'];
            $csr = (new CertificateBuilder())
                // The Organization Identifier must be 15 digits, starting andending with 3
                ->setOrganizationIdentifier($data['vat_number'])
                // string $solutionName .. The solution provider name
                // string $model .. The model of the unit the stamp is being generated for
                // string $serialNumber .. # If you have multiple devices each should have a unique serial number
                ->setSerialNumber('POS', 'A1', $data['egs_serial_number'])
                ->setCommonName($data['common_name'])          // The common name to be used in the certificate
                ->setCountryName($data['country_code'])                          // The Country name must be Two chars only
                ->setOrganizationName($data['organization_name'])    // The name of your organization
                ->setOrganizationalUnitName($data['organization_unit_name'])    // Organizational unit
                ->setAddress($address)            // Address
                // # Four digits, each digit acting as a bool. The order is as follows: Standard Invoice, Simplified, future use, future use 
                ->setInvoiceType(1100)
                ->setProduction($data['portal_mode'] == 'production')                          // true = Production |  false = Testing
                ->setBusinessCategory($data['business_category']);             // Your business category like food, real estate, etc
                // Generate and save the certificate and private key
                // ->generate();
                // ->generateAndSave(StorageFacade::path('certificate.csr'), StorageFacade::path('private.pem'));
            $csr->generate();
            $certificate = $csr->getCsr();
            $privateKey = $csr->getPrivateKey();

            return [
                'certificate' => $certificate,
                'privateKey' => $privateKey
            ];
        } catch (CertificateBuilderException $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }
    }

    /**
     * Get the CSID for the Zatca API
     * @param array 
     * $data = [
     *   "portal_mode" => "developer",
     *   "otp" => "121212",
     *   "csr" => "certificate.csr",
     *  ];
     * @return array
     */
    public function getCSID(array $data): array
    {
        $zatcaClient = new ZatcaAPI($data['portal_mode'] == 'production' ? 'production' : 'sandbox');

        try {
            $complianceResult = $zatcaClient->requestComplianceCertificate($data['csr'], $data['otp']);
            return [
                'certificate' => $complianceResult->getCertificate(),
                'secret' => $complianceResult->getSecret(),
                'requestId' => $complianceResult->getRequestId()
            ];

        } catch (ZatcaApiException $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }


    /**
     * Get the Production CSID for the Zatca API
     * @param array
     * $data = [
     *   "portal_mode" => "developer",
     *   "certificate" => "certificate.csr",
     *   "secret" => "secret",
     *   "requestId" => "requestId"
     * ];
     * @return array
     */
    public function getProdCSID(array $data)
    {
        $api = new ZatcaAPI($data['portal_mode'] == 'production' ? 'production' : 'sandbox');
        try {
            $result = $api->requestProductionCertificate($data['certificate'], $data['secret'], $data['requestId']);
            return [
                'certificate' => $result->getCertificate(),
                'secret' => $result->getSecret(),
                'requestId' => $result->getRequestId()
            ];
        } catch (ZatcaApiException $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function renewProdCSID(array $data)
    {
        $api = new ZatcaAPI($data['portal_mode'] == 'production' ? 'production' : 'sandbox');
        try {
            $result = $api->renewProductionCertificate($data['certificate'], $data['secret'], $data['csr'], $data['otp']);
            return [
                'certificate' => $result->getCertificate(),
                'secret' => $result->getSecret(),
                'requestId' => $result->getRequestId()
            ];
        } catch (ZatcaApiException $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }


    public function getInvoice()
    {
        $signatureInfo = (new SignatureInformation)
            ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures)
            ->setSignatureInformation($signatureInfo);

        $extensionContent = (new ExtensionContent)
            ->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtension = (new UBLExtension)
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($extensionContent);

        // Default UBL Extensions Default
        $ublExtensions = (new UBLExtensions)
            ->setUBLExtensions([$ublExtension]);

        // --- Signature Default ---
        $signature = (new Signature)
            ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades");

        // --- Invoice Type ---
        $invoiceType = (new InvoiceType())
            ->setInvoice('simplified') // 'standard' or 'simplified'
            ->setInvoiceType('invoice') // 'invoice', 'debit', or 'credit', 'prepayment'
            ->setIsThirdParty(false) // Third-party transaction
            ->setIsNominal(false) // Nominal transaction
            ->setIsExportInvoice(false) // Export invoice
            ->setIsSummary(false) // Summary invoice
            ->setIsSelfBilled(false); // Self-billed invoice

        // add Attachment
        $attachment = (new Attachment())
            ->setBase64Content(
                'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                'base64',
                'text/plain'
            );

        // --- Additional Document References ---
        $additionalDocs = [];

        // icv = Invoice counter value
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID("10"); //Invoice counter value

        // pih = Previous Invoice Hash
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('PIH')
            ->setAttachment($attachment); // Previous Invoice Hash

        // qr = QR Code Default
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('QR');

        // --- Tax Scheme & Party Tax Schemes ---
        $taxScheme = (new TaxScheme())
            ->setId("VAT");

        // --- Legal Entity Company ---
        $legalEntityCompany = (new LegalEntity())
            ->setRegistrationName('Maximum Speed Tech Supply');

        // --- Party Tax Scheme Company ---
        $partyTaxSchemeCompany = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId('399999999900003');

        // --- Address Company ---
        $addressCompany = (new Address())
            ->setStreetName('Prince Sultan')
            ->setBuildingNumber("2322")
            ->setCitySubdivisionName('Al-Murabba')
            ->setCityName('Riyadh')
            ->setPostalZone('23333')
            ->setCountry('SA');

        // --- Supplier Company ---
        $supplierCompany = (new Party())
            ->setPartyIdentification("1010010000")
            ->setPartyIdentificationId("CRN")
            ->setLegalEntity($legalEntityCompany)
            ->setPartyTaxScheme($partyTaxSchemeCompany)
            ->setPostalAddress($addressCompany);


        // --- Legal Entity Customer ---
        $legalEntityCustomer = (new LegalEntity())
            ->setRegistrationName('Fatoora Samples');

        // --- Party Tax Scheme Customer ---
        $partyTaxSchemeCustomer = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId('399999999800003');

        // --- Address Customer ---
        $addressCustomer = (new Address())
            ->setStreetName('Salah Al-Din')
            ->setBuildingNumber("1111")
            ->setCitySubdivisionName('Al-Murooj')
            ->setCityName('Riyadh')
            ->setPostalZone('12222')
            ->setCountry('SA');

        // --- Supplier Customer ---
        $supplierCustomer = (new Party())
            ->setLegalEntity($legalEntityCustomer)
            ->setPartyTaxScheme($partyTaxSchemeCustomer)
            ->setPostalAddress($addressCustomer);

        // --- Payment Means ---
        $paymentMeans = (new PaymentMeans())
            ->setPaymentMeansCode("10");


        // --- array of Tax Category Discount ---
        $taxCategoryDiscount = [];

        // --- Tax Category Discount ---
        $taxCategoryDiscount[] = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Allowance Charge (for Invoice Line) ---
        $allowanceCharges = [];
        $allowanceCharges[] = (new AllowanceCharge())
            ->setChargeIndicator(false)
            ->setAllowanceChargeReason('discount')
            ->setAmount(0.00)
            ->setTaxCategory($taxCategoryDiscount);// Tax Category Discount

        // --- Tax Category ---
        $taxCategorySubTotal = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Tax Sub Total ---
        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount(4)
            ->setTaxAmount(0.6)
            ->setTaxCategory($taxCategorySubTotal);

        // --- Tax Total ---
        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount(0.6);

        // --- Legal Monetary Total ---
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount(4)// Total amount of the invoice
            ->setTaxExclusiveAmount(4) // Total amount without tax
            ->setTaxInclusiveAmount(4.60) // Total amount with tax
            ->setPrepaidAmount(0) // Prepaid amount
            ->setPayableAmount(4.60) // Amount to be paid
            ->setAllowanceTotalAmount(0); // Total amount of allowances

        // --- Classified Tax Category ---
        $classifiedTax = (new ClassifiedTaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Item (Product) ---
        $productItem = (new Item())
            ->setName('Product') // Product name
            ->setClassifiedTaxCategory($classifiedTax); // Classified tax category

        // --- Allowance Charge (for Price) ---
        $allowanceChargesPrice = [];
        $allowanceChargesPrice[] = (new AllowanceCharge())
            ->setChargeIndicator(true)
            ->setAllowanceChargeReason('discount')
            ->setAmount(0.00);

        // --- Price ---
        $price = (new Price())
            ->setUnitCode(UnitCode::UNIT)
            ->setAllowanceCharges($allowanceChargesPrice)
            ->setPriceAmount(2);

        // --- Invoice Line Tax Total ---
        $lineTaxTotal = (new TaxTotal())
            ->setTaxAmount(0.60)
            ->setRoundingAmount(4.60);

        // --- Invoice Line(s) ---
        $invoiceLine = (new InvoiceLine())
            ->setUnitCode("PCE")
            ->setId(1)
            ->setItem($productItem)
            ->setLineExtensionAmount(4)
            ->setPrice($price)
            ->setTaxTotal($lineTaxTotal)
            ->setInvoicedQuantity(2);
        $invoiceLines = [$invoiceLine];


        // Invoice
        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID('3cf5ee18-ee25-44ea-a444-2c37ba7f28be')
            ->setId('SME00023')
            ->setIssueDate(new DateTime('2024-09-07 17:41:08'))
            ->setIssueTime(new DateTime('2024-09-07 17:41:08'))
            ->setInvoiceType($invoiceType)
            ->setNote('ABC')->setlanguageID('ar')
            ->setInvoiceCurrencyCode('SAR') // Currency code (ISO 4217)
            ->setTaxCurrencyCode('SAR') // Tax currency code (ISO 4217)
            ->setAdditionalDocumentReferences($additionalDocs) // Additional document references
            ->setAccountingSupplierParty($supplierCompany)// Supplier company
            ->setAccountingCustomerParty($supplierCustomer) // Customer company
            ->setPaymentMeans($paymentMeans)// Payment means
            ->setAllowanceCharges($allowanceCharges)// Allowance charges
            ->setTaxTotal($taxTotal)// Tax total
            ->setLegalMonetaryTotal($legalMonetaryTotal)// Legal monetary total
            ->setInvoiceLines($invoiceLines)// Invoice lines
            ->setSignature($signature);


        try {
            // Generate the XML (default currency 'SAR')
            // Save the XML to an output file
            $toXml = GeneratorInvoice::invoice($invoice);
            // (new Storage)->put(StorageFacade::path('unsigned_invoice.xml'), $toXml->getXML());
            return $toXml->getXML();
        } catch (\Exception $e) {
            // Log error message and exit
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function getInvoiceSigned(string $xmlInvoice, string $certificate, string $secret, string $privateKey)
    {
        try {
            $cleanPrivateKey = trim(str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], "", $privateKey));
            // Create a Certificate instance
            $certificate = new Certificate(
                $certificate,
                $cleanPrivateKey,
                $secret
            );
            // Sign the invoice
            $signedInvoice = InvoiceSigner::signInvoice($xmlInvoice, $certificate);

            $hash = $signedInvoice->getHash();
            $qrCode = $signedInvoice->getQRCode();
            $xml = $signedInvoice->getXML();
            return [
                    'hash' => $hash,
                    'qrCode' => $qrCode,
                    'xml' => $xml
                ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            throw new ZatcaException("Failed to sign ZATCA XML: " . $e->getMessage(), 0, $e);
        }
    }


    public function getHash(string $xml): string
    {
        $xmlDom = InvoiceExtension::fromString($xml);

        $xmlDom->removeByXpath('ext:UBLExtensions');
        $xmlDom->removeByXpath('cac:Signature');
        $xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        // Compute hash using SHA-256
        $invoiceHashBinary = hash('sha256', $xmlDom->getElement()->C14N(false, false), true);
        return base64_encode($invoiceHashBinary);
    }

    /**
     * Generate ZATCA invoice XML from a transaction
     * 
     * @param Transaction $transaction
     * @return array Array containing XML, QR code, and hash
     */
    public function generateInvoiceXml(Transaction $transaction)
    {
        // Get business details
        $business = Business::find($transaction->business_id);
        if (!$business) {
            throw new ZatcaException('Business not found');
        }
        
        // Get location details
        $location = BusinessLocation::find($transaction->location_id);
        if (!$location) {
            throw new ZatcaException('Business location not found');
        }

        // Get customer details
        $customer = Contact::find($transaction->contact_id);

        if (!$customer) {
            throw new ZatcaException('Customer not found');
        }

        // Basic UBL Extensions (required for ZATCA)
        $signatureInfo = (new SignatureInformation)
            ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures)
            ->setSignatureInformation($signatureInfo);

        $extensionContent = (new ExtensionContent)
            ->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtension = (new UBLExtension)
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($extensionContent);

        $ublExtensions = (new UBLExtensions)
            ->setUBLExtensions([$ublExtension]);

        // Signature
        $signature = (new Signature)
            ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades");

        // Determine invoice type (simplified or standard)
        $invoiceType = (new InvoiceType())
            ->setInvoice('simplified') // Default to simplified for simple invoices
            ->setInvoiceType('invoice') // Regular invoice type
            ->setIsThirdParty(false)
            ->setIsNominal(false)
            ->setIsExportInvoice(false)
            ->setIsSummary(false)
            ->setIsSelfBilled(false);

        // Set up Additional Document References
        $additionalDocs = [];

        // Add ICV (Invoice Counter Value)
        // Use transaction ID as invoice counter for now
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID($transaction->id);

        // Add PIH (Previous Invoice Hash) if this is not the first invoice
        $previousHash = DB::table('transactions')
            ->where('business_id', $transaction->business_id)
            ->where('id', '<', $transaction->id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereNotNull('zatca_hash')
            ->orderBy('id', 'desc')
            ->value('zatca_hash');

        if ($previousHash) {
            $attachment = (new Attachment())
                ->setBase64Content($previousHash, 'base64', 'text/plain');

            $additionalDocs[] = (new AdditionalDocumentReference())
                ->setId('PIH')
                ->setAttachment($attachment);
        } else {
            $attachment = (new Attachment())
                ->setBase64Content(
                    'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                    'base64',
                    'text/plain'
                );
            $additionalDocs[] = (new AdditionalDocumentReference())
                ->setId('PIH')
                ->setAttachment($attachment);
        }

        // Add QR placeholder (will be filled in after signing)
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('QR');

        // Tax Scheme
        $taxScheme = (new TaxScheme())
            ->setId("VAT");

        // Supplier Address
        $addressSupplier = (new Address())
            ->setStreetName($location->landmark . ' ' . $location->custom_field1 . ' ' . $location->custom_field2 . ' ' . $location->custom_field3 . ' ' . $location->custom_field4)
            // ->setBuildingNumber($location->custom_field1 ?? '1234') // Assuming custom_field1 stores building number
            // ->setCitySubdivisionName($location->custom_field2) // Assuming custom_field2 stores district
            ->setCityName($location->city)
            ->setPostalZone($location->zip_code)
            ->setCountry($location->country);

        // Supplier Legal Entity
        $legalEntitySupplier = (new LegalEntity())
            ->setRegistrationName($business->name);

        // Supplier Party Tax Scheme
        $partyTaxSchemeSupplier = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId($business->tax_number_1 ); // VAT registration number

        // Build complete supplier party
        $supplierParty = (new Party())
            ->setPartyIdentification($business->tax_number_2) // Commercial registration number
            ->setPartyIdentificationId("CRN")
            ->setLegalEntity($legalEntitySupplier)
            ->setPartyTaxScheme($partyTaxSchemeSupplier)
            ->setPostalAddress($addressSupplier);

        // Customer Address - For simplified invoices this might be minimal
        // $addressCustomer = (new Address())
        //     ->setStreetName($customer->address_line_1 . ' ' . $customer->address_line_2 . ' ' . $customer->custom_field1 . ' ' . $customer->custom_field2 . ' ' . $customer->custom_field3 . ' ' . $customer->custom_field4)
        //     ->setBuildingNumber($customer->custom_field1)
        //     ->setCitySubdivisionName($customer->custom_field2)
        //     ->setCityName($customer->city)
        //     ->setPostalZone($customer->zip_code)
        //     ->setCountry($customer->country);

        // Customer Legal Entity
        // $legalEntityCustomer = (new LegalEntity())
        //     ->setRegistrationName($customer->name);

        // Customer Party Tax Scheme (if customer has VAT number)
        // $partyTaxSchemeCustomer = (new PartyTaxScheme())
        //     ->setTaxScheme($taxScheme)
        //     ->setCompanyId($customer->tax_number);

        // Build complete customer party
        // $customerParty = (new Party())
        //     ->setLegalEntity($legalEntityCustomer)
        //     // ->setPartyTaxScheme($partyTaxSchemeCustomer)
        //     ->setPostalAddress($addressCustomer);

        // Payment Means
        $paymentMeans = (new PaymentMeans())
            ->setPaymentMeansCode("10"); // Cash payment by default


        $taxRate = $transaction->tax_rate > 0 ? ($transaction->tax_amount / $transaction->total_before_tax) * 100 : 0;
        // Tax Category for Discount
        $taxCategoryDiscount = [];
        $taxCategoryDiscount[] = (new TaxCategory())
            ->setPercent($taxRate) // Default 15% VAT for Saudi Arabia
            ->setTaxScheme($taxScheme);

        // Allowance Charges (discounts)
        $allowanceCharges = [];
        if ($transaction->discount_amount > 0) {
            $allowanceCharges[] = (new AllowanceCharge())
                ->setChargeIndicator(false) // False for discount
                ->setAllowanceChargeReason('discount')
                ->setAmount($transaction->discount_amount)
                ->setTaxCategory($taxCategoryDiscount);
        }
        // Tax calculation 
        $taxableAmount = $transaction->total_before_tax;
        $taxAmount = $transaction->tax_amount;

        // Tax Category
        $taxCategorySubTotal = (new TaxCategory())
            ->setPercent($taxRate)
            ->setTaxScheme($taxScheme);

        // Tax Sub Total
        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount($taxableAmount)
            ->setTaxAmount($taxAmount)
            ->setTaxCategory($taxCategorySubTotal);

        // Tax Total
        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount($taxAmount);

        // Legal Monetary Total
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount($taxableAmount) // Total amount of the invoice without tax
            ->setTaxExclusiveAmount($taxableAmount) // Total amount without tax
            ->setTaxInclusiveAmount($transaction->final_total) // Total amount with tax
            ->setPrepaidAmount(0) // Prepaid amount
            ->setPayableAmount($transaction->final_total) // Amount to be paid
            ->setAllowanceTotalAmount($transaction->discount_amount); // Total discount amount

        // Invoice Lines
        $invoiceLines = [];
        $lineNumber = 1;

        foreach ($transaction->sell_lines as $sellLine) {
            // Get product details
            $product = $sellLine->product;
            if (!$product) {
                continue;
            }

            // Classified Tax Category for product
            $classifiedTax = (new ClassifiedTaxCategory())
                ->setPercent($taxRate)
                ->setTaxScheme($taxScheme);

            // Item (Product)
            $productItem = (new Item())
                ->setName($product->name)
                ->setClassifiedTaxCategory($classifiedTax);

            $discountAmount = $sellLine->line_discount_type == 'fixed' ? $sellLine->line_discount_amount : ($sellLine->unit_price_before_discount * $sellLine->line_discount_amount / 100);
            // Allowance Charge for line discount
            $allowanceChargesPrice = [];
            if ($sellLine->line_discount_amount > 0) {
                $allowanceChargesPrice[] = (new AllowanceCharge())
                    ->setChargeIndicator(false)
                    ->setAllowanceChargeReason('discount')
                    ->setAmount($discountAmount);
            }

            // Price
            $price = (new Price())
                ->setUnitCode(UnitCode::UNIT)
                ->setAllowanceCharges($allowanceChargesPrice)
                ->setPriceAmount($sellLine->unit_price_before_discount);

            // Line Tax Total
            $lineTaxAmount = $sellLine->item_tax * $sellLine->quantity;
            $lineTaxTotal = (new TaxTotal())
                ->setTaxAmount($lineTaxAmount)
                ->setRoundingAmount($sellLine->unit_price_inc_tax * $sellLine->quantity);

            // Invoice Line
            $invoiceLine = (new InvoiceLine())
                ->setUnitCode("PCE") // Piece
                ->setId($lineNumber++)
                ->setItem($productItem)
                ->setLineExtensionAmount($sellLine->unit_price_before_discount * $sellLine->quantity)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setInvoicedQuantity($sellLine->quantity);

            $invoiceLines[] = $invoiceLine;
        }
        $currency = Currency::find($business->currency_id);
        // Build the complete invoice
        $uuid = Uuid::uuid4()->toString();
        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID($uuid)
            ->setId($transaction->invoice_no)
            ->setIssueDate(new DateTime($transaction->transaction_date))
            ->setIssueTime(new DateTime($transaction->transaction_date))
            ->setInvoiceType($invoiceType)
            ->setNote($transaction->additional_notes ?? 'Invoice')
            ->setlanguageID('ar')
            ->setInvoiceCurrencyCode($currency->code)
            ->setTaxCurrencyCode($currency->code)
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setAccountingSupplierParty($supplierParty)
            // ->setAccountingCustomerParty($customerParty)
            ->setPaymentMeans($paymentMeans)
            ->setAllowanceCharges($allowanceCharges)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines($invoiceLines)
            ->setSignature($signature);
        try {
            // Generate the XML
            $toXml = GeneratorInvoice::invoice($invoice, $currency->code);

            $xmlContent = $toXml->getXML();
            
            return [
                'xml' => $xmlContent,
                'invoice' => $invoice,
                'uuid' => $uuid
            ];
        } catch (\Exception $e) {
            throw new ZatcaException("Failed to generate ZATCA XML: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the Production CSID for the Zatca API
     * @param array
     * $data = [
     *   "portal_mode" => "developer",
     *   "certificate" => "certificate.csr",
     *   "secret" => "secret",
     *   "signedXmlInvoice" => "signedXmlInvoice",
     *   "invoiceHash" => "invoiceHash",
     *   "uuid" => "uuid"
     * ];
     * @return array
     */
    public function report(array $data)
    {
        $zatcaClient = new ZatcaAPI($data['portal_mode'] == 'production' ? 'production' : 'sandbox');
        $zatcaClient->setWarningHandling(true);
        $complianceResult = $zatcaClient->reportSimpleInvoice(
            $data['certificate'],
            $data['secret'],
            $data['signedXmlInvoice'],
            $data['invoiceHash'],
            $data['uuid']
        );
        return $complianceResult;
    }
}
