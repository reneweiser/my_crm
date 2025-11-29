<?php

namespace App\Services;

class CrmConfig
{
    /**
     * Get company information.
     */
    public static function company(?string $key = null): mixed
    {
        return $key
            ? config("crm.company.{$key}")
            : config('crm.company');
    }

    /**
     * Get tax information.
     */
    public static function tax(?string $key = null): mixed
    {
        return $key
            ? config("crm.tax.{$key}")
            : config('crm.tax');
    }

    /**
     * Get bank information.
     */
    public static function bank(?string $key = null): mixed
    {
        return $key
            ? config("crm.bank.{$key}")
            : config('crm.bank');
    }

    /**
     * Get invoice settings.
     */
    public static function invoice(?string $key = null): mixed
    {
        return $key
            ? config("crm.invoice.{$key}")
            : config('crm.invoice');
    }

    /**
     * Get quote settings.
     */
    public static function quote(?string $key = null): mixed
    {
        return $key
            ? config("crm.quote.{$key}")
            : config('crm.quote');
    }

    /**
     * Get the default tax rate as a percentage.
     */
    public static function defaultTaxRate(): float
    {
        return (float) config('crm.tax.default_rate');
    }

    /**
     * Get the reduced tax rate as a percentage.
     */
    public static function reducedTaxRate(): float
    {
        return (float) config('crm.tax.reduced_rate');
    }

    /**
     * Get the default tax rate as a decimal multiplier (e.g., 0.19 for 19%).
     */
    public static function defaultTaxMultiplier(): float
    {
        return self::defaultTaxRate() / 100;
    }

    /**
     * Get the reduced tax rate as a decimal multiplier (e.g., 0.07 for 7%).
     */
    public static function reducedTaxMultiplier(): float
    {
        return self::reducedTaxRate() / 100;
    }

    /**
     * Get the currency symbol.
     */
    public static function currencySymbol(): string
    {
        return config('crm.currency_symbol');
    }

    /**
     * Get the currency code.
     */
    public static function currency(): string
    {
        return config('crm.currency');
    }

    /**
     * Get the locale.
     */
    public static function locale(): string
    {
        return config('crm.locale');
    }

    /**
     * Format a monetary amount with currency symbol.
     */
    public static function formatMoney(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? self::currency();
        $locale = self::locale();

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Calculate tax amount from a net amount.
     */
    public static function calculateTax(float $netAmount, ?float $taxRate = null): float
    {
        $rate = $taxRate ?? self::defaultTaxRate();

        return round($netAmount * ($rate / 100), 2);
    }

    /**
     * Calculate gross amount from net amount and tax rate.
     */
    public static function calculateGross(float $netAmount, ?float $taxRate = null): float
    {
        $tax = self::calculateTax($netAmount, $taxRate);

        return round($netAmount + $tax, 2);
    }
}
