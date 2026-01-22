<?php

namespace App\Helpers;

class NumberToText
{
    /**
     * Convert number to Vietnamese text
     * 
     * @param float $number
     * @return string
     */
    public static function convert(float $number): string
    {
        $ones = [
            '', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín',
            'mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm',
            'mười sáu', 'mười bảy', 'mười tám', 'mười chín'
        ];

        $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        $hundreds = ['', 'một trăm', 'hai trăm', 'ba trăm', 'bốn trăm', 'năm trăm', 'sáu trăm', 'bảy trăm', 'tám trăm', 'chín trăm'];

        if ($number == 0) {
            return 'không';
        }

        // Handle decimal part
        $integerPart = (int)$number;
        $decimalPart = round(($number - $integerPart) * 100);

        $result = self::convertInteger($integerPart, $ones, $tens, $hundreds);

        if ($decimalPart > 0) {
            $result .= ' phẩy ' . self::convertInteger($decimalPart, $ones, $tens, $hundreds);
        }

        return ucfirst($result);
    }

    /**
     * Convert integer to Vietnamese text
     */
    private static function convertInteger(int $number, array $ones, array $tens, array $hundreds): string
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $ten = (int)($number / 10);
            $one = $number % 10;
            
            if ($one == 0) {
                return $tens[$ten];
            } elseif ($one == 1) {
                return $tens[$ten] . ' mốt';
            } elseif ($one == 5) {
                return $tens[$ten] . ' lăm';
            } else {
                return $tens[$ten] . ' ' . $ones[$one];
            }
        }

        if ($number < 1000) {
            $hundred = (int)($number / 100);
            $remainder = $number % 100;
            
            if ($remainder == 0) {
                return $hundreds[$hundred];
            } else {
                return $hundreds[$hundred] . ' ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
            }
        }

        if ($number < 1000000) {
            $thousand = (int)($number / 1000);
            $remainder = $number % 1000;
            
            $thousandText = self::convertInteger($thousand, $ones, $tens, $hundreds);
            
            if ($remainder == 0) {
                return $thousandText . ' nghìn';
            } elseif ($remainder < 100) {
                return $thousandText . ' nghìn không trăm ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
            } else {
                return $thousandText . ' nghìn ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
            }
        }

        if ($number < 1000000000) {
            $million = (int)($number / 1000000);
            $remainder = $number % 1000000;
            
            $millionText = self::convertInteger($million, $ones, $tens, $hundreds);
            
            if ($remainder == 0) {
                return $millionText . ' triệu';
            } elseif ($remainder < 1000) {
                return $millionText . ' triệu không nghìn ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
            } else {
                return $millionText . ' triệu ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
            }
        }

        // For billions
        $billion = (int)($number / 1000000000);
        $remainder = $number % 1000000000;
        
        $billionText = self::convertInteger($billion, $ones, $tens, $hundreds);
        
        if ($remainder == 0) {
            return $billionText . ' tỷ';
        } else {
            return $billionText . ' tỷ ' . self::convertInteger($remainder, $ones, $tens, $hundreds);
        }
    }

    /**
     * Convert number to Vietnamese currency text
     * 
     * @param float $number
     * @return string
     */
    public static function convertCurrency(float $number): string
    {
        $text = self::convert($number);
        return $text . ' đồng';
    }
}

