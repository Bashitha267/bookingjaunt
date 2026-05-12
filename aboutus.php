<?php
require_once 'config.php';
session_start();
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
    <title>About Us - Bookingjaunt</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#003580',
                        secondary: '#006ce4',
                        gold: '#febb02',
                        palm: '#008009',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .mesh-gradient { background: radial-gradient(at 0% 0%, hsla(214,100%,92%,1) 0, transparent 50%), radial-gradient(at 50% 0%, hsla(214,100%,95%,1) 0, transparent 50%), radial-gradient(at 100% 0%, hsla(214,100%,92%,1) 0, transparent 50%); }
    </style>
</head>
<body class="bg-white text-neutral-800">

    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="relative h-[60vh] flex items-center justify-center overflow-hidden">
        <img src="https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=1920&q=80" class="absolute inset-0 w-full h-full object-cover" alt="About Hero">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-7xl font-black text-white font-outfit mb-6 tracking-tight">Our Story. Our Passion.</h1>
            <p class="text-xl md:text-2xl text-white/90 font-medium leading-relaxed">Redefining how you experience the wonders of Sri Lanka, one stay at a time.</p>
        </div>
    </section>

    <!-- Our Mission -->
    <section class="py-24 mesh-gradient">
        <div class="max-w-[1400px] mx-auto px-4 lg:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                <div class="space-y-8">
                    <div class="inline-block px-4 py-1.5 bg-primary/10 text-primary rounded-full text-[12px] font-black uppercase tracking-[0.2em]">Our Mission</div>
                    <h2 class="text-4xl md:text-5xl font-black text-neutral-800 font-outfit leading-tight">We empower travelers to discover local gems and premium escapes effortlessly.</h2>
                    <p class="text-lg text-neutral-600 leading-relaxed">
                        At Bookingjaunt, we believe travel should be more than just visiting a place—it should be about the connection you make with the culture, the people, and the landscapes. Our platform was born from a desire to bridge the gap between world-class service and local authenticity.
                    </p>
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-4xl font-black text-primary mb-1">500+</p>
                            <p class="text-sm font-bold text-neutral-400 uppercase tracking-widest">Properties</p>
                        </div>
                        <div>
                            <p class="text-4xl font-black text-primary mb-1">10k+</p>
                            <p class="text-sm font-bold text-neutral-400 uppercase tracking-widest">Happy Travelers</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="rounded-[2.5rem] overflow-hidden shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-700">
                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80" alt="Mission" class="w-full h-[500px] object-cover">
                    </div>
                    <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-gold rounded-full opacity-10 blur-3xl -z-10"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-24 bg-neutral-900 text-white">
        <div class="max-w-[1400px] mx-auto px-4 lg:px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-black font-outfit mb-4">The Bookingjaunt Difference</h2>
                <p class="text-neutral-400 max-w-2xl mx-auto">Why thousands of travelers trust us with their Sri Lankan adventures.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-10 rounded-3xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                    <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Curated Quality</h3>
                    <p class="text-neutral-400 leading-relaxed">Every property on our platform is handpicked to ensure they meet our rigorous standards for comfort, service, and authenticity.</p>
                </div>
                <div class="p-10 rounded-3xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                    <div class="w-16 h-16 bg-secondary rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Local Partnerships</h3>
                    <p class="text-neutral-400 leading-relaxed">We work directly with property owners and local guides to provide you with exclusive deals and insider knowledge you won't find anywhere else.</p>
                </div>
                <div class="p-10 rounded-3xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                    <div class="w-16 h-16 bg-palm rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Secure & Simple</h3>
                    <p class="text-neutral-400 leading-relaxed">Our advanced booking engine ensures your transactions are secure and your planning is stress-free, with 24/7 support by your side.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Vision -->
    <section class="py-24 relative overflow-hidden">
        <div class="max-w-[1400px] mx-auto px-4 lg:px-6">
            <div class="bg-primary rounded-[3rem] p-12 md:p-24 relative overflow-hidden">
                <div class="relative z-10 max-w-3xl">
                    <h2 class="text-4xl md:text-6xl font-black text-white font-outfit mb-8 leading-tight">Our vision is to make Sri Lanka the world's most accessible paradise.</h2>
                    <p class="text-xl text-white/80 leading-relaxed mb-12">
                        We are building a future where every traveler can find their perfect slice of island life, from high-altitude tea estate villas to hidden beach bungalows, with just a few clicks.
                    </p>
                    <a href="hotels.php" class="inline-flex items-center gap-3 bg-white text-primary px-10 py-5 rounded-2xl font-black text-lg hover:bg-neutral-100 transition-all shadow-xl">
                        Start Your Journey <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <!-- Background decoration -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-secondary/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 p-12 opacity-10">
                    <i class="fas fa-plane-departure text-[20rem] text-white"></i>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>
