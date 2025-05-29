<?php
require 'config.php';
require 'auth.php';

// Sayfalama için gerekli değişkenler
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filtreleme ve arama
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Veritabanından verileri çekme
$sql = "SELECT * FROM isler_tb WHERE 1=1"; // Temel sorgu
$params = [];

if ($filter) {
    $sql .= " AND durum = :filter";
    $params[':filter'] = $filter;
}

if ($search) {
    $sql .= " AND (sube LIKE :search OR kisi LIKE :search OR iletisim LIKE :search OR konu LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY tarih DESC LIMIT $start, $limit";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam satır sayısını hesaplama
$sql_total = "SELECT COUNT(*) FROM isler_tb WHERE 1=1";
if ($filter) {
    $sql_total .= " AND durum = :filter";
}
if ($search) {
    $sql_total .= " AND (sube LIKE :search OR kisi LIKE :search OR iletisim LIKE :search OR konu LIKE :search)";
}

$stmt_total = $pdo->prepare($sql_total);
$params_total = [];
if ($filter) {
    $params_total[':filter'] = $filter;
}
if ($search) {
    $params_total[':search'] = "%$search%";
}

$stmt_total->execute($params_total);
$total_results = $stmt_total->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Durumun CSS class'ını döndüren yardımcı fonksiyon
function getStatusClass($durum) {
    switch ($durum) {
        case 'Tamamlanmadı':
            return 'status-not-completed';
        case 'Tamamlandı':
            return 'status-completed';
        case 'Bilgi':
            return 'status-info';
        case 'iletildi':
            return 'status-dark';
        default:
            return '';
    }
}

// Tarih formatını dönüştüren yardımcı fonksiyon
function formatDate($date) {
    $datetime = new DateTime($date);
    return $datetime->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="index.css">
    <title>Günlük İşler</title>
    <style>
        .gallery-item {
            text-align: center;  /* İçerikleri ortalar */
            font-size: 1.5em;    /* Yazı boyutu (1.5 kat büyük) */
            color: #555;         /* Gri-mor yazı rengi */
        }
        .gallery-item img { /* Resimlere hover efekti eklemek için*/
            width: 50px;
            height: 50px;
        }
        .lightbox {
            display: none;           /* Başlangıçta gizli */
            position: fixed;         /* Sayfaya sabitlenir */
            z-index: 1;             /* Diğer elementlerin üstünde */
            background-color: rgba(0, 0, 0, 0.8); /* Yarı saydam siyah arka plan */
            justify-content: center; /* İçeriği yatayda ortalar */
            align-items: center;     /* İçeriği dikeyde ortalar */
            
        }
        .lightbox img {
            max-width: 80%;
            max-height: 80%;
        }
        .close-btn {
            position: absolute; /* Lightbox içinde sabit konum */
            top: 20px;
            right: 40px;
            font-size: 40px;    /* Büyük boyut */
            color: #fff;       /* Beyaz renk */
        }
        .fas {
            color: #17153B;
        }

        table {
            width: 100%;          /* Tam genişlik */
            border-collapse: collapse; /* Hücre kenarlıklarını birleştirir */
        }

        th, td {
            border-bottom: 1px solid #e0e0e0; /* Sadece alt kenarlık */
            padding: 10px;  /*Hücre içi boşluk ayarlanıyor.  Normalde 7 yapmıştık*/
        }

        th {
            background-color: #f2f2f2; /* Başlık arka plan rengi */
        }
        /* Tarih sütununun genişliğini ayarlama */
        table th:nth-child(1), table td:nth-child(1) {
            width: 90px; /* Genişliği ihtiyaca göre ayarlayın */
        }

        /* Durum renkleri */
        .status-completed { /*Tamamlanan işler*/
            background-color: #83B4FF;
            color: white;
        }
        .status-not-completed { /*Tamamlanmayan işler*/
            background-color: #F075AA;
            color: white;
        }
        .status-info {   /*Bilgi amaçlı işler*/
            background-color: #FEF9D9;
            color: black;
        }
        .status-dark {     /*İletilen/işlemde işler*/
            background-color: #F5F7F8;
            color:white;
        }
        .add-form {
            border: 1px solid #ddd;       /* Açık gri çerçeve */
            border-radius: 5px;          /* Hafif yuvarlak köşe */
            padding: 20px;               /* İç boşluk */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Hafif gölge */
            margin-bottom: 20px;         /* Alt boşluk */
        }
  
    </style>
</head>

<div style="position: absolute; bottom: 10px; right: 10px;">
    <form action="logout.php" method="post">
        <button type="submit" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer;">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </form>
</div>



<body>
    <div class="container">
        <h2>Günlük İşler</h2>

        <!-- Ekleme Formu -->
        <form action="insert.php" method="post" enctype="multipart/form-data" class="add-form">
            <input type="date" name="tarih" required>
            <input type="text" name="sube" placeholder="Şube" required>
            <input type="text" name="kisi" placeholder="Kişi" required>
            <input type="text" name="iletisim" placeholder="İletişim" maxlength="14" required>
            <input type="text" name="konu" placeholder="Konu" required>
            <select name="durum" required>
                <option value="Tamamlanmadı">Tamamlanmadı</option>
                <option value="Tamamlandı">Tamamlandı</option>
                <option value="Bilgi">Bilgi</option>
                <option value="iletildi">iletildi</option>
            </select>
            <input type="file" name="fileToUpload" id="fileToUpload">
            <input type="submit" value="Ekle" name="submit">
        </form>

        <!-- Arama ve Filtreleme Formu -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <form method="get" action="" style="flex-grow: 1; margin-right: 10px; max-width: 150px;">
                <input type="text" name="search" placeholder="Ara" value="<?php echo htmlspecialchars($search); ?>" style="width: 100%;">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="hidden" name="page" value="1"> <!-- Sayfa numarasını sıfırla -->
            </form>

            <form method="get" action="">
                <select name="filter" onchange="this.form.submit()">
                    <option value="">Tüm Durumlar</option>
                    <option value="Tamamlanmadı" <?php if ($filter == 'Tamamlanmadı') echo 'selected'; ?>>Tamamlanmadı</option>
                    <option value="Tamamlandı" <?php if ($filter == 'Tamamlandı') echo 'selected'; ?>>Tamamlandı</option>
                    <option value="Bilgi" <?php if ($filter == 'Bilgi') echo 'selected'; ?>>Bilgi</option>
                    <option value="iletildi" <?php if ($filter == 'iletildi') echo 'selected'; ?>>İletildi</option>
                </select>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="page" value="1"> <!-- Sayfa numarasını sıfırla -->
           </form>
        </div>
            <!---İnternet Hizmetleri Bağlantısı --->
        <div style="margin: 10px 0; text-align: right; padding-right: 10px;">
            <a href="internet.php" style="padding: 6px 12px; 
                                        background: #f0f0f0; 
                                        border: 1px solid #ddd; 
                                        border-radius: 3px; 
                                        color: #333; 
                                        text-decoration: none;
                                        font-size: 13px;
                                        display: inline-flex;
                                        align-items: center;
                                        gap: 5px;">
                <i class="fas fa-wifi" style="font-size: 12px;"></i> İnternet Hizmetleri
            </a>
        </div>

        <!-- Veri Tablosu -->
        <table id="taskTable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Şube</th>
                    <th>Kişi</th>
                    <th>İletişim</th>
                    <th>Konu</th>
                    <th>Durum</th>
                    <th>Resim</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td  data-id="<?php echo $row['id']; ?>" data-column="tarih"><?php echo formatDate($row['tarih']); ?></td>
                        <td  data-id="<?php echo $row['id']; ?>" data-column="sube"><?php echo htmlspecialchars($row['sube']); ?></td>
                        <td  data-id="<?php echo $row['id']; ?>" data-column="kisi"><?php echo htmlspecialchars($row['kisi']); ?></td>
                        <td  data-id="<?php echo $row['id']; ?>" data-column="iletisim"><?php echo htmlspecialchars($row['iletisim']); ?></td>
                        <td  data-id="<?php echo $row['id']; ?>" data-column="konu"><?php echo htmlspecialchars($row['konu']); ?></td>
                        <td class="<?php echo getStatusClass($row['durum']); ?>">
                            <form method="post" action="update_status.php">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <select name="durum" onchange="this.form.submit()">
                                    <option value="Tamamlanmadı" <?php if ($row['durum'] == 'Tamamlanmadı') echo 'selected'; ?>>Tamamlanmadı</option>
                                    <option value="Tamamlandı" <?php if ($row['durum'] == 'Tamamlandı') echo 'selected'; ?>>Tamamlandı</option>
                                    <option value="Bilgi" <?php if ($row['durum'] == 'Bilgi') echo 'selected'; ?>>Bilgi</option>
                                    <option value="iletildi" <?php if ($row['durum'] == 'iletildi') echo 'selected'; ?>>İletildi</option>
                                </select>
                            </form>
                        </td>
                        <td class="gallery-item" onclick="openLightbox('<?php echo htmlspecialchars($row['resim']); ?>')">
                            <?php if (!empty($row['resim'])): ?>
                                <img src="<?php echo htmlspecialchars($row['resim']); ?>" alt="resim">
                            <?php else: ?>
                                <i class="fas fa-camera-retro"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="delete-button" onclick="confirmDelete(<?php echo $row['id']; ?>)">Sil</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

     <!-- Sayfalama -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">&laquo; İlk</a>
                <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">&lt;</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">&gt;</a>
                <a href="?page=<?= $total_pages ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">Son &raquo;</a>
            <?php endif; ?>
        </div>
           

        <script>
            // lightbox açma fonksiyonu
            function openLightbox(imageUrl) {
                document.getElementById('lightbox-img').src = imageUrl;
                document.querySelector('.lightbox').style.display = 'flex';
            }

            // lightbox kapama fonksiyonu
            function closeLightbox() {
                document.querySelector('.lightbox').style.display = 'none';
            }
            function confirmDelete(id) {
                Swal.fire({
                    title: 'Emin misin?',
                    text: "Bu kaydı geri alamazsın!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Evet, sil!',
                    cancelButtonText: 'Vazgeç'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'delete.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 400) {
                                Swal.fire(
                                    'Silindi!',
                                    'Kayıt başarıyla silindi.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Hata!',
                                    'Kayıt silinirken bir hata oluştu.',
                                    'error'
                                );
                            }
                        };
                        xhr.onerror = function() {
                            Swal.fire(
                                'Hata!',
                                'Sunucuya bağlanılamadı.',
                                'error'
                            );
                        };
                        xhr.send('id=' + id);
                    }
                });
            }
                

            // Tablo hücrelerini güncelleme
            document.addEventListener('DOMContentLoaded', function() {
                var taskTable = document.getElementById('taskTable');

                taskTable.addEventListener('focusout', function(event) {
                    var target = event.target;
                    if (target.hasAttribute('contenteditable')) {
                        var id = target.getAttribute('data-id');
                        var column = target.getAttribute('data-column');
                        var value = target.textContent.trim();

                        // Tarih formatını düzeltme (eğer tarih sütunu ise)
                        if (column === 'tarih') {
                            var parts = value.split('/');
                            if (parts.length === 3) {
                                value = parts[2] + '-' + parts[1] + '-' + parts[0];
                            }
                        }

                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'update.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 400) {
                                var response = JSON.parse(xhr.responseText);
                                if (!response.success) {
                                    console.error('Güncelleme başarısız: ' + (response.error || 'Bilinmeyen hata'));
                                    // Hata durumunda eski değeri geri yükle
                                    target.textContent = target.getAttribute('data-old-value');
                                }
                            } else {
                                console.error('Sunucu hatası: ' + xhr.statusText);
                                target.textContent = target.getAttribute('data-old-value');
                            }
                        };
                        xhr.onerror = function() {
                            console.error('İstek hatası');
                            target.textContent = target.getAttribute('data-old-value');
                        };
                        xhr.send('id=' + id + '&column=' + column + '&value=' + encodeURIComponent(value));
                    }
                });

                // Düzenleme başladığında eski değeri sakla
                taskTable.addEventListener('focusin', function(event) {
                    var target = event.target;
                    if (target.hasAttribute('contenteditable')) {
                        target.setAttribute('data-old-value', target.textContent.trim());
                    }
                });
            });
        </script>
    <!-- Lightbox HTML -->
    <div class="lightbox" onclick="closeLightbox()">
        <img id="lightbox-img" src="" alt="Lightbox Resmi">
        <span class="close-btn" onclick="closeLightbox()">&times;</span>
    </div>
    
</body>
</html>