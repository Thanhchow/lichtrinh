<?php
header('Content-Type: application/json; charset=utf-8');

// Define constants
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('DATA_FILE', __DIR__ . '/data.json');
define('PROGRESS_FILE', __DIR__ . '/progress.json'); // File mới lưu tiến độ
define('ADMIN_PASSWORD', 'admin'); 

// GIỮ NGUYÊN BẢO MẬT: Phân quyền 0755 an toàn hơn 0777
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true); 
}

if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, json_encode([]));
}

if (!file_exists(PROGRESS_FILE)) {
    file_put_contents(PROGRESS_FILE, json_encode([]));
}

function getData() {
    $content = file_get_contents(DATA_FILE);
    return json_decode($content, true) ?: [];
}

function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Function xử lý Tiến độ
function getProgress() {
    $content = file_get_contents(PROGRESS_FILE);
    return json_decode($content, true) ?: [];
}

function saveProgress($data) {
    file_put_contents(PROGRESS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    if ($action === 'get_data') {
        $response = ['success' => true, 'data' => getData()];
    } 
    elseif ($action === 'get_progress') { // API Lấy tiến độ
        $response = ['success' => true, 'data' => getProgress()];
    }
    elseif ($action === 'update_progress') { // API Lưu tiến độ
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $taskId = $input['taskId'] ?? '';
        $status = isset($input['status']) ? (bool)$input['status'] : false;
        
        if (empty($taskId)) {
            throw new Exception("Mã công việc không hợp lệ.");
        }
        
        $prog = getProgress();
        $prog[$taskId] = $status;
        saveProgress($prog);
        
        $response = ['success' => true, 'message' => 'Đã đồng bộ tiến độ!'];
    }
    elseif ($action === 'upload') {
        $password = $_POST['password'] ?? '';
        if ($password !== ADMIN_PASSWORD) {
            throw new Exception("Sai mật khẩu tải lên.");
        }

        $taskId = $_POST['taskId'] ?? '';
        if (empty($taskId)) {
            throw new Exception("Mã công việc không hợp lệ.");
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Lỗi tải file. Có thể file quá lớn.");
        }

        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowedExts)) {
            throw new Exception("Định dạng file không được hỗ trợ.");
        }

        // GIỮ NGUYÊN BẢO MẬT: Kiểm tra MIME type thực sự của file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!str_starts_with($mime, 'image/')) {
            throw new Exception("File không phải là hình ảnh thực sự.");
        }

        $filename = $taskId . '_' . time() . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $data = getData();
            if (isset($data[$taskId])) {
                $oldFile = __DIR__ . '/' . $data[$taskId];
                if (file_exists($oldFile)) unlink($oldFile);
            }
            $imageUrl = 'uploads/' . $filename;
            $data[$taskId] = $imageUrl;
            saveData($data);
            $response = ['success' => true, 'message' => 'Tải ảnh thành công!', 'imageUrl' => $imageUrl];
        } else {
            throw new Exception("Không thể lưu file trên server.");
        }
    } 
    elseif ($action === 'delete') {
         $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
         $password = $input['password'] ?? '';
         if ($password !== ADMIN_PASSWORD) throw new Exception("Sai mật khẩu xóa.");
 
         $taskId = $input['taskId'] ?? '';
         if (empty($taskId)) throw new Exception("Mã công việc không hợp lệ.");

         $data = getData();
         if (isset($data[$taskId])) {
             $fileToDelete = __DIR__ . '/' . $data[$taskId];
             if (file_exists($fileToDelete)) unlink($fileToDelete);
             unset($data[$taskId]);
             saveData($data);
         }
         $response = ['success' => true, 'message' => 'Xóa ảnh thành công!'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>