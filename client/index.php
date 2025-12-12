<?php include 'partials/header.php'; ?>

<main>
    <section class="hero" style="
        background: linear-gradient(rgba(39, 174, 96, 0.8), rgba(39, 174, 96, 0.8)), url('https://via.placeholder.com/1200x400');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 20px;
        text-align: center;
        margin: 0;
    ">
        <div class="container">
            <h1 style="font-size: 48px; margin-bottom: 20px;">Welcome to Maata Fish Farm</h1>
            <p style="font-size: 20px; margin-bottom: 15px;">Family-owned Aquaculture & Food Service</p>
            <p style="font-size: 18px; margin-bottom: 30px;">Fresh Fish Daily • Authentic Filipino Cuisine • Event Hosting</p>
            <a href="menu.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">
                <i class="fas fa-utensils"></i> View Our Food Menu
            </a>
            <a href="booking.php" class="btn btn-secondary" style="font-size: 16px; padding: 12px 30px;">
                <i class="fas fa-calendar"></i> Reserve Now
            </a>
        </div>
    </section>

    <section style="padding: 50px 20px;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 40px; color: #27ae60; font-size: 32px;">What We Offer</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-fish" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Fresh Fish</h3>
                    <p>Tilapia, Japanese Koi, and Catfish from our 2-hectare pond farm. Available daily at ₱200/kg</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-utensils" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Food Service</h3>
                    <p>Authentic Filipino cuisine featuring our fresh-caught fish. Entrance fee: ₱30/person</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-birthday-cake" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Event Hosting</h3>
                    <p>Host your celebrations, family gatherings, and private events at our dining facility</p>
                </div>
            </div>
        </div>
    </section>

    <section style="padding: 50px 20px; background-color: white;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 40px; color: #27ae60; font-size: 32px;">Fresh Fish Daily</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img src="../assets/img/tilapia.jpg" style="width: 100%; height: 250px; object-fit: cover;">
                    <div style="padding: 15px;">
                        <h4 style="margin-bottom: 5px;">Tilapia</h4>
                        <p style="color: #666; margin-bottom: 10px;">Most in-demand fresh tilapia</p>
                        <p style="color: #27ae60; font-weight: bold;">₱200/kg</p>
                    </div>
                </div>
                <div style="background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img src="../assets/img/catfish.jpg" style="width: 100%; height: 250px; object-fit: cover;">
                    <div style="padding: 15px;">
                        <h4 style="margin-bottom: 5px;">Catfish</h4>
                        <p style="color: #666; margin-bottom: 10px;">Fresh farm-raised catfish</p>
                        <p style="color: #27ae60; font-weight: bold;">₱200/kg</p>
                    </div>
                </div>
                <div style="background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <img src="../assets/img/koi.jpg" style="width: 100%; height: 250px; object-fit: cover;">
                    <div style="padding: 15px;">
                        <h4 style="margin-bottom: 5px;">Japanese Koi</h4>
                        <p style="color: #666; margin-bottom: 10px;">Premium Japanese Koi fish</p>
                        <p style="color: #27ae60; font-weight: bold;">₱200/kg</p>
                    </div>
                </div>
              
            </div>
        </div>
    </section>

</main>

<?php include 'partials/footer.php'; ?>
