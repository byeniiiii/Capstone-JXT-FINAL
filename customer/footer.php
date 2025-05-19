<style>
    /* Footer */
    .footer {
        background-color: #343a40;
        color: white;
        padding: 20px 0;
        margin-top: 40px;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    
    .footer-section {
        flex: 1;
        min-width: 200px;
        padding: 0 15px;
        margin-bottom: 20px;
    }
    
    .footer-title {
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 8px;
    }
    
    .footer-links a {
        color: rgba(255,255,255,0.75);
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .footer-links a:hover {
        color: white;
    }
    
    .social-icons a {
        color: rgba(255,255,255,0.75);
        font-size: 1.2rem;
        margin-right: 15px;
        transition: color 0.2s;
    }
    
    .social-icons a:hover {
        color: white;
    }
    
    .copyright {
        width: 100%;
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,0.1);
        margin-top: 20px;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.75);
    }
</style>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h5 class="footer-title">JX Tailoring</h5>
                <p>Quality custom tailoring and sublimation services for all your needs. We create personalized clothing that fits perfectly.</p>
                <div class="social-icons mt-3">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="place_order.php">Place Order</a></li>
                    <li><a href="track_order.php">Track Order</a></li>
                    <li><a href="#">About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h5 class="footer-title">Contact Us</h5>
                <ul class="footer-links">
                    <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                    <li><i class="fas fa-envelope me-2"></i> info@jxtailoring.com</li>
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Tailor St, Manila, PH</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> JX Tailoring. All rights reserved.
        </div>
    </div>
</footer>