<?php
// Always start the session at the very beginning
session_start();

// --- CONFIGURATION ---
$APP_NAME = "Krinry Host";
$UPLOAD_DIR = 'uploads/';
$TELEGRAM_ID = 'krinry123';
$SECRET_CODE = 'terabaap'; // This is your secret admin code

// --- FUNCTIONS ---
function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    else if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    else return round($bytes / 1048576, 2) . ' MB';
}
function getFileIcon($extension) {
    $icons = [
        'html' => ['fab fa-html5', '#E44D26'], 'css' => ['fab fa-css3-alt', '#1572B6'],
        'js' => ['fab fa-js-square', '#F7DF1E'], 'php' => ['fab fa-php', '#777BB4'],
        'png' => ['fas fa-image', '#34A853'], 'jpg' => ['fas fa-image', '#34A853'],
        'jpeg' => ['fas fa-image', '#34A853'], 'gif' => ['fas fa-file-image', '#34A853'],
        'pdf' => ['fas fa-file-pdf', '#DB4437'], 'zip' => ['fas fa-file-archive', '#F4B400'],
        'txt' => ['fas fa-file-alt', '#5A5A5A'],
    ];
    return $icons[$extension] ?? ['fas fa-file-alt', '#6c757d'];
}

// --- AUTHENTICATION & SECURITY ---
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

if (isset($_GET['auth']) && $_GET['auth'] === $SECRET_CODE) {
    $_SESSION['is_admin'] = true;
    header("Location: index.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- ACTIONS ---
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0777, true);
}

// Handle File Deletion
if (isset($_GET['delete'])) {
    $file_to_delete = basename($_GET['delete']);
    $file_path = $UPLOAD_DIR . $file_to_delete;
    
    $can_delete = false;
    if ($is_admin) {
        $can_delete = true;
    } elseif (isset($_SESSION['my_files']) && in_array($file_to_delete, $_SESSION['my_files'])) {
        $can_delete = true;
        $_SESSION['my_files'] = array_diff($_SESSION['my_files'], [$file_to_delete]);
    }

    if ($can_delete && file_exists($file_path)) {
        unlink($file_path);
        $_SESSION['message'] = ['text' => "File '{$file_to_delete}' deleted successfully.", 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => "You do not have permission to delete this file.", 'type' => 'danger'];
    }
    header("Location: index.php");
    exit;
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // <<< --- START: DUPLICATE FILE NAME FIX --- >>>
    $original_filename = basename($file['name']);
    $filepath = pathinfo($original_filename);
    $filename_part = $filepath['filename'];
    // Handle files that might not have an extension
    $extension_part = isset($filepath['extension']) ? '.' . $filepath['extension'] : '';

    $final_filename = $original_filename;
    $destination = $UPLOAD_DIR . $final_filename;
    $counter = 1;

    // Keep checking for a unique name if the file already exists
    while (file_exists($destination)) {
        $final_filename = $filename_part . '(' . $counter . ')' . $extension_part;
        $destination = $UPLOAD_DIR . $final_filename;
        $counter++;
    }
    // <<< --- END: DUPLICATE FILE NAME FIX --- >>>

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!isset($_SESSION['my_files'])) {
            $_SESSION['my_files'] = [];
        }
        // Store the final (potentially renamed) filename in the session
        $_SESSION['my_files'][] = $final_filename;
        $_SESSION['message'] = ['text' => "File '{$final_filename}' uploaded successfully!", 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'An error occurred during upload.', 'type' => 'danger'];
    }
    header("Location: index.php");
    exit;
}

// --- DATA RETRIEVAL ---
$files_to_show = [];
if ($is_admin) {
    $files_to_show = array_diff(scandir($UPLOAD_DIR), array('.', '..'));
} elseif (isset($_SESSION['my_files'])) {
    $files_to_show = $_SESSION['my_files'];
}

// --- MESSAGE HANDLING ---
$message = '';
if (isset($_SESSION['message'])) {
    $message = "<div class='alert alert-{$_SESSION['message']['type']} alert-dismissible fade show' role='alert'>{$_SESSION['message']['text']}<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $APP_NAME; ?> - Secure File Hosting</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Inline Styles -->
    <style>
        :root { --primary-color: #6a11cb; --secondary-color: #2575fc; --dark-color: #1a1a2e; --light-color: #f4f7f6; }
        body { background-color: var(--light-color); font-family: 'Poppins', sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        .navbar { transition: background-color 0.3s ease; }
        .navbar-brand { font-weight: 700; color: var(--primary-color) !important; }
        .hero-section { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 4rem 0; border-radius: 0 0 30px 30px; box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .card-header { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; font-weight: 600; border-radius: 15px 15px 0 0 !important; }
        .file-item { border-left: 5px solid var(--primary-color); transition: all 0.3s ease; }
        .file-item:hover { background-color: #f8f9fa; transform: translateX(5px); }
        .dropzone { border: 2px dashed #ccc; border-radius: 10px; padding: 2.5rem; text-align: center; cursor: pointer; transition: all 0.3s ease; background-color: #fafbff; }
        .dropzone:hover, .dropzone.dragover { background-color: #eef2ff; border-color: var(--primary-color); }
        footer { background-color: var(--dark-color); color: white; padding: 2rem 0; margin-top: auto; }
        .floating-tg-icon { position: fixed; bottom: 20px; right: 20px; background-color: #0088cc; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.3s ease; z-index: 1000; }
        .floating-tg-icon:hover { transform: scale(1.1); color: white; }
        .btn-action { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; }
    </style>
</head>
<body>
    <!-- Floating Telegram Icon -->
    <a href="https://t.me/<?php echo $TELEGRAM_ID; ?>" target="_blank" class="floating-tg-icon" title="Contact on Telegram">
        <i class="fab fa-telegram-plane"></i>
    </a>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand animate__animated animate__fadeIn" href="index.php">
                <i class="fas fa-shield-halved me-2"></i><?php echo $APP_NAME; ?>
            </a>
            <?php if ($is_admin): ?>
            <div class="ms-auto">
                <span class="badge bg-success me-3"><i class="fas fa-user-shield me-1"></i> Admin Mode</span>
                <a href="?logout=true" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section animate__animated animate__fadeIn">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Secure & Smart Hosting</h1>
            <p class="lead">Your files are private and automatically renamed to prevent duplicates.</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Upload Card -->
        <div class="card mb-5 animate__animated animate__fadeInUp">
            <div class="card-header"><h3 class="mb-0"><i class="fas fa-upload me-2"></i>Upload New File</h3></div>
            <div class="card-body p-4">
                <?php echo $message; ?>
                <div class="dropzone mb-4" id="dropzone">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                    <h4>Drag & Drop Files Here</h4>
                    <p class="text-muted mb-0">or click to browse</p>
                </div>
                <form method="POST" enctype="multipart/form-data" id="uploadForm" action="index.php">
                    <input type="file" name="file" id="fileInput" class="d-none" required>
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="fileInfo" class="text-muted">No file selected</div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4" id="uploadBtn">
                            <i class="fas fa-paper-plane me-2"></i><span id="btnText">Upload</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- File List -->
        <div class="card animate__animated animate__fadeInUp animate__delay-1s">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-folder-open me-2"></i>
                    <?php echo $is_admin ? 'All Uploaded Files' : 'Your Uploaded Files'; ?>
                </h3>
            </div>
            <div class="card-body p-4">
                <?php if (empty($files_to_show)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h4><?php echo $is_admin ? 'The storage is empty.' : 'You have not uploaded any files yet.'; ?></h4>
                        <p class="text-muted">Use the form above to upload your first file.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush" id="fileList">
                        <?php foreach (array_reverse($files_to_show) as $file): // array_reverse to show newest first
                            if (!file_exists($UPLOAD_DIR . $file)) continue;
                            $file_path = $UPLOAD_DIR . $file;
                            $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            list($icon_class, $icon_color) = getFileIcon($file_ext);
                        ?>
                        <li class="file-item list-group-item d-flex align-items-center p-3 mb-2">
                            <i class="<?php echo $icon_class; ?> fa-2x me-3" style="color: <?php echo $icon_color; ?>;"></i>
                            <div class="flex-grow-1">
                                <a href="<?php echo $file_path; ?>" target="_blank" class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($file); ?></a>
                                <div class="text-muted small"><?php echo formatFileSize(filesize($file_path)); ?></div>
                            </div>
                            <div class="ms-auto">
                                <a href="<?php echo $file_path; ?>" class="btn btn-outline-success btn-action" download title="Download"><i class="fas fa-download"></i></a>
                                <a href="?delete=<?php echo urlencode($file); ?>" class="btn btn-outline-danger btn-action" onclick="return confirm('Are you sure you want to delete this file?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="animate__animated animate__fadeIn">
        <div class="container text-center">
            <h4><i class="fas fa-shield-halved me-2"></i><?php echo $APP_NAME; ?></h4>
            <p class="mb-0">&copy; <?php echo date("Y"); ?>. Privacy First Hosting.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        dropzone.addEventListener('click', () => fileInput.click());
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropzone.addEventListener(e, p => { p.preventDefault(); p.stopPropagation(); }));
        ['dragenter', 'dragover'].forEach(e => dropzone.addEventListener(e, () => dropzone.classList.add('dragover')));
        ['dragleave', 'drop'].forEach(e => dropzone.addEventListener(e, () => dropzone.classList.remove('dragover')));
        dropzone.addEventListener('drop', e => {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo(fileInput.files[0]);
        });
        fileInput.addEventListener('change', () => fileInput.files.length && updateFileInfo(fileInput.files[0]));
        function updateFileInfo(file) {
            document.getElementById('fileInfo').innerHTML = `<strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        }
        document.getElementById('uploadForm').addEventListener('submit', function() {
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('btnText').innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...`;
        });
    </script>
</body>
</html>
