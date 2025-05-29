<?php
session_start();
require 'config.php';

// CSRF token üretimi
if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token']) && isset($_SESSION['form_token']) && $_POST['token'] === $_SESSION['form_token']) {
    $lokasyon_adi = $_POST['lokasyon_adi'] ?? '';
    $ci_name = $_POST['ci_name'] ?? '';
    $adres = $_POST['adres'] ?? '';
    $local_ip = $_POST['local_ip'] ?? '';
    $durum = $_POST['durum'] ?? 'Kapalı';
    $not_veya_link = $_POST['not_veya_link'] ?? '';
    $iade = $_POST['iade'] ?? '0'; // İade tutarı eklendi

    // Resim yükleme işlemi
    $resim_yolu = '';
    if (isset($_FILES['resim']) && $_FILES['resim']['error'] === UPLOAD_ERR_OK) {
        $hedef_klasor = 'uploads/';
        if (!file_exists($hedef_klasor)) {
            mkdir($hedef_klasor, 0777, true);
        }

        $dosya_adi = uniqid() . '_' . basename($_FILES['resim']['name']);
        $hedef_yol = $hedef_klasor . $dosya_adi;

        if (move_uploaded_file($_FILES['resim']['tmp_name'], $hedef_yol)) {
            $resim_yolu = $hedef_yol;
        }
    }

    $sql = "INSERT INTO internet_hizmetleri (lokasyon_adi, ci_name, adres, local_ip, durum, not_veya_link, resim, tarih, iade)
            VALUES (:lokasyon_adi, :ci_name, :adres, :local_ip, :durum, :not_veya_link, :resim, :tarih, :iade)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lokasyon_adi' => $lokasyon_adi,
        ':ci_name' => $ci_name,
        ':adres' => $adres,
        ':local_ip' => $local_ip,
        ':durum' => $durum,
        ':not_veya_link' => $not_veya_link,
        ':resim' => $resim_yolu,
        ':tarih' => $_POST['tarih'] ?? date('Y-m-d H:i:s'),
        ':iade' => $iade,
    ]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$total = $pdo->query("SELECT COUNT(*) FROM internet_hizmetleri")->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM internet_hizmetleri ORDER BY tarih DESC LIMIT :start, :limit");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$veriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>İnternet Hizmeti Takibi</title>
    <link rel="stylesheet" href="internet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-wifi"></i> İnternet Hizmet Takip Sistemi</h1>
        </header>

        <section class="form-section">
            <h2><i class="fas fa-plus-circle"></i> Yeni Kayıt Ekle</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="tarih"><i class="far fa-calendar-alt"></i> Tarih</label>
                        <input type="datetime-local" id="tarih" name="tarih" value="<?= date('Y-m-d\TH:i') ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="lokasyon_adi"><i class="fas fa-map-marker-alt"></i> Lokasyon Adı</label>
                        <input type="text" id="lokasyon_adi" name="lokasyon_adi" placeholder="Lokasyon Adı" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ci_name"><i class="fas fa-tag"></i> CI Name</label>
                        <input type="text" id="ci_name" name="ci_name" placeholder="CI Name" />
                    </div>
                    <div class="form-group">
                        <label for="adres"><i class="fas fa-address-card"></i> Adres</label>
                        <input type="text" id="adres" name="adres" placeholder="Adres" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="local_ip"><i class="fas fa-network-wired"></i> Local IP</label>
                        <input type="text" id="local_ip" name="local_ip" placeholder="Local IP Adresi" />
                    </div>
                    <div class="form-group">
                        <label for="durum"><i class="fas fa-power-off"></i> Durum</label>
                        <select id="durum" name="durum">
                            <option value="Açık">Açık</option>
                            <option value="Kapalı">Kapalı</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="not_veya_link"><i class="fas fa-sticky-note"></i> Not / Link</label>
                    <textarea id="not_veya_link" name="not_veya_link" placeholder="Not veya Vodafone Linki"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group iade-tutari">
                        <label for="iade"><i class="fas fa-undo-alt"></i> İade Tutarı</label>
                        <input type="number" step="0.01" id="iade" name="iade" placeholder="₺" />
                    </div>
                    <div class="form-group resim-ekle">
                        <label for="resim"><i class="fas fa-image"></i> Resim Ekle</label>
                        <input type="file" id="resim" name="resim" />
                    </div>
                </div>


                <input type="hidden" name="token" value="<?= $_SESSION['form_token'] ?>" />
                <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Kaydet</button>
            </form>
        </section>

        <section class="table-section">
            <h2><i class="fas fa-list"></i> Mevcut Kayıtlar</h2>
            <input
                type="text"
                id="searchInput"
                placeholder="Tabloda ara..."
                style="margin: 10px 0; padding: 8px; width: 100%; max-width: 300px; border: 1px solid #ccc; border-radius: 4px;"
            />
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-map-marker-alt"></i> Lokasyon</th>
                            <th><i class="fas fa-tag"></i> CI Name</th>
                            <th><i class="fas fa-address-card"></i> Adres</th>
                            <th><i class="fas fa-network-wired"></i> IP</th>
                            <th><i class="fas fa-power-off"></i> Durum</th>
                            <th><i class="fas fa-image"></i> Resim</th>
                            <th><i class="fas fa-sticky-note"></i> Not/Link</th>
                            <th><i class="far fa-calendar-alt"></i> Tarih</th>
                            <th><i class="fas fa-sticky-note"></i> İade Tutarı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($veriler as $veri): ?>
                        <tr class="<?= $veri['durum'] === 'Kapalı' ? 'status-closed' : 'status-open' ?>">
                            <td><?= htmlspecialchars($veri['lokasyon_adi']) ?></td>
                            <td><?= htmlspecialchars($veri['ci_name']) ?></td>
                            <td title="<?= htmlspecialchars($veri['adres']) ?>"><?= htmlspecialchars($veri['adres']) ?></td>
                            <td><?= htmlspecialchars($veri['local_ip']) ?></td>
                            <td><span class="status-badge"><?= htmlspecialchars($veri['durum']) ?></span></td>
                            <td>
                                <?php if (!empty($veri['resim'])): ?>
                                <img src="<?= htmlspecialchars($veri['resim']) ?>" class="thumbnail" onclick="openModal('<?= htmlspecialchars($veri['resim']) ?>')" />
                                <?php else: ?>
                                <span class="resim-yok">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td title="<?= htmlspecialchars($veri['not_veya_link']) ?>">
                                <?php if (filter_var($veri['not_veya_link'], FILTER_VALIDATE_URL)): ?>
                                <a href="<?= htmlspecialchars($veri['not_veya_link']) ?>" class="link" target="_blank"><i class="fas fa-external-link-alt"></i> Link</a>
                                <?php else: ?>
                                <?= htmlspecialchars($veri['not_veya_link']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($veri['tarih'])) ?></td>
                            <td><?= htmlspecialchars($veri['iade']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?page=<?= $p ?>" class="<?= ($p == $page) ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </section>
    </div>

    <div id="modal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <img class="modal-content" id="modal-img" />
    </div>

    <script src="internet.js"></script>
    <script>
        // Resim modal açma kapama
        function openModal(src) {
            const modal = document.getElementById('modal');
            const modalImg = document.getElementById('modal-img');
            modal.style.display = 'block';
            modalImg.src = src;
        }

        function closeModal() {
            const modal = document.getElementById('modal');
            modal.style.display = 'none';
        }

        // Tablo arama fonksiyonu
        document.getElementById('searchInput').addEventListener('input', function () {
            const filtre = this.value.toLowerCase();
            const satirlar = document.querySelectorAll('table tbody tr');
            satirlar.forEach(tr => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = text.includes(filtre) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
