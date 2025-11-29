<?php

use App\Services\CrmConfig;

test('can get company information', function () {
    $company = CrmConfig::company();

    expect($company)->toBeArray()
        ->and($company)->toHaveKey('name');
});

test('can get specific company field', function () {
    $name = CrmConfig::company('name');

    expect($name)->toBeString();
});

test('can get tax information', function () {
    $tax = CrmConfig::tax();

    expect($tax)->toBeArray()
        ->and($tax)->toHaveKey('default_rate');
});

test('can get default tax rate', function () {
    $rate = CrmConfig::defaultTaxRate();

    expect($rate)->toBe(19.0);
});

test('can get reduced tax rate', function () {
    $rate = CrmConfig::reducedTaxRate();

    expect($rate)->toBe(7.0);
});

test('can get tax multiplier', function () {
    $multiplier = CrmConfig::defaultTaxMultiplier();

    expect($multiplier)->toBe(0.19);
});

test('can get reduced tax multiplier', function () {
    $multiplier = CrmConfig::reducedTaxMultiplier();

    expect($multiplier)->toBe(0.07);
});

test('can get currency symbol', function () {
    $symbol = CrmConfig::currencySymbol();

    expect($symbol)->toBe('€');
});

test('can get currency code', function () {
    $currency = CrmConfig::currency();

    expect($currency)->toBe('EUR');
});

test('can get locale', function () {
    $locale = CrmConfig::locale();

    expect($locale)->toBe('de_DE');
});

test('can calculate tax amount', function () {
    $tax = CrmConfig::calculateTax(100.00);

    expect($tax)->toBe(19.00);
});

test('can calculate tax amount with custom rate', function () {
    $tax = CrmConfig::calculateTax(100.00, 7.0);

    expect($tax)->toBe(7.00);
});

test('can calculate gross amount', function () {
    $gross = CrmConfig::calculateGross(100.00);

    expect($gross)->toBe(119.00);
});

test('can calculate gross amount with custom rate', function () {
    $gross = CrmConfig::calculateGross(100.00, 7.0);

    expect($gross)->toBe(107.00);
});

test('can format money', function () {
    $formatted = CrmConfig::formatMoney(1234.56);

    expect($formatted)->toBeString()
        ->and($formatted)->toContain('1');
});

test('can get bank information', function () {
    $bank = CrmConfig::bank();

    expect($bank)->toBeArray()
        ->and($bank)->toHaveKey('iban');
});

test('can get invoice settings', function () {
    $invoice = CrmConfig::invoice();

    expect($invoice)->toBeArray()
        ->and($invoice)->toHaveKey('number_prefix');
});

test('can get quote settings', function () {
    $quote = CrmConfig::quote();

    expect($quote)->toBeArray()
        ->and($quote)->toHaveKey('number_prefix');
});
