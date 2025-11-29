<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Your company details that appear on quotes and invoices.
    | These are required for GoBD-compliant invoicing in Germany.
    |
    */

    'company' => [
        'name' => env('COMPANY_NAME', 'Your Company Name'),
        'legal_name' => env('COMPANY_LEGAL_NAME', 'Your Company Legal Name GmbH'),
        'address_line_1' => env('COMPANY_ADDRESS_LINE_1', ''),
        'address_line_2' => env('COMPANY_ADDRESS_LINE_2', ''),
        'postal_code' => env('COMPANY_POSTAL_CODE', ''),
        'city' => env('COMPANY_CITY', ''),
        'country' => env('COMPANY_COUNTRY', 'Germany'),
        'email' => env('COMPANY_EMAIL', 'info@example.com'),
        'phone' => env('COMPANY_PHONE', ''),
        'website' => env('COMPANY_WEBSITE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Information
    |--------------------------------------------------------------------------
    |
    | Tax ID numbers and default tax rates for invoicing.
    | German businesses need either Steuernummer or USt-IdNr.
    |
    */

    'tax' => [
        // Steuernummer (Tax ID)
        'tax_number' => env('COMPANY_TAX_NUMBER', ''),

        // USt-IdNr (VAT ID for EU businesses)
        'vat_id' => env('COMPANY_VAT_ID', ''),

        // Default tax rate (19% for Germany, 0% for reverse charge, etc.)
        'default_rate' => env('TAX_DEFAULT_RATE', 19.0),

        // Reduced tax rate (7% for certain goods/services in Germany)
        'reduced_rate' => env('TAX_REDUCED_RATE', 7.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Banking Information
    |--------------------------------------------------------------------------
    |
    | Bank details for payment instructions on invoices.
    |
    */

    'bank' => [
        'name' => env('BANK_NAME', ''),
        'account_holder' => env('BANK_ACCOUNT_HOLDER', ''),
        'iban' => env('BANK_IBAN', ''),
        'bic' => env('BANK_BIC', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice generation and numbering.
    |
    */

    'invoice' => [
        // Invoice number prefix (e.g., INV-2024-0001)
        'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),

        // Number of digits for invoice number (e.g., 4 = 0001)
        'number_padding' => env('INVOICE_NUMBER_PADDING', 4),

        // Default payment terms in days
        'default_payment_terms' => env('INVOICE_PAYMENT_TERMS', 30),

        // Default due date text
        'payment_terms_text' => env('INVOICE_PAYMENT_TERMS_TEXT', 'Zahlbar innerhalb von :days Tagen netto ohne Abzug.'),

        // Invoice footer text
        'footer_text' => env('INVOICE_FOOTER_TEXT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quote Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for quote generation and numbering.
    |
    */

    'quote' => [
        // Quote number prefix (e.g., Q-2024-0001)
        'number_prefix' => env('QUOTE_NUMBER_PREFIX', 'Q'),

        // Number of digits for quote number
        'number_padding' => env('QUOTE_NUMBER_PADDING', 4),

        // Default validity period in days
        'default_validity_days' => env('QUOTE_VALIDITY_DAYS', 30),

        // Quote footer text
        'footer_text' => env('QUOTE_FOOTER_TEXT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale & Currency
    |--------------------------------------------------------------------------
    |
    | Default locale and currency for the application.
    |
    */

    'locale' => env('CRM_LOCALE', 'de_DE'),
    'currency' => env('CRM_CURRENCY', 'EUR'),
    'currency_symbol' => env('CRM_CURRENCY_SYMBOL', '€'),

];
