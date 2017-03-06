<?php
namespace lib\exception;

class PlugintoException extends \Exception
{  
    const INTERNAL_SERVER_ERROR_CODE = 1;
    const INTERNAL_SERVER_ERROR_MSG = 'Internal server error';
    const INVALID_REQUEST_METHOD_ERROR_CODE = 2;
    const INVALID_REQUEST_METHOD_ERROR_MSG = 'Invalid request method';
    const INVALID_DATA_FORMAT_ERROR_CODE = 3;
    const INVALID_DATA_FORMAT_ERROR_MSG = 'Invalid request data format, JSON data expected';
    const INVALID_DATA_ERROR_CODE = 4;
    const INVALID_DATA_ERROR_MSG = 'Invalid request data';
    const USER_NOT_AUTHENTICATED_ERROR_CODE = 5;
    const USER_NOT_AUTHENTICATED_ERROR_MSG = 'User is not authenticated. Authentication url: ';
    const USER_AUTHENTICATION_FAILED_ERROR_CODE = 6;
    const USER_AUTHENTICATION_FAILED_ERROR_MSG = 'User authentication to accounting vendor failed';
    const DATA_SERVICE_RENDER_FAILED_ERROR_CODE = 7;
    const DATA_SERVICE_RENDER_FAILED_ERROR_MSG = 'Data sevice render failed';
    const ACCOUNTING_VENDOR_FAILED_ERROR_CODE = 8;
    const ACCOUNTING_VENDOR_FAILED_ERROR_MSG = 'Accounting vendor failed';
    const ACCOUNT_VENDOR_NOT_SET_ERROR_CODE = 9;
    const ACCOUNT_VENDOR_NOT_SET_ERROR_MSG = 'Accounting vendor is not set for the user';
    const INVALID_ACCOUNT_VENDOR_SET_ERROR_CODE = 10;
    const INVALID_ACCOUNT_VENDOR_SET_ERROR_MSG = 'Invalid accounting vendor is not set for the user';
    const BASE64_ENCODED_URL_MISSING_ERROR_CODE = 11;
    const BASE64_ENCODED_URL_MISSING_ERROR_MSG = 'Base64 encoded return url missing';
    const INVALID_USER_ID_ERROR_CODE = 12;
    const INVALID_USER_ID_ERROR_MSG = 'Invalid user id';
    const INVALID_COMPANY_ID_ERROR_CODE = 13;
    const INVALID_COMPANY_ID_ERROR_MSG = 'Invalid company id';
    const INVALID_ACCOUNT_ID_ERROR_CODE = 14;
    const INVALID_ACCOUNT_ID_ERROR_MSG = 'Invalid account id';
    const INVALID_TRANSACTION_ID_ERROR_CODE = 15;
    const INVALID_TRANSACTION_ID_ERROR_MSG = 'Invalid transaction id';
    const INVALID_INVOICE_ID_ERROR_CODE = 16;
    const INVALID_INVOICE_ID_ERROR_MSG = 'Invalid invoice id';
    const INVALID_INVOICE_LINE_ID_ERROR_CODE = 17;
    const INVALID_INVOICE_LINE_ID_ERROR_MSG = 'Invalid invoice line id';
    const MISSING_REQUIRED_FIELD_ACCOUNTING_VENDOR_ERROR_CODE = 18;
    const MISSING_REQUIRED_FIELD_ACCOUNTING_VENDOR_ERROR_MSG = 'Missing required field accounting_vendor';
    const MISSING_REQUIRED_FIELD_USER_LOGIN_ERROR_CODE = 19;
    const MISSING_REQUIRED_FIELD_USER_LOGIN_ERROR_MSG = 'Missing required field user_login';
    const MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_CODE = 20;
    const MISSING_REQUIRED_FIELD_COMPANY_ID_ERROR_MSG = 'Missing required field company_id';
    const MISSING_REQUIRED_FIELD_USER_ID_ERROR_CODE = 21;
    const MISSING_REQUIRED_FIELD_USER_ID_ERROR_MSG = 'Missing required field user_id';
    const MISSING_REQUIRED_FIELD_AMOUNT_ERROR_CODE = 22;
    const MISSING_REQUIRED_FIELD_AMOUNT_ERROR_MSG = 'Missing required field amount or currency';
    const MISSING_REQUIRED_FIELD_COMPANY_TYPE_ERROR_CODE = 23;
    const MISSING_REQUIRED_FIELD_COMPANY_TYPE_ERROR_MSG = 'Missing required field company_type';
    const MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_CODE = 24;
    const MISSING_REQUIRED_FIELD_DISPLAY_NAME_SURNAME_ERROR_MSG = 'Missing required field - display_name or name or surname must be given';
    const MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_CODE = 25;
    const MISSING_REQUIRED_FIELD_ACCOUNT_ID_ERROR_MSG = 'Missing required field account_id';
    const MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ID_ERROR_CODE = 26;
    const MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ID_ERROR_MSG = 'Missing required field account_id';
    const MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ERROR_CODE = 27;
    const MISSING_REQUIRED_FIELD_FINANCIAL_ACCOUNT_ERROR_MSG = 'Missing required field bank_account_id';
    const MISSING_REQUIRED_FIELD_PAYMENT_TYPE_ERROR_CODE = 28;
    const MISSING_REQUIRED_FIELD_PAYMENT_TYPE_ERROR_MSG = 'Missing required field payment_type';
    const INVALID_ACCOUNTING_SERVICE_TYPE_ERROR_CODE = 29;
    const INVALID_ACCOUNTING_SERVICE_TYPE_ERROR_MSG = 'Invalid accounting service type';
    const NOT_A_CUSTOMER_ERROR_CODE = 30;
    const NOT_A_CUSTOMER_ERROR_MSG = 'Provided company is not a customer';
    const NOT_A_VENDOR_ERROR_CODE = 31;
    const NOT_A_VENDOR_ERROR_MSG = 'Provided company is not a vendor';
    const NOT_A_SALES_ACCOUNT_ERROR_CODE = 32;
    const NOT_AN_INCOME_ACCOUNT_ERROR_MSG = 'Provided account is not an income account';
    const NOT_AN_EXPENSE_ACCOUNT_ERROR_CODE = 33;
    const NOT_AN_EXPENSE_ACCOUNT_ERROR_MSG = 'Provided account is not an expense account';
    const MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_CODE = 34;
    const MISSING_REQUIRED_FIELD_CUSTOMER_REF_ERROR_MSG = 'Missing accounting vendor field CustomerRef';
    const MISSING_REQUIRED_FIELD_VENDOR_REF_ERROR_CODE = 35;
    const MISSING_REQUIRED_FIELD_VENDOR_REF_ERROR_MSG = 'Missing accounting vendor field VendorRef';
    const INVOICE_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_CODE = 36;
    const INVOICE_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_MSG = 'Invoice amount cannot be less than zero';
    const SALESRECEIPT_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_CODE = 37;
    const SALESRECEIPT_AMOUNT_CANNOT_BE_NEGETIVE_ERROR_MSG = 'Sales receipt amount cannot be less than zero';
    const BILL_AMOUNT_CANNOT_BE_POSITIVE_ERROR_CODE = 38;
    const BILL_AMOUNT_CANNOT_BE_POSITIVE_ERROR_MSG = 'Bill amount cannot be greater than zero';
    const INVALID_METHOD_CALL_ERROR_CODE = 39;
    const INVALID_METHOD_CALL_ERROR_MSG = 'Invalid method call';
    const INVALID_AGENCY_ID_ERROR_CODE = 40;
    const INVALID_AGENCY_ID_ERROR_MSG = 'Invalid agency id';
    const INVALID_TAXRATE_ID_ERROR_CODE = 41;
    const INVALID_TAXRATE_ID_ERROR_MSG = 'Invalid TaxRate id';
    const INVALID_TAXCODE_ID_ERROR_CODE = 42;
    const INVALID_TAXCODE_ID_ERROR_MSG = 'Invalid TaxCode id';
    const MISSING_ACCESS_TOKEN_ERROR_CODE = 43;
    const MISSING_ACCESS_TOKEN_ERROR_MSG = 'Missing access token';
    const INVALID_ACCESS_TOKEN_ERROR_CODE = 44;
    const INVALID_ACCESS_TOKEN_ERROR_MSG = 'Invalid access token';
    const INVALID_SUPPLIER_ID_ERROR_CODE = 45;
    const INVALID_SUPPLIER_ID_ERROR_MSG = 'Invalid supplier id';
    const INVALID_ITEM_ID_ERROR_CODE = 46;
    const INVALID_ITEM_ID_ERROR_MSG = 'Invalid item id';
    const INVALID_BANK_ACCOUNT_ID_ERROR_CODE = 47;
    const INVALID_BANK_ACCOUNT_ID_ERROR_MSG = 'Invalid bank account id';
    const MISSING_REQUIRED_FIELD_USER_PASSWORD_ERROR_CODE = 48;
    const MISSING_REQUIRED_FIELD_USER_PASSWORD_ERROR_MSG = 'Missing required field user_password';
    
    
    
    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this('Unknown '. get_class($this));
        }
        parent::__construct($message, $code);
    }

}
