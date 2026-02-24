<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Table Availability System Setup</title>
    <style>
        body { 
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container { 
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #27ae60; margin-bottom: 10px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 30px; }
        .step { 
            background: #f9f9f9;
            border-left: 4px solid #27ae60;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .step-number { 
            display: inline-block;
            background: #27ae60;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 10px;
        }
        .step h3 { margin: 10px 0 10px 0; display: inline-block; }
        .step p { margin: 10px 0; }
        .code { 
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow: auto;
        }
        .button-group { margin-top: 20px; }
        .btn { 
            display: inline-block;
            padding: 12px 24px;
            margin: 5px 5px 5px 0;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-primary { background: #27ae60; color: white; }
        .btn-primary:hover { background: #229954; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Table Availability System Setup</h1>
        <p class="subtitle">Complete this setup to enable dine-in table reservations</p>

        <div class="info-box">
            <strong>✓ Status:</strong> System ready for setup. Follow the steps below to enable table availability for online reservations.
        </div>

        <h2 style="color: #27ae60; margin-top: 40px;">Setup Steps</h2>

        <!-- Step 1 -->
        <div class="step">
            <h3><span class="step-number">1</span> Create Availability Tables Database</h3>
            <p>This will create the <code>availability_tables</code> table that stores dining table information.</p>
            <div class="button-group">
                <form method="GET" action="setup_availability_table.php" style="display: inline;">
                    <button type="submit" class="btn btn-primary">Run Migration</button>
                </form>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="step">
            <h3><span class="step-number">2</span> Add Table ID Column to Reservations</h3>
            <p>This will add the <code>table_id</code> column to the reservations table for tracking which table was booked.</p>
            <div class="button-group">
                <form method="GET" action="setup_table_id_column.php" style="display: inline;">
                    <button type="submit" class="btn btn-primary">Run Migration</button>
                </form>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="step">
            <h3><span class="step-number">3</span> Create Your First Dining Table</h3>
            <p>Go to availability management and add your dining tables with capacity information.</p>
            <div class="button-group">
                <a href="availability_set.php" class="btn btn-primary">Go to Create Table</a>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="step">
            <h3><span class="step-number">4</span> View & Manage Tables</h3>
            <p>View all created tables and manage their status (Available / Not Available).</p>
            <div class="button-group">
                <a href="availability_check.php" class="btn btn-primary">Go to Manage Tables</a>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="step">
            <h3><span class="step-number">5</span> Test Online Reservation</h3>
            <p>Customers can now book tables through the online dine-in reservation form.</p>
            <div class="button-group">
                <a href="http://localhost/maataFishFarmSystem/client/booking.php?type=dine-in" target="_blank" class="btn btn-secondary">Test Dine-In Booking</a>
            </div>
        </div>

        <h2 style="color: #27ae60; margin-top: 40px;">System Overview</h2>

        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Description</th>
                    <th>File/Link</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Create Tables</strong></td>
                    <td>Add new dining tables with capacity</td>
                    <td><a href="availability_set.php">availability_set.php</a></td>
                </tr>
                <tr>
                    <td><strong>Manage Tables</strong></td>
                    <td>View, edit, and delete tables</td>
                    <td><a href="availability_check.php">availability_check.php</a></td>
                </tr>
                <tr>
                    <td><strong>Customer Booking</strong></td>
                    <td>Customers book tables online</td>
                    <td><a href="client/booking.php?type=dine-in" target="_blank">booking.php?type=dine-in</a></td>
                </tr>
                <tr>
                    <td><strong>Database Tables</strong></td>
                    <td>Stores table info</td>
                    <td><code>availability_tables</code></td>
                </tr>
            </tbody>
        </table>

        <h2 style="color: #27ae60; margin-top: 40px;">Table Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Description</th>
                    <th>Customer Can Book?</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span style="background: #d4edda; color: #155724; padding: 8px 12px; border-radius: 3px; font-weight: 600;">Available</span></td>
                    <td>Table is available for bookings</td>
                    <td style="color: #27ae60; font-weight: bold;">✓ Yes</td>
                </tr>
                <tr>
                    <td><span style="background: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 3px; font-weight: 600;">Not Available</span></td>
                    <td>Table is unavailable (maintenance, booked, etc.)</td>
                    <td style="color: #dc3545; font-weight: bold;">✗ No</td>
                </tr>
            </tbody>
        </table>

        <div class="info-box" style="margin-top: 40px;">
            <strong>💡 Tip:</strong> After setting up the system, create several example tables with different capacities (e.g., Table 1: 2 seats, Table 2: 4 seats, Table 3: 6 seats) to test the customer booking form.
        </div>

        <h2 style="color: #27ae60; margin-top: 40px;">Troubleshooting</h2>

        <div class="step">
            <h3>❌ Database error: Table 'maata.availability_tables' doesn't exist</h3>
            <p><strong>Solution:</strong> Run the "Create Availability Tables Database" migration in Step 1 above.</p>
        </div>

        <div class="step">
            <h3>❌ No tables showing in dine-in booking form</h3>
            <p><strong>Solution:</strong> Create at least one table in Step 3 (Create Your First Dining Table) and ensure the status is set to "Available".</p>
        </div>

        <div class="step">
            <h3>❌ Cannot select a table with capacity validation error</h3>
            <p><strong>Solution:</strong> Make sure the number of guests doesn't exceed the table's capacity. Select a table with higher capacity or reduce the number of guests.</p>
        </div>

        <hr style="margin: 40px 0; border: none; border-top: 1px solid #ddd;">
        <p style="color: #666; font-size: 12px;">Last Updated: February 23, 2026</p>
    </div>
</body>
</html>
