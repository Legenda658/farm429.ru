<?php
function generate_qr_code($text, $size = 200) {
    $encoded_text = urlencode($text);
    $light_qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_text}&bgcolor=ffffff&color=000000&margin=1";
    $dark_qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_text}&bgcolor=212529&color=ffffff&margin=1";
    return sprintf(
        '<div class="qr-code-container theme-adaptive" style="padding: 20px; border-radius: 10px; display: inline-block; margin: 10px 0;">
            <img src="%s" alt="QR Code" class="qr-light img-fluid" style="width: %dpx; height: %dpx; display: none;">
            <img src="%s" alt="QR Code" class="qr-dark img-fluid" style="width: %dpx; height: %dpx; display: none;">
        </div>',
        $light_qr_url,
        $size,
        $size,
        $dark_qr_url,
        $size,
        $size
    );
}
?> 