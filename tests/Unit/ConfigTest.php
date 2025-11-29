<?php

test('crm config is loaded', function () {
    expect(config('crm'))->toBeArray();
});

test('company information is configured', function () {
    $company = config('crm.company');

    expect($company)->toBeArray()
        ->and($company)->toHaveKeys(['name', 'legal_name', 'email', 'address_line_1', 'city', 'country']);
});

test('tax information is configured', function () {
    $tax = config('crm.tax');

    expect($tax)->toBeArray()
        ->and($tax)->toHaveKeys(['tax_number', 'vat_id', 'default_rate', 'reduced_rate'])
        ->and($tax['default_rate'])->toBeFloat()
        ->and($tax['reduced_rate'])->toBeFloat();
});

test('default tax rate is 19 percent', function () {
    expect(config('crm.tax.default_rate'))->toBe(19.0);
});

test('reduced tax rate is 7 percent', function () {
    expect(config('crm.tax.reduced_rate'))->toBe(7.0);
});

test('bank information is configured', function () {
    $bank = config('crm.bank');

    expect($bank)->toBeArray()
        ->and($bank)->toHaveKeys(['name', 'account_holder', 'iban', 'bic']);
});

test('invoice settings are configured', function () {
    $invoice = config('crm.invoice');

    expect($invoice)->toBeArray()
        ->and($invoice)->toHaveKeys(['number_prefix', 'number_padding', 'default_payment_terms'])
        ->and($invoice['number_prefix'])->toBe('INV')
        ->and($invoice['number_padding'])->toBe(4)
        ->and($invoice['default_payment_terms'])->toBe(30);
});

test('quote settings are configured', function () {
    $quote = config('crm.quote');

    expect($quote)->toBeArray()
        ->and($quote)->toHaveKeys(['number_prefix', 'number_padding', 'default_validity_days'])
        ->and($quote['number_prefix'])->toBe('Q')
        ->and($quote['number_padding'])->toBe(4)
        ->and($quote['default_validity_days'])->toBe(30);
});

test('locale and currency are configured', function () {
    expect(config('crm.locale'))->toBe('de_DE')
        ->and(config('crm.currency'))->toBe('EUR')
        ->and(config('crm.currency_symbol'))->toBe('€');
});
