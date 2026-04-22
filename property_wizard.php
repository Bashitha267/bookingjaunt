<?php
require_once 'config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}

$type = $_GET['type'] ?? 'hotel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Property - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .step-circle { 
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            background: white; border: 2px solid #e2e8f0; color: #64748b; font-weight: 700; transition: all 0.3s;
            position: relative; z-index: 10;
        }
        .step-item.active .step-circle { background: #006ce4; border-color: #006ce4; color: white; box-shadow: 0 0 0 4px rgba(0, 108, 228, 0.1); }
        .step-item.completed .step-circle { background: #10b981; border-color: #10b981; color: white; }
        
        .step-line { 
            position: absolute; top: 20px; left: 50%; width: 100%; height: 2px; background: #e2e8f0; z-index: 5;
        }
        .step-item:last-child .step-line { display: none; }
        .step-item.completed .step-line { background: #10b981; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <nav class="bg-white border-b p-4 flex justify-between items-center sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-xl font-bold text-[#006ce4]">Bookingjaunt</a>
            <span class="text-gray-300">|</span>
            <span class="text-sm font-semibold text-gray-600">Register your property</span>
        </div>
        <div class="flex items-center gap-4 bg-gray-50 px-4 py-1.5 rounded-full border">
            <span class="text-sm font-bold"><?= $_SESSION['user_name'] ?></span>
            <div class="w-8 h-8 bg-[#003580] text-white rounded-full flex items-center justify-center font-bold text-xs">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
        </div>
    </nav>

    <!-- Stepper Container -->
    <div class="max-w-5xl mx-auto w-full pt-12 px-6">
        <div class="flex justify-between relative mb-16">
            <?php 
            $steps = ["Type", "Info", "Manager", "Rules", "Amenities", "Photos", "Finish"];
            foreach($steps as $i => $name): 
                $num = $i + 1;
            ?>
                <div class="step-item flex-1 flex flex-col items-center group relative" data-step="<?= $num ?>">
                    <div class="step-line"></div>
                    <div class="step-circle mb-3"><?= $num ?></div>
                    <span class="text-xs font-bold text-gray-400 group-[.active]:text-[#006ce4] group-[.completed]:text-[#10b981] transition-all uppercase tracking-wider"><?= $name ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Wizard Form -->
        <main class="max-w-3xl mx-auto pb-24">
            <form id="property-form" class="bg-white rounded-[2rem] shadow-xl shadow-blue-900/5 p-12 border border-blue-50">
                
                <!-- Step 1: Business Type -->
                <div class="wizard-step active" data-step="1">
                    <div class="mb-10">
                        <span class="text-[#006ce4] font-bold text-sm tracking-widest uppercase mb-2 block">Step 01</span>
                        <h2 class="text-3xl font-extrabold text-gray-900">What are you listing?</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-8">
                        <label class="group relative border-2 rounded-[2rem] p-10 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="hotel" required class="hidden" <?= $type == 'hotel' ? 'checked' : '' ?>>
                            <div class="w-20 h-20 bg-blue-50 text-[#006ce4] rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                                <i class="fas fa-hotel text-3xl"></i>
                            </div>
                            <div class="font-bold text-xl mb-2">Hotel / Resort</div>
                            <p class="text-sm text-gray-500">Provide luxury stay and multiple services.</p>
                        </label>
                        <label class="group relative border-2 rounded-[2rem] p-10 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="reception_hall" class="hidden" <?= $type == 'reception_hall' ? 'checked' : '' ?>>
                            <div class="w-20 h-20 bg-blue-50 text-[#006ce4] rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                                <i class="fas fa-glass-cheers text-3xl"></i>
                            </div>
                            <div class="font-bold text-xl mb-2">Reception Hall</div>
                            <p class="text-sm text-gray-500">Host weddings, parties and grand events.</p>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Basic Info -->
                <div class="wizard-step" data-step="2">
                    <div class="mb-10">
                        <span class="text-[#006ce4] font-bold text-sm tracking-widest uppercase mb-2 block">Step 02</span>
                        <h2 class="text-3xl font-extrabold text-gray-900">General Information</h2>
                    </div>
                    <div class="space-y-8">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">Property Name</label>
                            <input type="text" name="property_name" required placeholder="e.g. Grand Plaza Hotel" class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">City</label>
                                <input type="text" name="city" required class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">Country</label>
                                <input type="text" name="country" required value="Sri Lanka" class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">Physical Address</label>
                            <textarea name="street_address" required rows="3" class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Manager Setup -->
                <div class="wizard-step" data-step="3">
                    <div class="mb-10">
                        <span class="text-[#006ce4] font-bold text-sm tracking-widest uppercase mb-2 block">Step 03</span>
                        <h2 class="text-3xl font-extrabold text-gray-900">Manager Details</h2>
                    </div>
                    <div class="bg-blue-50/50 p-6 rounded-2xl mb-8 flex items-center justify-between border border-blue-100">
                        <span class="font-bold text-gray-700">Are you the manager of this property?</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:after:w-5 after:transition-all peer-checked:bg-[#006ce4]"></div>
                        </label>
                    </div>
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">Manager Name</label>
                                <input type="text" name="manager_name" class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3 ml-1">Contact Number</label>
                                <input type="text" name="manager_phone" class="w-full px-6 py-4 rounded-2xl border bg-gray-50 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placeholder for Steps 4-6 -->
                <div class="wizard-step" data-step="4">
                    <div class="mb-10 text-center py-20">
                        <i class="fas fa-shield-alt text-6xl text-blue-200 mb-6"></i>
                        <h2 class="text-3xl font-extrabold text-gray-900">Policies & Rules</h2>
                        <p class="text-gray-500 mt-4">Define your property rules, cancellation policies and terms.</p>
                    </div>
                </div>

                <div class="wizard-step" data-step="5">
                    <div class="mb-10 text-center py-20">
                        <i class="fas fa-concierge-bell text-6xl text-blue-200 mb-6"></i>
                        <h2 class="text-3xl font-extrabold text-gray-900">Amenities & Services</h2>
                        <p class="text-gray-500 mt-4">What makes your property special? List your features.</p>
                    </div>
                </div>

                <div class="wizard-step" data-step="6">
                    <div class="mb-10 text-center py-20">
                        <i class="fas fa-images text-6xl text-blue-200 mb-6"></i>
                        <h2 class="text-3xl font-extrabold text-gray-900">Property Photos</h2>
                        <p class="text-gray-500 mt-4">Add beautiful high-quality photos to attract more travelers.</p>
                    </div>
                </div>

                <!-- Step 7: Final Review -->
                <div class="wizard-step" data-step="7">
                    <div class="mb-10 text-center">
                        <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-8">
                            <i class="fas fa-check text-4xl"></i>
                        </div>
                        <h2 class="text-3xl font-extrabold text-gray-900">Ready to go live!</h2>
                        <p class="text-gray-500 mt-4 max-w-md mx-auto">Please review your information. Once submitted, our team will verify your property within 24 hours.</p>
                    </div>
                    <button type="submit" class="w-full bg-[#10b981] text-white py-5 rounded-[2rem] font-bold text-xl shadow-xl shadow-green-900/10 hover:bg-[#059669] transition-all transform hover:-translate-y-1">Submit Property for Review</button>
                </div>

                <!-- Footer Navigation -->
                <div class="flex justify-between mt-16 pt-10 border-t border-gray-100">
                    <button type="button" id="prev-btn" onclick="changeStep(-1)" class="px-8 py-3 font-bold text-gray-400 hover:text-gray-900 transition-all flex items-center gap-2 invisible">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" id="next-btn" onclick="changeStep(1)" class="px-12 py-4 bg-[#006ce4] text-white rounded-full font-extrabold text-lg shadow-lg shadow-blue-900/10 hover:bg-[#0056b3] transition-all flex items-center gap-3">
                        Continue <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 7;

        function updateDisplay() {
            // Update Wizard Steps
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');
            
            // Update Stepper UI
            document.querySelectorAll('.step-item').forEach(el => {
                const step = parseInt(el.dataset.step);
                el.classList.remove('active', 'completed');
                if (step === currentStep) el.classList.add('active');
                else if (step < currentStep) el.classList.add('completed');
            });

            // Update Navigation Buttons
            document.getElementById('prev-btn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            document.getElementById('next-btn').style.display = currentStep === totalSteps ? 'none' : 'block';
        }

        function changeStep(delta) {
            if (currentStep + delta > 0 && currentStep + delta <= totalSteps) {
                currentStep += delta;
                updateDisplay();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        document.getElementById('property-form').onsubmit = async (e) => {
            e.preventDefault();
            alert('Congratulations! Your property listing has been submitted successfully.');
            window.location.href = 'index.php';
        };

        // Initial display update
        updateDisplay();
    </script>
</body>
</html>
