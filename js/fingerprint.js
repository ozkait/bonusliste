// js/fingerprint.js

(function() {
    // Tarayıcıdan çeşitli verileri toplayan fonksiyon
    function getBrowserFingerprint() {
        const fingerprint = {};

        // 1. User-Agent (Zaten PHP tarafında kontrol ediliyor ama JS tarafında da alınabilir)
        fingerprint.userAgent = navigator.userAgent;

        // 2. Ekran Boyutları
        fingerprint.screenWidth = window.screen.width;
        fingerprint.screenHeight = window.screen.height;
        fingerprint.colorDepth = window.screen.colorDepth;

        // 3. Tarayıcı Dili
        fingerprint.language = navigator.language || navigator.userLanguage;

        // 4. Zaman Dilimi Ofseti
        try {
            fingerprint.timezoneOffset = new Date().getTimezoneOffset();
            fingerprint.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            fingerprint.timezoneOffset = 'N/A';
            fingerprint.timezone = 'N/A';
        }

        // 5. Platform Bilgisi
        fingerprint.platform = navigator.platform;

        // 6. Donanım Eşzamanlılığı (CPU Çekirdek Sayısı)
        fingerprint.hardwareConcurrency = navigator.hardwareConcurrency || 'N/A';

        // 7. Tarayıcı Eklentileri (Plugins) - Botlar genellikle eklenti barındırmaz
        fingerprint.plugins = Array.from(navigator.plugins).map(p => p.name + '::' + p.description).join(';');

        // 8. Canvas Parmak İzi (Görsel renderlama farklılıklarını kullanır)
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 20;
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText("Browser Fingerprint", 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText("Browser Fingerprint", 4, 17);
            fingerprint.canvasHash = canvas.toDataURL().split(',')[1];
        } catch (e) {
            fingerprint.canvasHash = 'N/A';
        }

        // 9. WebGL Parmak İzi (Grafik kartı ve sürücülerini kullanır)
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                fingerprint.webglVendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) || 'N/A';
                fingerprint.webglRenderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || 'N/A';
            } else {
                fingerprint.webglVendor = 'N/A';
                fingerprint.webglRenderer = 'N/A';
            }
        } catch (e) {
            fingerprint.webglVendor = 'N/A';
            fingerprint.webglRenderer = 'N/A';
        }

        // 10. WebRTC IP Handling (Dahili IP'yi sızdırmaz ama varlığını kontrol edebiliriz, bu daha çok network ile alakalı)
        // fingerprint.webrtcSupport = typeof RTCPeerConnection !== 'undefined';

        // Daha fazla özellik eklenebilir (örn. fontlar, ses özellikleri) ancak bunlar başlangıç için iyi bir set.

        return fingerprint;
    }

    // Parmak izini sunucuya gönderen fonksiyon
    function sendFingerprintToServer() {
        const fingerprintData = getBrowserFingerprint();
        
        // Sunucuya POST isteği gönder (Ajax/Fetch API kullanıyoruz)
        // Bu URL'yi sunucu tarafında oluşturacağımız PHP endpoint'ine ayarlayacağız.
        const apiUrl = '<?php echo BASE_URL; ?>api/fingerprint_data.php'; 

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(fingerprintData)
        })
        .then(response => {
            // Yanıt JSON değilse veya bir hata varsa yakala
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || 'Network response was not ok'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Sunucudan gelen parmak izi kararını bir çereze kaydet (isteğe bağlı)
                // Bu, aynı kullanıcının/botun tekrar tekrar analiz edilmesini engeller.
                if (data.decision) {
                    document.cookie = `_fp_decision=${data.decision}; path=/; max-age=${3600 * 24}; SameSite=Lax`; // 24 saat
                }
                // console.log('Fingerprint data sent successfully:', data);
            } else {
                console.error('Failed to send fingerprint data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error sending fingerprint data:', error);
            // network hatası veya server hatası durumunda çerez kaydetmek gibi bir fallback düşünülebilir.
        });
    }

    // Sayfa yüklendikten sonra parmak izini gönder
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sendFingerprintToServer);
    } else {
        sendFingerprintToServer();
    }
})();