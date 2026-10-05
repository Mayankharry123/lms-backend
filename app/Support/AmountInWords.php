<?php

/**
 * AmountInWords
 * -----------------------------------------
 * Formats money in Indian grouping and converts a rupee amount into words.
 *
 * @package App\Support
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Support;

class AmountInWords
{
    /**
     * Format a number as Indian currency without a currency symbol.
     */
    public static function format(float $amount): string
    {
        $negative = $amount < 0;
        $amount = abs(round($amount, 2));
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $integer = $parts[0];
        $decimal = $parts[1];
        $lastThree = substr($integer, -3);
        $rest = substr($integer, 0, -3);

        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $integer = $rest . ',' . $lastThree;
        }

        $formatted = $decimal === '00' ? $integer : $integer . '.' . $decimal;

        return ($negative ? '-' : '') . $formatted;
    }

    /**
     * Convert a rupee amount to words.
     */
    public static function rupees(float $amount): string
    {
        $amount = round(abs($amount), 2);
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);
        $words = 'Rupees ' . self::indian($rupees);

        if ($paise > 0) {
            $words .= ' and ' . self::indian($paise) . ' Paise';
        }

        return $words . ' Only.';
    }

    /**
     * Convert an integer using the Indian lakh and crore scale.
     */
    private static function indian(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $parts = [];
        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;

        if ($crore > 0) {
            $parts[] = self::belowHundred($crore) . ' Crore';
        }

        if ($lakh > 0) {
            $parts[] = self::belowHundred($lakh) . ' Lakh';
        }

        if ($thousand > 0) {
            $parts[] = self::belowHundred($thousand) . ' Thousand';
        }

        if ($number > 0) {
            $parts[] = self::belowThousand($number);
        }

        return implode(' ', $parts);
    }

    /**
     * Convert 1 to 999.
     */
    private static function belowThousand(int $number): string
    {
        $hundred = intdiv($number, 100);
        $rest = $number % 100;
        $words = '';

        if ($hundred > 0) {
            $words = self::belowHundred($hundred) . ' Hundred';
        }

        if ($rest > 0) {
            $words = trim($words . ' ' . self::belowHundred($rest));
        }

        return $words;
    }

    /**
     * Convert 1 to 99.
     */
    private static function belowHundred(int $number): string
    {
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number < 20) {
            return $ones[$number];
        }

        $word = $tens[intdiv($number, 10)];
        $remainder = $number % 10;

        if ($remainder > 0) {
            $word .= ' ' . $ones[$remainder];
        }

        return $word;
    }
}
