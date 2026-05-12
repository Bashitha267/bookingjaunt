<?php
require_once 'config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    <title>List Your Property - Bookingjaunt Partners</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #003580 0%, #006ce4 100%); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-[#003580] text-white p-6">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold">Bookingjaunt <span class="font-light text-sm opacity-80 italic">for Partners</span></a>
            <div class="flex items-center gap-6 text-sm font-semibold">
                <a href="#" class="hover:underline">Already a partner?</a>
                <a href="login.php" class="bg-white text-[#003580] px-4 py-2 rounded-md hover:bg-gray-100 transition-all">Sign in</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-gradient text-white py-24 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl font-extrabold mb-8 leading-tight">List any type of property on Bookingjaunt</h1>
            <p class="text-xl opacity-90 mb-12">Whether your property is a cozy home, a luxury hotel, or something unique, list it for free and reach millions of travelers.</p>
            <div class="bg-white p-8 rounded-xl shadow-2xl max-w-lg mx-auto text-gray-900">
                <h3 class="text-2xl font-bold mb-6">Start listing today</h3>
                <ul class="text-left space-y-4 mb-8 text-sm font-medium">
                    <li class="flex items-center gap-3"><i class="fas fa-check text-green-500"></i> Reach millions of global travelers</li>
                    <li class="flex items-center gap-3"><i class="fas fa-check text-green-500"></i> No hidden registration fees</li>
                    <li class="flex items-center gap-3"><i class="fas fa-check text-green-500"></i> 24/7 support in multiple languages</li>
                </ul>
                <a href="property_wizard.php" class="block w-full bg-[#006ce4] text-white py-4 rounded-md font-bold text-lg hover:bg-[#0056b3] transition-all">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-20 px-6 max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-16">What can I list?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-4xl mx-auto">
            <!-- Hotel Card -->
            <a href="property_wizard.php?type=hotel" class="bg-white p-12 rounded-3xl shadow-sm border-2 border-transparent hover:border-[#006ce4] hover:shadow-xl transition-all cursor-pointer group flex flex-col items-center text-center">
                <div class="w-24 h-24 bg-blue-50 rounded-2xl flex items-center justify-center mb-8 group-hover:bg-[#006ce4] transition-all">
                    <i class="fas fa-hotel text-4xl text-[#006ce4] group-hover:text-white"></i>
                </div>
                <h4 class="text-3xl font-extrabold mb-4">Hotels</h4>
                <p class="text-gray-500 text-lg">List your hotel, resort, or luxury accommodation and reach millions.</p>
                <div class="mt-8 text-[#006ce4] font-bold flex items-center gap-2 group-hover:gap-4 transition-all">
                    List your hotel <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <!-- Reception Hall Card -->
            <a href="property_wizard.php?type=reception_hall" class="bg-white p-12 rounded-3xl shadow-sm border-2 border-transparent hover:border-[#006ce4] hover:shadow-xl transition-all cursor-pointer group flex flex-col items-center text-center">
                <div class="w-24 h-24 bg-blue-50 rounded-2xl flex items-center justify-center mb-8 group-hover:bg-[#006ce4] transition-all">
                    <i class="fas fa-glass-cheers text-4xl text-[#006ce4] group-hover:text-white"></i>
                </div>
                <h4 class="text-3xl font-extrabold mb-4">Reception Halls</h4>
                <p class="text-gray-500 text-lg">Register your venue for weddings, parties, and corporate events.</p>
                <div class="mt-8 text-[#006ce4] font-bold flex items-center gap-2 group-hover:gap-4 transition-all">
                    List your hall <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t py-12 px-6">
        <div class="max-w-7xl mx-auto text-center text-sm text-gray-500">
            <p class="mb-4">© 2026 Bookingjaunt.com. All rights reserved.</p>
            <div class="flex justify-center gap-6 font-semibold">
                <a href="#">About Us</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms & Conditions</a>
                <a href="#">Support</a>
            </div>
        </div>
    </footer>

</body>
</html>
