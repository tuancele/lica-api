<?php
if (! function_exists('countProduct')) {
    function countProduct($variant,$type){
        $total = 0;
        $product = App\Modules\Warehouse\Models\ProductWarehouse::select('qty')->where([['variant_id',$variant],['type',$type]])->get();
        $total = array_sum(array_column($product->toArray(), 'qty'));
        return $total;
    }
}
if (! function_exists('countPrice')) {
    function countPrice($variant,$type){
        $total = 0;
        $product = App\Modules\Warehouse\Models\ProductWarehouse::select('price')->where([['variant_id',$variant],['type',$type]])->get();
        $total = array_sum(array_column($product->toArray(), 'price'));
        return $total;
    }
}

if (! function_exists('convertNumberToWords')) {
    /**
     * Convert number to Vietnamese words
     * @param int|float $number
     * @return string
     */
    function convertNumberToWords($number) {
        if ($number == 0) return 'không';
        
        $number = (int)$number;
        if ($number < 0) return 'âm ' . convertNumberToWords(abs($number));
        
        $ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $tens = ['', 'mười', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        
        $result = '';
        
        // Tỷ
        if ($number >= 1000000000) {
            $billions = floor($number / 1000000000);
            $result .= convertNumberToWords($billions) . ' tỷ ';
            $number %= 1000000000;
            if ($number > 0 && $number < 1000000) {
                $result .= 'không triệu ';
            }
        }
        
        // Triệu
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $result .= convertNumberToWords($millions) . ' triệu ';
            $number %= 1000000;
            if ($number > 0 && $number < 1000) {
                $result .= 'không nghìn ';
            }
        }
        
        // Nghìn
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $result .= convertNumberToWords($thousands) . ' nghìn ';
            $number %= 1000;
        }
        
        // Trăm
        if ($number >= 100) {
            $hundred = floor($number / 100);
            $result .= $ones[$hundred] . ' trăm ';
            $number %= 100;
            if ($number > 0 && $number < 10) {
                $result .= 'lẻ ';
            }
        }
        
        // Chục và đơn vị
        if ($number >= 20) {
            $ten = floor($number / 10);
            $result .= $tens[$ten] . ' ';
            $number %= 10;
            if ($number > 0) {
                $result .= ($number == 5 ? 'lăm' : ($number == 1 ? 'mốt' : $ones[$number])) . ' ';
            }
        } elseif ($number >= 10) {
            if ($number == 10) {
                $result .= 'mười ';
            } else {
                $unit = $number % 10;
                $result .= 'mười ' . ($unit == 5 ? 'lăm' : ($unit == 1 ? 'một' : $ones[$unit])) . ' ';
            }
            $number = 0;
        } elseif ($number > 0) {
            $result .= ($number == 5 ? 'lăm' : ($number == 1 && strlen($result) > 0 ? 'mốt' : $ones[$number])) . ' ';
        }
        
        return ucfirst(trim($result));
    }
}

if (! function_exists('getVatInvoiceFromContent')) {
    /**
     * Extract VAT invoice number from content
     * @param string $content
     * @return string
     */
    function getVatInvoiceFromContent($content) {
        if (empty($content)) return '';
        
        if (preg_match('/Số hóa đơn VAT:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }
}

if (! function_exists('getImportReceiptCode')) {
    /**
     * Generate import receipt code
     * @param int $id
     * @param string $createdAt
     * @return string
     */
    function getImportReceiptCode($id, $createdAt = null) {
        $date = $createdAt ? date('Ymd', strtotime($createdAt)) : date('Ymd');
        return 'PH-' . $date . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('getExportReceiptCode')) {
    /**
     * Generate export receipt code
     * @param int $id
     * @param string $createdAt
     * @return string
     */
    function getExportReceiptCode($id, $createdAt = null) {
        $date = $createdAt ? date('Ymd', strtotime($createdAt)) : date('Ymd');
        return 'PX-' . $date . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('generateQRCode')) {
    /**
     * Generate QR code image from URL
     * @param string $url
     * @param int $size
     * @return string QR code image URL
     */
    function generateQRCode($url, $size = 150) {
        // Use reliable online QR code service
        // Google Charts API or QR Server API
        $encodedUrl = urlencode($url);
        
        // Option 1: QR Server API (reliable and fast)
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . $encodedUrl . '&format=png&margin=1';
        
        // Option 2: Google Charts API (alternative)
        // return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . $encodedUrl;
    }
}