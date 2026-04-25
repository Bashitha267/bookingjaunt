<?php
require_once '../../config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all properties for this user
$stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ?");
$stmt->execute([$user_id]);
$properties = $stmt->fetchAll();

// For the sidebar to show at least one property name/logo if available
$property = $properties[0] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #febb02;
            color: white;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #003580; border-radius: 10px; }
    </style>
</head>

<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
        
        <!-- Top Nav -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-8 py-4 flex justify-between items-center">
            <div class="flex-1">
                <h1 class="text-xl font-black text-[#003580]">My Properties</h1>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="../../property_wizard.php" class="bg-[#003580] text-white px-6 py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">
                    <i class="fas fa-plus"></i>
                    Add Property
                </a>
            </div>
        </header>

        <div class="p-8">
            <div class="mb-8">
                <p class="text-sm text-gray-400 font-medium">Manage and update your registered properties</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                <?php if (empty($properties)): ?>
                    <div class="col-span-full bg-white p-12 rounded-[2.5rem] border border-dashed border-gray-200 text-center">
                        <i class="fas fa-hotel text-4xl text-gray-200 mb-4"></i>
                        <h3 class="text-lg font-bold text-gray-400">No properties found</h3>
                        <p class="text-sm text-gray-400 mb-6">Start by adding your first property to the platform.</p>
                        <a href="../../property_wizard.php" class="inline-block bg-[#003580] text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest">Add Property</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($properties as $prop): ?>
                        <div class="bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-xl shadow-blue-900/5 group">
                            <div class="h-56 relative overflow-hidden">
                                <img src="../../<?php echo $prop['cover_image'] ?: 'assets/placeholder.png'; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-6">
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <span class="bg-[#febb02] text-[#003580] px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest mb-2 inline-block"><?php echo str_replace('_', ' ', $prop['business_type']); ?></span>
                                            <h3 class="text-white text-xl font-black"><?php echo htmlspecialchars($prop['property_name']); ?></h3>
                                            <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($prop['city']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-8">
                                <div class="grid grid-cols-2 gap-6 mb-8">
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Status</p>
                                        <p class="text-xs font-black text-green-600 uppercase">Live & Active</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">City</p>
                                        <p class="text-xs font-black text-[#003580] uppercase"><?php echo htmlspecialchars($prop['city']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <a href="../../property_wizard.php?edit=<?php echo $prop['id']; ?>" class="flex-1 bg-[#003580] text-white py-4 rounded-2xl font-bold text-xs uppercase tracking-widest text-center hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">
                                        <i class="fas fa-edit mr-2"></i> Edit
                                    </a>
                                    <button onclick="requestDeletion(<?php echo $prop['id']; ?>)" class="px-6 border-2 border-red-50 text-red-400 rounded-2xl hover:bg-red-500 hover:text-white hover:border-red-500 transition-all">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        async function requestDeletion(id) {
            if (confirm('Are you sure you want to request deletion of this property? This requires admin approval and will take effect once reviewed.')) {
                const formData = new FormData();
                formData.append('action', 'request_delete');
                formData.append('property_id', id);

                try {
                    const response = await fetch('../../register.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Deletion request sent to admin successfully.');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while submitting the request.');
                }
            }
        }
    </script>

</body>

</html>
