<?php
$current_page = basename($_SERVER['PHP_SELF']);
$view = $_GET['view'] ?? 'dashboard';

// --- Handle background image upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_bg') {
    $upload_dir = '../../uploads/dashboard_bg/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uid = $_SESSION['user_id'];

    if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'bg_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $upload_dir . $filename)) {
                $path = 'uploads/dashboard_bg/' . $filename;
                $upd = $pdo->prepare("UPDATE users SET dashboard_bg = ? WHERE id = ?");
                $upd->execute([$path, $uid]);
                $_SESSION['dashboard_bg'] = $path;
            }
        }
    } elseif (isset($_POST['reset_bg'])) {
        $upd = $pdo->prepare("UPDATE users SET dashboard_bg = NULL WHERE id = ?");
        $upd->execute([$_SESSION['user_id']]);
        $_SESSION['dashboard_bg'] = null;
    }

    // Redirect back to the same page
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header("Location: $redirect");
    exit();
}

// --- Fetch current user background ---
$bg_path = null;
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['dashboard_bg_loaded'])) {
        $bg_stmt = $pdo->prepare("SELECT dashboard_bg FROM users WHERE id = ?");
        $bg_stmt->execute([$_SESSION['user_id']]);
        $bg_row = $bg_stmt->fetch();
        $bg_path = $bg_row['dashboard_bg'] ?? null;
        $_SESSION['dashboard_bg'] = $bg_path;
        $_SESSION['dashboard_bg_loaded'] = true;
    } else {
        $bg_path = $_SESSION['dashboard_bg'] ?? null;
    }
}

$bg_url = $bg_path ? '../../' . htmlspecialchars($bg_path) : null;
?>

<!-- ===== GLOBAL GLASS BACKGROUND STYLES ===== -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    :root {
        --accent: #febb02;
        --accent-blue: #006ce4;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
    }

    body {
        <?php if ($bg_url): ?>
        background: linear-gradient(rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.55)), url('<?php echo $bg_url; ?>') no-repeat center center fixed !important;
        <?php else: ?>
        background: linear-gradient(rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.55)), linear-gradient(135deg, #020c24 0%, #041848 35%, #0a2a6e 65%, #0d3b8a 100%) !important;
        <?php endif; ?>
        background-size: cover !important;
        background-attachment: fixed !important;
        color: #f8fafc !important;
    }

    /* ---- Main Layout ---- */
    .main-content {
        margin-left: 256px;
        min-height: 100vh;
        overflow-y: auto;
        height: 100vh;
        flex: 1;
        width: calc(100% - 256px);
    }

    @media (max-width: 1023px) {
        .main-content { margin-left: 0; width: 100%; }
    }

    /* Glassmorphism for Headers */
    header.glass-header {
        background: rgba(15, 23, 42, 0.3) !important;
        backdrop-filter: blur(2px) saturate(120%) !important;
        -webkit-backdrop-filter: blur(2px) saturate(120%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    header.glass-header h1, header.glass-header p, header.glass-header span, header.glass-header i {
        color: #ffffff !important;
    }

    /* Sidebar Glassmorphism */
    .glass-sidebar {
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(2px) !important;
        -webkit-backdrop-filter: blur(2px) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    /* Sidebar Link Active */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7) !important;
        transition: all 0.2s;
        border-left: 4px solid transparent;
        text-decoration: none;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.10);
        color: white !important;
    }

    .sidebar-link.active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 4px solid #febb02;
        color: white !important;
        border-radius: 0 12px 12px 0;
        margin-left: -12px;
        padding-left: 28px;
    }

    .sidebar-link i {
        width: 20px;
        text-align: center;
        color: #60a5fa;
        transition: color 0.2s;
    }

    .sidebar-link:hover i, .sidebar-link.active i {
        color: #febb02;
    }

    /* Glassmorphism for Cards */
    .glass-card, .glass-table {
        background: rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(2px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(2px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.25) !important;
        border-radius: 1.5rem !important;
        color: #f8fafc !important;
    }

    /* Specific overrides for hotel elements inside cards */
    .stat-label {
        color: rgba(255, 255, 255, 0.95) !important;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 4px;
    }

    .stat-value {
        color: white !important;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: rgba(255, 255, 255, 0.15) !important;
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    /* Tables Styling */
    .glass-table {
        overflow: hidden;
    }
    
    .glass-table table {
        background: transparent !important;
    }

    .glass-th {
        padding: 12px 20px;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        background: rgba(255, 255, 255, 0.05) !important;
        color: rgba(255, 255, 255, 0.85) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .glass-td {
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        color: #ffffff !important;
        font-size: 0.875rem;
    }

    .glass-tr:last-child .glass-td { border-bottom: none !important; }

    .glass-tr:hover {
        background: rgba(255, 255, 255, 0.04) !important;
    }
    
    /* Ensure text readability */
    .glass-card h2, .glass-card h3, .glass-card p, .glass-card label {
        color: #ffffff !important;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.5625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: rgba(255, 255, 255, 0.15) !important;
        backdrop-filter: blur(1px) !important;
        border: none !important;
    }

    .badge-confirmed { color: #a7f3d0 !important; background: rgba(16, 185, 129, 0.25) !important; }
    .badge-pending { color: #fde047 !important; background: rgba(245, 158, 11, 0.25) !important; }
    .badge-checked_in { color: #93c5fd !important; background: rgba(59, 130, 246, 0.25) !important; }
    .badge-checked_out { color: #ddd6fe !important; background: rgba(139, 92, 246, 0.25) !important; }
    .badge-cancelled { color: #fca5a5 !important; background: rgba(239, 68, 68, 0.25) !important; }

    /* Form Fields and Inputs */
    .glass-input,
    main input[type="text"], 
    main input[type="file"],
    main input[type="number"],
    main input[type="email"],
    main input[type="password"],
    main textarea, 
    main select {
        background: rgba(255, 255, 255, 0.07) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        backdrop-filter: blur(1px) !important;
        border-radius: 0.75rem !important;
    }

    .glass-input::placeholder, main input::placeholder, main textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .glass-input:focus, main input:focus, main textarea:focus, main select:focus {
        background: rgba(255, 255, 255, 0.14) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1) !important;
        outline: none !important;
    }

    /* Buttons */
    .btn-glass {
        background: rgba(255, 255, 255, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        padding: 8px 20px;
        border-radius: 0.75rem;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-glass:hover {
        background: rgba(255, 255, 255, 0.22) !important;
        transform: translateY(-1px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #006ce4, #0047b3);
        color: white;
        padding: 8px 20px;
        border-radius: 0.75rem;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0,108,228,0.35);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #0080ff, #005ad6);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0,108,228,0.5);
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2) !important; border-radius: 10px; }

    /* FullCalendar overrides */
    .fc {
        background: rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(2px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(2px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        padding: 20px;
        border-radius: 1.5rem;
    }
    .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 800; color: white !important; }
    .fc .fc-button-primary { background: rgba(255,255,255,0.12) !important; border-color: rgba(255,255,255,0.2) !important; color: white !important; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .fc .fc-button-primary:hover { background: rgba(255,255,255,0.22) !important; }
    .fc .fc-button-active { background: var(--accent-blue) !important; border-color: var(--accent-blue) !important; }
    .fc th { padding: 10px 0; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.7) !important; letter-spacing: 0.1em; }
    .fc td { font-size: 0.8rem; color: rgba(255,255,255,0.9) !important; }
    .fc-daygrid-day { background: transparent; }
    .fc-day-today { background: rgba(255,255,255,0.1) !important; }

    /* BG Upload Btn Panel */
    #bgPanel {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease, opacity 0.3s;
        opacity: 0;
    }
    #bgPanel.open {
        max-height: 300px;
        opacity: 1;
    }

    #sidebar.show { transform: translateX(0) !important; }
    select.glass-input option { background: #0f172a; color: white; }

    /* Animations */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(15px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .anim-up { animation: fadeSlideUp 0.5s ease forwards; }
    .anim-up-2 { animation: fadeSlideUp 0.5s 0.08s ease forwards; opacity: 0; }
    .anim-up-3 { animation: fadeSlideUp 0.5s 0.16s ease forwards; opacity: 0; }
</style>

<!-- ===== SIDEBAR ===== -->
<aside id="sidebar" class="glass-sidebar w-64 flex flex-col fixed h-full z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0" style="top:0; left:0;">
    
    <!-- Logo -->
    <div class="p-6 flex justify-between items-center lg:block">
        <a href="../../index.php" class="flex items-center gap-2 no-underline">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg,#006ce4,#003580);">
                <i class="fas fa-hotel text-white text-sm"></i>
            </div>
            <span style="color:white; font-size:1.25rem; font-weight:900; letter-spacing:-0.03em;">
                Booking<span style="color:var(--accent);">Jaunt</span>
            </span>
        </a>
        <button onclick="toggleSidebar()" class="lg:hidden" style="color:rgba(255,255,255,0.5); background:none; border:none; cursor:pointer;">
            <i class="fas fa-times text-xl"></i>
        </button>
        <p class="text-[10px] font-bold mt-1 uppercase tracking-widest opacity-40 lg:block hidden" style="color:#93c5fd;">Manager Console</p>
    </div>

    <!-- Nav -->
    <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <a href="dashboard.php?view=dashboard" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $view === 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="bookings.php" class="sidebar-link <?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Bookings
        </a>
        <a href="reports.php" class="sidebar-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Reports
        </a>
        <a href="monthly_report.php" class="sidebar-link <?php echo ($current_page == 'monthly_report.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Monthly Reports
        </a>
        <a href="feedbacks.php" class="sidebar-link <?php echo ($current_page == 'feedbacks.php') ? 'active' : ''; ?>">
            <i class="fas fa-comment-dots"></i> Feedbacks
        </a>
        <a href="payments.php" class="sidebar-link <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i> Pay to System
        </a>
        <a href="my_properties.php" class="sidebar-link <?php echo ($current_page == 'my_properties.php') ? 'active' : ''; ?>">
            <i class="fas fa-hotel"></i> My Properties
        </a>
        <a href="my_vehicles.php" class="sidebar-link <?php echo ($current_page == 'my_vehicles.php') ? 'active' : ''; ?>">
            <i class="fas fa-car"></i> My Vehicles
        </a>
        <a href="vehicle_bookings.php" class="sidebar-link <?php echo ($current_page == 'vehicle_bookings.php') ? 'active' : ''; ?>">
            <i class="fas fa-car-side"></i> Vehicle Bookings
        </a>

        <!-- Account Section -->
        <div class="pt-8 pb-3">
            <p class="px-4 text-[10px] font-bold uppercase tracking-widest opacity-40" style="color:#93c5fd;">Account</p>
        </div>

        <!-- Background Image Upload Toggle -->
        <button onclick="toggleBgPanel()" class="sidebar-link w-full text-left" style="background:none;border:none;cursor:pointer;">
            <i class="fas fa-image"></i>
            <span>Customize Background</span>
            <i class="fas fa-chevron-down ml-auto text-xs transition-transform" id="bgChevron"></i>
        </button>

        <!-- BG Panel -->
        <div id="bgPanel" class="px-2">
            <div class="rounded-xl p-3 mt-1" style="background:rgba(0,0,0,0.25);border:1px solid var(--glass-border);">
                <form action="<?php echo basename($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_bg">
                    <?php if (isset($_GET['property_id'])): ?>
                    <input type="hidden" name="property_id" value="<?php echo (int)$_GET['property_id']; ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['view'])): ?>
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
                    <?php endif; ?>

                    <label class="block text-[9px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-muted);">Upload Background Image</label>
                    
                    <!-- Preview -->
                    <?php if ($bg_path): ?>
                    <div class="mb-2 rounded-lg overflow-hidden" style="height:60px;">
                        <img src="../../<?php echo htmlspecialchars($bg_path); ?>" class="w-full h-full object-cover opacity-80">
                    </div>
                    <?php endif; ?>

                    <input type="file" name="bg_image" accept="image/jpeg,image/png,image/webp"
                        class="w-full text-[10px] mb-2 cursor-pointer"
                        style="color:var(--text-secondary);">
                    
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-2 rounded-lg text-[9px] font-bold uppercase tracking-widest text-white" style="background:var(--accent-blue);">
                            <i class="fas fa-upload mr-1"></i> Apply
                        </button>
                        <?php if ($bg_path): ?>
                        <button type="submit" name="reset_bg" value="1" class="px-3 py-2 rounded-lg text-[9px] font-bold uppercase text-white" style="background:rgba(239,68,68,0.5);">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-[8px] mt-2" style="color:var(--text-muted);">JPG, PNG or WEBP. Used as your dashboard wallpaper.</p>
                </form>
            </div>
        </div>

        <a href="../../logout.php" class="sidebar-link" style="color:rgba(248,113,113,0.7);">
            <i class="fas fa-sign-out-alt" style="color:rgba(248,113,113,0.7);"></i> Logout
        </a>
    </nav>

    <!-- Property Info Card -->
    <div class="p-4 m-4 rounded-xl lg:block hidden" style="background:rgba(0,0,0,0.3);border:1px solid var(--glass-border);">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white flex-shrink-0 overflow-hidden" style="background:linear-gradient(135deg,#006ce4,#003580);">
                <?php if (isset($property['logo_image']) && $property['logo_image']): ?>
                    <img src="../../<?php echo $property['logo_image']; ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($property['property_name'] ?? 'P', 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold truncate" style="color:white;"><?php echo htmlspecialchars($property['property_name'] ?? 'My Property'); ?></p>
                <p class="text-[9px] font-bold uppercase tracking-wider" style="color:#93c5fd;">Owner</p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Open Button -->
<button id="sidebarOpenBtn" onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] w-11 h-11 rounded-xl flex items-center justify-center shadow-lg"
    style="background:rgba(5,18,60,0.75);backdrop-filter:blur(12px);border:1px solid var(--glass-border);color:white;">
    <i class="fas fa-bars text-lg"></i>
</button>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 z-40 hidden lg:hidden" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('sidebarOpenBtn');
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('show');
            overlay.classList.remove('hidden');
            if (openBtn) openBtn.classList.add('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('show');
            overlay.classList.add('hidden');
            if (openBtn) openBtn.classList.remove('hidden');
            document.body.style.overflow = '';
        }
    }

    function toggleBgPanel() {
        const panel = document.getElementById('bgPanel');
        const chevron = document.getElementById('bgChevron');
        panel.classList.toggle('open');
        chevron.style.transform = panel.classList.contains('open') ? 'rotate(180deg)' : '';
    }
</script>


