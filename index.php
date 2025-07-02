<?php
// Başlangıçta config dosyasını dahil ediyoruz
require_once 'config.php';

// Veritabanından genel ayarları çek
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

// Carousel slaytlarını çek
$stmt_carousel = $pdo->query("SELECT * FROM carousel_slides WHERE is_active = TRUE ORDER BY order_num ASC");
$carousel_slides = $stmt_carousel->fetchAll();

// Bonusları çek - sort_order'a göre sıralanıyor
$stmt_bonuses = $pdo->query("SELECT * FROM bonuses WHERE status = 'active' ORDER BY sort_order ASC, id DESC");
$bonuses = $stmt_bonuses->fetchAll();

// Üst bar promosyonunu çek
$top_promo = [
    'logo' => $settings['top_promo_logo'] ?? '',
    'text' => $settings['top_promo_text'] ?? '',
    'button_text' => $settings['top_promo_button_text'] ?? '',
    'button_link' => $settings['top_promo_button_link'] ?? '#'
];

// Mevcut kullanıcının giriş yapıp yapmadığını kontrol et
$is_logged_in = isset($_SESSION['user_id']);

// Aktif popup'ları çek
$active_popups = [];
try {
    $stmt_active_popups = $pdo->query("SELECT * FROM popups WHERE is_active = TRUE ORDER BY display_order ASC LIMIT 1"); // Sadece ilk aktif popup'ı gösterelim
    $active_popups = $stmt_active_popups->fetch();
} catch (PDOException $e) {
    error_log("Popup çekilirken hata: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bonus Siteleri</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="<?php echo BASE_URL; ?>js/fingerprint.js" defer></script>
    <style>
        /* Popup için temel stil */
        .website-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .website-popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .website-popup-content {
            background-color: #fff; /* Veya transparan, popup türüne göre değişir */
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            position: relative;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
        }
        .website-popup-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .website-popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4d4d;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 1.2rem;
            line-height: 30px;
            cursor: pointer;
            text-align: center;
            font-weight: bold;
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }
        .website-popup-close:hover {
            opacity: 1;
        }
        .website-popup-text-content {
            background-color: #2a2a2a;
            color: #fff;
            padding: 25px;
            border-radius: 8px;
        }
        .website-popup-text-content h3 {
            color: #f5a623;
            margin-bottom: 15px;
        }
        .website-popup-text-content p {
            margin-bottom: 15px;
        }
        .website-popup-button {
            display: inline-block;
            background-color: #4a90e2;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>
  <div class="site-header">
    <h1>Deneme Bonusu Veren Siteler</h1>
    <div class="header-buttons">
      <?php if (!$is_logged_in): ?>
        <button onclick="location.href='login.php'">Giriş Yap</button>
        <button onclick="location.href='register.php'">Üye Ol</button>
      <?php else: ?>
        <button onclick="location.href='profile.php'">Profilim</button>
        <button onclick="location.href='logout.php'">Çıkış Yap</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="scrolling-text-container">
    <div class="scrolling-text"><?php echo htmlspecialchars($settings['scrolling_text'] ?? '🎉 En Güncel Deneme Bonusu Veren Siteleri Keşfet! Kaçırma! 🎁 En Avantajlı VIP Bonuslar Seni Bekliyor!'); ?></div>
  </div>

  <div class="carousel-container">
    <div class="carousel-track" id="carouselTrack">
      <?php if (!empty($carousel_slides)): ?>
          <?php foreach ($carousel_slides as $slide): ?>
            <div class="carousel-slide">
              <?php
              // Carousel görsel yolu oluşturma mantığı
              $carousel_image_src = htmlspecialchars($slide['image_url'] ?? '');
              if (!empty($carousel_image_src) && strpos($carousel_image_src, 'http') !== 0) {
                  // Tüm yerel görseller admin/uploads/bonuses/ altında olmalı
                  $carousel_image_src = BASE_URL . 'admin/uploads/bonuses/' . basename($carousel_image_src); 
              }
              ?>
              <img src="<?php echo $carousel_image_src; ?>" alt="<?php echo htmlspecialchars($slide['title'] ?? 'Slayt Görseli'); ?>">
              <h2><?php echo htmlspecialchars($slide['title'] ?? ''); ?></h2>
              <p><?php echo htmlspecialchars($slide['description'] ?? ''); ?></p>
            </div>
          <?php endforeach; ?>
      <?php else: ?>
          <div class="carousel-slide">
              <img src="https://via.placeholder.com/80x80?text=Slayt Yok" alt="No Slide">
              <h2>Henüz Slayt Yok</h2>
              <p>Admin panelinden yeni slaytlar ekleyebilirsiniz.</p>
          </div>
      <?php endif; ?>
    </div>
    <?php if (count($carousel_slides) > 1): ?>
    <button class="carousel-button prev" onclick="moveSlide(-1)">‹</button>
    <button class="carousel-button next" onclick="moveSlide(1)">›</button>
    <?php endif; ?>
  </div>

  <?php if (!empty($top_promo['text'])): ?>
  <div class="top-bonus-bar">
    <img src="<?php echo htmlspecialchars($top_promo['logo'] ?? ''); ?>" alt="Promosyon Logo">
    <span><?php echo htmlspecialchars($top_promo['text'] ?? ''); ?></span>
    <button onclick="location.href='<?php echo htmlspecialchars($top_promo['button_link'] ?? '#'); ?>'"><?php echo htmlspecialchars($top_promo['button_text'] ?? ''); ?></button>
  </div>
  <?php endif; ?>

  <div class="search-filter-bar">
    <input type="text" id="searchInput" placeholder="Başlık ara...">
    <select id="filterSelect">
      <option value="all">Tümü</option>
      <?php
      $stmt_categories = $pdo->query("SELECT DISTINCT category FROM bonuses");
      while ($cat = $stmt_categories->fetchColumn()) {
          echo '<option value="' . htmlspecialchars($cat ?? '') . '">' . ucfirst(htmlspecialchars($cat ?? '')) . '</option>';
      }
      ?>
    </select>
  </div>

  <section class="bonus-list">
    <?php if (!empty($bonuses)): ?>
        <?php foreach ($bonuses as $bonus): ?>
        <div class="bonus-item" data-category="<?php echo htmlspecialchars($bonus['category'] ?? ''); ?>">
          <div class="info">
            <?php
            // Bonus görsel yolu oluşturma mantığı: admin/uploads/bonuses/ altında beklendiği için
            $bonus_image_src = htmlspecialchars($bonus['image_url'] ?? '');
            if (!empty($bonus_image_src)) {
                if (strpos($bonus_image_src, 'http') !== 0) { // Eğer URL 'http' ile başlamıyorsa (yani yerel bir yolsa)
                    // BASE_URL + admin/uploads/bonuses/resim.png şeklinde ayarlandı
                    $bonus_image_src = BASE_URL . 'admin/uploads/bonuses/' . basename($bonus_image_src); 
                }
            } else {
                $bonus_image_src = 'https://via.placeholder.com/80x80?text=Gorsel Yok'; // Varsayılan görsel
            }
            ?>
            <img src="<?php echo $bonus_image_src; ?>" alt="<?php echo htmlspecialchars($bonus['title'] ?? 'Bonus Görseli'); ?>">
            <span class="badge"><?php echo htmlspecialchars($bonus['bonus_code'] ?? ''); ?></span>
            <div class="details">
              <strong><?php echo htmlspecialchars($bonus['title'] ?? ''); ?></strong>
              <span><?php echo htmlspecialchars($bonus['description'] ?? ''); ?></span>
            </div>
          </div>
          <a href="track_bonus_click.php?bonus_id=<?php echo htmlspecialchars($bonus['id']); ?>&link=<?php echo urlencode($bonus['link'] ?? ''); ?>" class="bonus-btn">Bonusu Al</a>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; margin-top: 2rem; color: #ccc;">Henüz bonus bulunmamaktadır. Lütfen admin panelinden ekleyiniz.</p>
    <?php endif; ?>
  </section>

  <footer>
    <a href="#">
      <img src="https://i.ibb.co/14MQ3MK/teams.png" alt="Contact" width="24">
      <span>MicrosoftTeams</span>
    </a>
    <a href="https://t.me/circusmarketing">
      <img src="https://i.ibb.co/7cQGKC4/tg.png" alt="Telegram" width="24">
      <span>Telegram</span>
    </a>
  </footer>

  <?php if (!empty($active_popups)): ?>
    <div class="website-popup-overlay" id="websitePopupOverlay">
        <div class="website-popup-content" id="websitePopupContent">
            <button class="website-popup-close" id="websitePopupClose">X</button>
            <?php if ($active_popups['type'] == 'image' && !empty($active_popups['image_url'])): ?>
                <?php
                // Popup görsel yolu oluşturma (admin/uploads/bonuses/ klasöründen beklendiği için)
                $popup_display_src = htmlspecialchars($active_popups['image_url'] ?? '');
                if (!empty($popup_display_src) && strpos($popup_display_src, 'http') !== 0) {
                    // BASE_URL + admin/uploads/bonuses/popup_XXX.png şeklinde ayarlandı
                    $popup_display_src = BASE_URL . $popup_display_src; 
                }
                ?>
                <a href="<?php echo htmlspecialchars($active_popups['link'] ?? '#'); ?>" target="_blank">
                    <img src="<?php echo $popup_display_src; ?>" alt="<?php echo htmlspecialchars($active_popups['title'] ?? 'Popup'); ?>">
                </a>
            <?php elseif ($active_popups['type'] == 'text' && !empty($active_popups['content'])): ?>
                <div class="website-popup-text-content">
                    <h3><?php echo htmlspecialchars($active_popups['title'] ?? 'Duyuru'); ?></h3>
                    <p><?php echo $active_popups['content']; ?></p>
                    <?php if (!empty($active_popups['link'])): ?>
                        <a href="<?php echo htmlspecialchars($active_popups['link']); ?>" target="_blank" class="website-popup-button">Daha Fazla Bilgi</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
  <?php endif; ?>

  <script>
    // Carousel JS kodu
    let currentIndex = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const track = document.getElementById('carouselTrack');

    function updateCarousel() {
      if (slides.length === 0) return;
      const width = slides[0].clientWidth;
      track.style.transform = `translateX(-${currentIndex * width}px)`;
    }

    function moveSlide(dir) {
      if (slides.length === 0) return;
      currentIndex += dir;
      if (currentIndex < 0) currentIndex = slides.length - 1;
      if (currentIndex >= slides.length) currentIndex = 0;
      updateCarousel();
    }

    const hasMultipleSlides = <?php echo (count($carousel_slides) > 1) ? 'true' : 'false'; ?>;
    if (hasMultipleSlides) {
      setInterval(() => moveSlide(1), 6000);
    }

    window.addEventListener('resize', updateCarousel);
    window.addEventListener('load', updateCarousel);


    // Arama ve Filtreleme JS kodu
    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');
    const bonusItems = document.querySelectorAll('.bonus-item');

    function filterBonuses() {
      const searchText = searchInput.value.toLowerCase();
      const selectedFilter = filterSelect.value;

      bonusItems.forEach(item => {
        const title = item.querySelector('.details strong')?.textContent?.toLowerCase() || '';
        const category = item.dataset.category || '';

        const matchesSearch = title.includes(searchText);
        const matchesFilter = selectedFilter === 'all' || category === selectedFilter;

        item.style.display = (matchesSearch && matchesFilter) ? 'flex' : 'none';
      });
    }

    searchInput.addEventListener('input', filterBonuses);
    filterSelect.addEventListener('change', filterBonuses);

    // Popup JS kodu
    document.addEventListener('DOMContentLoaded', function() {
        const popupOverlay = document.getElementById('websitePopupOverlay');
        const popupCloseButton = document.getElementById('websitePopupClose');

        if (popupOverlay && popupCloseButton) {
            setTimeout(() => {
                popupOverlay.classList.add('active');
            }, 1000);

            popupCloseButton.addEventListener('click', () => {
                popupOverlay.classList.remove('active');
            });

            popupOverlay.addEventListener('click', (event) => {
                if (event.target === popupOverlay) {
                    popupOverlay.classList.remove('active');
                }
            });
        }
    });
  </script>
</body>
</html>