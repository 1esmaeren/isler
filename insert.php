<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// Upload klasörünü kontrol et ve oluştur
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

$uploadOk = 1;
$target_file = '';

// Dosya yükleme işlemi

if (!empty($_FILES["fileToUpload"]["name"])) {
    $target_dir = "uploads/";
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . uniqid() . '.' . $imageFileType; // Benzersiz dosya adı oluşturma

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        echo "Dosya bir resim değil.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Üzgünüz, dosyanız çok büyük.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    $allowed_formats = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_formats)) {
        echo "Üzgünüz, sadece JPG, JPEG, PNG ve GIF dosyalarına izin veriyoruz.";
        $uploadOk = 0;
    }

    // Dosya yükleme işlemi gerçekleştir
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "Dosya başarıyla yüklendi.";
        } else {
            echo "Dosya yüklenirken bir hata oluştu.";
            $uploadOk = 0;
        }
    }
}

// Veritabanına kayıt ekleme
if ($uploadOk == 1) {
    $sql = "INSERT INTO isler_tb (tarih, sube, kisi, iletisim, konu, durum, resim) VALUES (:tarih, :sube, :kisi, :iletisim, :konu, :durum, :resim)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tarih' => $_POST['tarih'],
        ':sube' => $_POST['sube'],
        ':kisi' => $_POST['kisi'],
        ':iletisim' => $_POST['iletisim'],
        ':konu' => $_POST['konu'],
        ':durum' => $_POST['durum'],
        ':resim' => $target_file  // Eğer dosya yüklenmediyse boş bir değer atanacak
    ]);

    header('Location: index.php');
    exit();
} else {
    echo "Kayıt eklenemedi, dosya yüklenmedi veya hatalı dosya türü.";
}
?>
