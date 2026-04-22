<?php 
require_once 'config.php'; 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookingjaunt - Find your next stay</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">Bookingjaunt</div>
        <div class="nav-links">
            <a href="index.php" class="nav-item"><i class="fas fa-bed"></i> Stays</a>
            <a href="#" class="nav-item"><i class="fas fa-plane"></i> Flights</a>
            <a href="#" class="nav-item"><i class="fas fa-car"></i> Car rentals</a>
            <a href="#" class="nav-item"><i class="fas fa-camera"></i> Attractions</a>
            <a href="#" class="nav-item"><i class="fas fa-taxi"></i> Airport taxis</a>
        </div>
        <div class="nav-auth">
            <button class="nav-item btn-outline" style="background:transparent; border:none;">USD</button>
            <button class="nav-item btn-outline" style="background:transparent; border:none;"><img src="https://flagcdn.com/w20/us.png" width="20" alt="US"></button>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- List Your Property Button -->
                <a href="list_your_property.php" class="btn-property-yellow">
                    <i class="fas fa-plus-circle"></i> List your property
                </a>
                
                <!-- User Profile Section -->
                <div class="user-nav-section">
                    <div class="user-pill">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </div>
                    <a href="logout.php" class="logout-icon" title="Sign out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
<?php else: ?>
                <a href="register.php" class="btn btn-light">Register</a>
                <a href="register.php" class="btn btn-light">Sign in</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="contact-floating">
            <i class="fas fa-phone-alt"></i> +94 1000000
        </div>
        <div class="hero-content">
            <h1>Find your next stay</h1>
            <p>Search deals on hotels, homes, and much more...</p>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-item">
                <i class="fas fa-bed"></i>
                <input type="text" placeholder="Where are you going?">
            </div>
            <div class="search-item">
                <i class="far fa-calendar-alt"></i>
                <span>Check-in — Check-out</span>
            </div>
            <div class="search-item">
                <i class="fas fa-user-friends"></i>
                <span>2 adults · 0 children · 1 room</span>
            </div>
            <button class="btn btn-search">Search</button>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="filter-box">
                <div class="filter-title">Filter by:</div>
                <div class="filter-group">
                    <h4>Your budget (per night)</h4>
                    <label class="filter-option"><input type="checkbox"> $0 - $50</label>
                    <label class="filter-option"><input type="checkbox"> $50 - $100</label>
                    <label class="filter-option"><input type="checkbox"> $100 - $150</label>
                    <label class="filter-option"><input type="checkbox"> $150 - $200</label>
                    <label class="filter-option"><input type="checkbox"> $200+</label>
                </div>
                <div class="filter-group">
                    <h4>Star rating</h4>
                    <label class="filter-option"><input type="checkbox"> 3 stars</label>
                    <label class="filter-option"><input type="checkbox"> 4 stars</label>
                    <label class="filter-option"><input type="checkbox"> 5 stars</label>
                </div>
                <div class="filter-group">
                    <h4>Popular filters</h4>
                    <label class="filter-option"><input type="checkbox"> Free WiFi</label>
                    <label class="filter-option"><input type="checkbox"> Breakfast included</label>
                    <label class="filter-option"><input type="checkbox"> Pool</label>
                    <label class="filter-option"><input type="checkbox"> Parking</label>
                </div>
            </div>
        </aside>

        <!-- Results -->
        <section class="results">
            <div class="results-header">
                <h2>SRI LANKA : 10 properties found</h2>
                <div class="sort-dropdown">
                    Sort by: 
                    <select style="padding: 5px; border-radius: 4px;">
                        <option>Our top picks</option>
                    </select>
                </div>
            </div>

            <!-- Property Cards -->
            <div class="property-card">
                <img src="assets/hotel1.png" alt="Sheraton" class="property-img">
                <div class="property-details">
                    <div class="property-info-main">
                        <div>
                            <div class="property-name">Sheraton Times Square Hotel <span style="color:#febb02"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span></div>
                            <div class="property-location">Manhattan, New York • Show on map • 250m from center</div>
                        </div>
                        <div class="property-rating-box">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="text-align:right">
                                    <div style="font-weight:700">Very Good</div>
                                    <div style="font-size:12px; color:var(--text-muted)">1,245 reviews</div>
                                </div>
                                <div class="property-rating">8.5</div>
                            </div>
                        </div>
                    </div>
                    <div class="badge-green">Free cancellation</div>
                    <div style="font-size:13px; font-weight:700">Superior Double Room</div>
                    <div style="font-size:12px">1 extra-large double bed</div>
                    <div style="color:#008009; font-size:12px; font-weight:700; margin-top:5px;"><i class="fas fa-check"></i> No prepayment needed – pay at the property</div>
                    
                    <div class="property-price-box">
                        <div class="price-label">1 night, 2 adults</div>
                        <div style="text-decoration: line-through; font-size: 12px; color: #d00;">$347</div>
                        <div class="price-value">$295</div>
                        <div style="font-size: 11px; color: var(--text-muted);">+ $45 taxes and charges</div>
                        <button class="btn btn-availability">See availability</button>
                    </div>
                </div>
            </div>

            <div class="property-card">
                <img src="assets/hotel2.png" alt="Central Park North" class="property-img">
                <div class="property-details">
                    <div class="property-info-main">
                        <div>
                            <div class="property-name">The Central Park North <span style="color:#febb02"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span></div>
                            <div class="property-location">Harlem, New York • Show on map</div>
                        </div>
                        <div class="property-rating-box">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="text-align:right">
                                    <div style="font-weight:700">Good</div>
                                    <div style="font-size:12px; color:var(--text-muted)">842 reviews</div>
                                </div>
                                <div class="property-rating">7.8</div>
                            </div>
                        </div>
                    </div>
                    <div class="badge-green" style="background:#e0f2fe; color:#0369a1;">Early 2024 Deal</div>
                    <div style="font-size:13px; font-weight:700">Standard Single Room</div>
                    <div style="font-size:12px">Shared bathroom</div>
                    
                    <div class="property-price-box">
                        <div class="price-label">1 night, 1 adult</div>
                        <div class="price-value">$152</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Includes taxes and charges</div>
                        <button class="btn btn-availability">See availability</button>
                    </div>
                </div>
            </div>

            <div class="property-card">
                <img src="assets/hotel3.png" alt="Ario NoMad" class="property-img">
                <div class="property-details">
                    <div class="property-info-main">
                        <div>
                            <div class="property-name">Ario NoMad <span style="color:#febb02"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span></div>
                            <div class="property-location">Midtown, New York • Show on map</div>
                        </div>
                        <div class="property-rating-box">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="text-align:right">
                                    <div style="font-weight:700">Excellent</div>
                                    <div style="font-size:12px; color:var(--text-muted)">2,582 reviews</div>
                                </div>
                                <div class="property-rating">9.2</div>
                            </div>
                        </div>
                    </div>
                    <div class="badge-green" style="background:#f0fdf4; color:#15803d;">Sustainability Level 2</div>
                    <div style="color:#15803d; font-size:12px; font-weight:700;">FREE breakfast</div>
                    <div style="font-size:13px; font-weight:700">Sky Queen Room</div>
                    <div style="font-size:12px">City view</div>
                    
                    <div class="property-price-box">
                        <div class="price-label">1 night, 2 adults</div>
                        <div class="price-value">$412</div>
                        <div style="font-size: 11px; color: var(--text-muted);">+ $80 taxes and charges</div>
                        <button class="btn btn-availability">See availability</button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div style="display:flex; justify-content:center; gap:5px; margin-top:20px;">
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i class="fas fa-chevron-left"></i></button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:var(--primary); color:#fff; border-radius:4px;">1</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">2</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">3</button>
                <span>...</span>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">24</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
    </main>

    <!-- Footer CTA -->
    <section class="footer-cta">
        <div style="font-size:24px; font-weight:700;">Save time, save money! <br><span style="font-size:14px; font-weight:400; opacity:0.8;">Sign up and we'll send the best deals to you</span></div>
        <div style="display:flex; gap:10px;">
            <input type="text" placeholder="Your email address" style="padding:12px 20px; border-radius:4px; border:none; width:300px;">
            <button class="btn btn-secondary" style="background:var(--secondary); color:#fff; padding:12px 30px;">Subscribe</button>
        </div>
    </section>

    <!-- Footer Main -->
    <footer class="footer-main">
        <div class="footer-col">
            <h4>Support</h4>
            <ul>
                <li>Help Center</li>
                <li>Customer Service</li>
                <li>Safety Resource Center</li>
                <li>Terms & Conditions</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Discover</h4>
            <ul>
                <li>Genius Rewards</li>
                <li>Seasonal Deals</li>
                <li>Travel Articles</li>
                <li>Car rentals</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Partners</h4>
            <ul>
                <li>List your property</li>
                <li>Become an affiliate</li>
                <li>Connectivity Partners</li>
            </ul>
            <div style="font-size:32px; font-weight:700; color:rgba(0,0,0,0.1); margin-top:20px;">+94 1000000</div>
        </div>
        <div class="footer-col">
            <h4>About</h4>
            <ul>
                <li>About TravelEase</li>
                <li>Careers</li>
                <li>Sustainability</li>
                <li>Press center</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Follow us</h4>
            <div style="display:flex; gap:10px;">
                <i class="fab fa-facebook" style="font-size:20px;"></i>
                <i class="fab fa-instagram" style="font-size:20px;"></i>
                <i class="fab fa-twitter" style="font-size:20px;"></i>
            </div>
        </div>
    </footer>

    <div class="footer-bottom">
        <div>© 2026 Bookingjaunt.com All rights reserved.</div>
        <div style="display:flex; gap:15px;">
            <span>Privacy & Cookies</span>
            <span>Manage Cookie Settings</span>
            <span>MSA Statement</span>
        </div>
    </footer>

</body>
</html>
