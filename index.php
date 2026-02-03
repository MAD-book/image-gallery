<?php
// โฟลเดอร์เก็บรูปภาพ
$upload_dir = 'uploads/';

// สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
    
$message = '';
$image_path = '';

// ดึงรายการรูปภาพทั้งหมด
$all_images = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $upload_dir . $file;
            if (is_file($file_path)) {
                $all_images[] = [
                    'name' => $file,
                    'path' => $file_path,
                    'time' => filemtime($file_path)
                ];
            }
        }
    }
    // จัดเรียงตามเวลา (ล่าสุดสุด)
    usort($all_images, function($a, $b) {
        return $b['time'] - $a['time'];
    });
}

// ตรวจสอบการอัปโหลดไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file_tmp);
    
    // ตรวจสอบข้อผิดพลาด
    if ($file_error !== UPLOAD_ERR_OK) {
        $message = '❌ เกิดข้อผิดพลาดในการอัปโหลด';
    } elseif ($file_size > 5000000) { // 5MB
        $message = '❌ ไฟล์ใหญ่เกินไป (ขีดจำกัด 5MB)';
    } elseif (!in_array($file_type, $allowed_types)) {
        $message = '❌ ประเภทไฟล์ไม่รองรับ (JPG, PNG, GIF, WebP เท่านั้น)';
    } else {
        // สร้างชื่อไฟล์ใหม่
        $new_file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file_name));
        $file_path = $upload_dir . $new_file_name;
        
        // อัปโหลดไฟล์
        if (move_uploaded_file($file_tmp, $file_path)) {
            $message = '✓ อัปโหลดสำเร็จ!';
            $image_path = $file_path;
            // รีเฟรชหน้า
            header("Refresh:1");
        } else {
            $message = '❌ ไม่สามารถบันทึกไฟล์ได้';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file'])) {
    // ตรวจสอบการลบไฟล์
    $delete_path = $_POST['delete_file'];
    if (file_exists($delete_path) && strpos(realpath($delete_path), realpath($upload_dir)) === 0) {
        if (unlink($delete_path)) {
            $message = '✓ ลบรูปภาพสำเร็จ!';
            // รีเฟรชหน้า
            header("Refresh:1");
        } else {
            $message = '❌ ไม่สามารถลบไฟล์ได้';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดรูปภาพ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f1ff;
        }
        
        .upload-area.dragover {
            border-color: #764ba2;
            background: #e8e9ff;
        }
        
        .upload-area p {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: auto;
            display: inline-block;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .upload-btn:active {
            transform: translateY(0);
        }
        
        .form-group .upload-btn[type="submit"] {
            width: 100%;
            display: block;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .image-preview {
            margin-top: 30px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            margin-top: 15px;
        }
        
        .file-info {
            color: #999;
            font-size: 12px;
            margin-top: 10px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .gallery-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #eee;
            width: calc(100% + 80px);
            margin-left: -40px;
            margin-right: -40px;
            padding-left: 40px;
            padding-right: 40px;
        }
        
        .gallery-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .gallery-grid {
            column-count: 6;
            gap: 15px;
            column-gap: 15px;
            width: 100%;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            break-inside: avoid;
            margin-bottom: 15px;
            display: inline-block;
            width: 100%;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .gallery-item img {
            width: 100%;
            height: auto;
            object-fit: cover;
            display: block;
            max-width: 100%;
        }
        
        @media (max-width: 1400px) {
            .gallery-grid {
                column-count: 5;
            }
        }
        
        @media (max-width: 1100px) {
            .gallery-grid {
                column-count: 4;
            }
        }
        
        @media (max-width: 768px) {
            .gallery-grid {
                column-count: 3;
            }
        }
        
        @media (max-width: 480px) {
            .gallery-grid {
                column-count: 1;
            }
        }
        
        .gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .empty-message {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-size: 16px;
        }
        
        .preview-section {
            margin-top: 30px;
            text-align: center;
            display: none;
        }
        
        .preview-section.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        .preview-section p {
            color: #666;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
        }
        
        .file-details {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .file-details p {
            margin: 5px 0;
            font-weight: normal;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 10px;
            position: relative;
        }
        
        .modal-content img {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px 12px;
            border-radius: 5px;
            transition: background 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 อัปโหลดรูปภาพ</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, '✓') !== false) ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="image">เลือกรูปภาพ</label>
                <div class="upload-area" id="uploadArea">
                    <p>📁 ลากไฟล์มาที่นี่ หรือ</p>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <button type="button" class="upload-btn" onclick="document.getElementById('image').click()">
                        คลิกเพื่อเลือกไฟล์
                    </button>
                    <p class="file-info">ไฟล์ที่รองรับ: JPG, PNG, GIF, WebP (สูงสุด 5MB)</p>
                </div>
            </div>
            
            <!-- Preview Section -->
            <div class="preview-section" id="previewSection">
                <p>👁️ Preview รูปภาพ</p>
                <img id="previewImage" class="preview-image" alt="Preview">
                <div class="file-details" id="fileDetails">
                    <p id="fileName">ชื่อไฟล์: -</p>
                    <p id="fileSize">ขนาด: -</p>
                    <p id="fileDimensions">ขนาด: -</p>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="upload-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    ยืนยันการอัปโหลด
                </button>
            </div>
        </form>
        
        <?php if ($image_path): ?>
            <div class="image-preview">
                <p style="color: #666; font-weight: 600;">รูปภาพที่อัปโหลด:</p>
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Uploaded Image">
            </div>
        <?php endif; ?>
        
        <!-- Gallery Section -->
        <?php if (!empty($all_images)): ?>
            <div class="gallery-section">
                <h2>🖼️ ภาพที่อัปโหลดทั้งหมด (<?php echo count($all_images); ?> รูป)</h2>
                <div class="gallery-grid">
                    <?php foreach ($all_images as $img): ?>
                        <div class="gallery-item" onclick="openModal('<?php echo htmlspecialchars($img['path']); ?>')">
                            <img src="<?php echo htmlspecialchars($img['path']); ?>" alt="<?php echo htmlspecialchars($img['name']); ?>">
                            <div class="gallery-item-overlay">
                                <form method="POST" style="display: flex; gap: 10px;" onclick="event.stopPropagation();">
                                    <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($img['path']); ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('ต้องการลบรูปนี้?')">🗑️ ลบ</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="gallery-section">
                <div class="empty-message">
                    📭 ยังไม่มีรูปภาพ อัปโหลดรูปภาพเพื่อเริ่มต้น
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Modal -->
        <div class="modal" id="imageModal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <img id="modalImage" src="" alt="">
            </div>
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('image');
        
        // ป้องกัน default behavior
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('dragover');
            }, false);
        });
        
        // Handle dropped files
        uploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            imageInput.files = files;
        }, false);
        
        // Show selected file name
        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024).toFixed(2); // KB
                
                // แสดง preview
                const reader = new FileReader();
                reader.onload = (event) => {
                    const previewImage = document.getElementById('previewImage');
                    previewImage.src = event.target.result;
                    
                    // ดึงขนาดของรูป
                    const img = new Image();
                    img.onload = () => {
                        document.getElementById('fileDimensions').textContent = 
                            `ขนาด: ${img.width} x ${img.height} px`;
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
                
                // อัปเดตข้อมูลไฟล์
                document.getElementById('fileName').textContent = `ชื่อไฟล์: ${fileName}`;
                document.getElementById('fileSize').textContent = `ขนาด: ${fileSize} KB`;
                
                // แสดง preview section
                document.getElementById('previewSection').classList.add('show');
            }
        });
        
        // Modal functions
        function openModal(imagePath) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = imagePath;
            modal.classList.add('show');
        }
        
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
        }
        
        // ปิด modal เมื่อคลิกที่พื้นหลัง
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
