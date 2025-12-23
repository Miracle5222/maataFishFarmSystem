<?php include 'partials/header.php'; ?>

<main>
    <!-- Hero Section -->
    <section style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 60px 20px; text-align: center;">
        <div class="container">
            <h1 style="font-size: 2.5em; margin-bottom: 15px;">Get in Touch</h1>
            <p style="font-size: 1.1em; margin-bottom: 0;">We'd love to hear from you! Contact us for orders, events, or inquiries.</p>
        </div>
    </section>

    <section style="padding: 50px 20px;">
        <div class="container">
            
            <!-- Contact Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 50px;">
                
                <!-- Location Card -->
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #27ae60;">
                    <i class="fas fa-map-marker-alt" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="color: #27ae60; margin-bottom: 10px;">Our Location</h3>
                    <p style="color: #666; line-height: 1.6;">
                        <strong>New Basak</strong><br>
                        Dumingag, Zamboanga del Sur<br>
                        <strong style="color: #27ae60;">Philippines</strong>
                    </p>
                    <p style="color: #999; font-size: 0.9em; margin-top: 10px;">Easily accessible by car or public transportation</p>
                </div>

                <!-- Business Hours Card -->
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #52be80;">
                    <i class="fas fa-clock" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="color: #27ae60; margin-bottom: 10px;">Business Hours</h3>
                    <div style="color: #666; text-align: left; display: inline-block;">
                        <p style="margin: 5px 0;"><strong>Monday - Friday:</strong> 8:00 AM - 6:00 PM</p>
                        <p style="margin: 5px 0;"><strong>Saturday:</strong> 9:00 AM - 5:00 PM</p>
                        <p style="margin: 5px 0;"><strong>Sunday:</strong> 10:00 AM - 3:00 PM</p>
                    </div>
                </div>

                <!-- Services Card -->
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #229954;">
                    <i class="fas fa-concierge-bell" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="color: #27ae60; margin-bottom: 10px;">Services Available</h3>
                    <ul style="list-style: none; color: #666; text-align: left; display: inline-block;">
                        <li style="margin-bottom: 6px;"><strong>✓</strong> Walk-in Dining</li>
                        <li style="margin-bottom: 6px;"><strong>✓</strong> Fish Orders</li>
                        <li style="margin-bottom: 6px;"><strong>✓</strong> Event Catering</li>
                        <li><strong>✓</strong> Fry/Fingerlings</li>
                    </ul>
                </div>

            </div>

            <!-- Contact Info Boxes -->
            <div style="max-width: 900px; margin: 0 auto 50px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                
                <!-- Phone -->
                <div style="background: #e8f5e9; padding: 25px; border-radius: 8px; border-left: 4px solid #27ae60;">
                    <h3 style="color: #27ae60; margin-bottom: 12px;"><i class="fas fa-phone"></i> Call Us</h3>
                    <p style="color: #666; margin-bottom: 8px;">For orders or inquiries:</p>
                    <p style="margin: 8px 0;"><a href="tel:+63" style="color: #27ae60; text-decoration: none; font-weight: 600; font-size: 1.05em;">Contact us directly</a></p>
                    <p style="color: #999; font-size: 0.9em; margin-top: 10px;">Call during business hours for immediate assistance</p>
                </div>

                <!-- Facebook -->
                <div style="background: #e8f5e9; padding: 25px; border-radius: 8px; border-left: 4px solid #27ae60;">
                    <h3 style="color: #27ae60; margin-bottom: 12px;"><i class="fab fa-facebook"></i> Facebook</h3>
                    <p style="color: #666; margin-bottom: 8px;">Order online anytime:</p>
                    <p style="margin: 8px 0;"><strong style="color: #27ae60;">Maata Fish Farm</strong></p>
                    <p style="color: #999; font-size: 0.9em; margin-top: 10px;">Message us on Facebook for orders and reservations</p>
                </div>

                <!-- Special Requests -->
                <div style="background: #e8f5e9; padding: 25px; border-radius: 8px; border-left: 4px solid #27ae60;">
                    <h3 style="color: #27ae60; margin-bottom: 12px;"><i class="fas fa-star"></i> Special Orders</h3>
                    <p style="color: #666; margin-bottom: 8px;">Need something specific?</p>
                    <p style="color: #333; margin: 8px 0;"><strong>We accommodate:</strong></p>
                    <ul style="list-style: none; color: #666; font-size: 0.95em;">
                        <li>• Large event catering</li>
                        <li>• Custom menu preparations</li>
                        <li>• Bulk fish orders</li>
                    </ul>
                </div>

            </div>

            <!-- Contact Form
            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                <h2 style="color: #27ae60; margin-bottom: 10px; text-align: center;"><i class="fas fa-envelope"></i> Send us a Message</h2>
                <p style="color: #666; text-align: center; margin-bottom: 25px;">We'll respond as soon as possible</p>
                
                <form method="POST" style="display: grid; gap: 15px;">
                    
                    <div>
                        <label for="name" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Your Name *</label>
                        <input type="text" id="name" name="name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="email" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Email Address *</label>
                        <input type="email" id="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="phone" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Phone Number</label>
                        <input type="tel" id="phone" name="phone" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    </div>

                    <div>
                        <label for="subject" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Subject *</label>
                        <select id="subject" name="subject" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                            <option value="">-- Select Subject --</option>
                            <option value="dining">Dining Reservation</option>
                            <option value="fish-order">Fish Order</option>
                            <option value="event-catering">Event Catering</option>
                            <option value="fry-purchase">Fry/Fingerling Purchase</option>
                            <option value="general">General Inquiry</option>
                        </select>
                    </div>

                    <div>
                        <label for="message" style="display: block; font-weight: 600; margin-bottom: 8px; color: #27ae60;">Message *</label>
                        <textarea id="message" name="message" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: 'Roboto', sans-serif;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px; font-size: 16px; font-weight: 600;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>

                </form>
            </div> -->

            <!-- Alternative Contact Methods -->
            <div style="max-width: 700px; margin: 50px auto 0; padding: 25px; background: #f0f8f5; border-radius: 8px; border-top: 3px solid #27ae60; text-align: center;">
                <h3 style="color: #27ae60; margin-bottom: 15px;"><i class="fas fa-lightbulb"></i> Quick Tips</h3>
                <ul style="list-style: none; color: #666; font-size: 0.95em;">
                    <li style="margin-bottom: 8px;">💡 <strong>For fastest service:</strong> Message us on Facebook or call during business hours</li>
                    <li style="margin-bottom: 8px;">🎉 <strong>Event Catering:</strong> Plan ahead - contact us at least 3 days in advance</li>
                    <li>🐟 <strong>Fresh Fish Orders:</strong> Available daily - advance orders recommended</li>
                </ul>
            </div>

        </div>
    </section>

</main>

<?php include 'partials/footer.php'; ?>
                <p style="margin-top: 15px; color: #666;">For urgent orders, call us anytime. We deliver 24/7 for bulk orders.</p>
            </div>

        </div>
    </section>
</main>

<?php include 'partials/footer.php'; ?>
