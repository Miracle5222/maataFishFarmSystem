<?php
// Make sure we're logged in as admin
session_start();

if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect
    header('Location: admin_login.php');
    exit;
}

include 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Debug</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>AJAX Debug - Cottage 22 (Cottage 4)</h1>
    <button onclick="testAjax()">Test AJAX Call for Cottage 22</button>
    <hr>
    <h2>Response:</h2>
    <pre id="response" style="border: 1px solid #ccc; padding: 10px; background: #f5f5f5; max-height: 600px; overflow: auto;"></pre>
    
    <h2>Unique Time Slots Found:</h2>
    <div id="slots"></div>
    
    <script>
        function testAjax() {
            document.getElementById('response').textContent = 'Loading...';
            document.getElementById('slots').textContent = '';
            
            $.ajax({
                url: 'handlers/cottage_handler.php?action=get&id=22',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    document.getElementById('response').textContent = JSON.stringify(data, null, 2);
                    
                    console.log('Data received:', data);
                    console.log('Availability count:', data.availability ? data.availability.length : 0);
                    
                    if (data.availability && data.availability.length > 0) {
                        const timeSlots = {};
                        data.availability.forEach(function(slot) {
                            const key = slot.available_time_start + '-' + slot.available_time_end;
                            if (!timeSlots[key]) {
                                timeSlots[key] = {
                                    start: slot.available_time_start,
                                    end: slot.available_time_end
                                };
                            }
                        });
                        
                        let html = '<ul>';
                        for (let key in timeSlots) {
                            html += '<li>' + timeSlots[key].start + ' to ' + timeSlots[key].end + '</li>';
                        }
                        html += '</ul>';
                        document.getElementById('slots').innerHTML = html;
                        console.log('Unique slots:', Object.keys(timeSlots).length);
                    } else {
                        document.getElementById('slots').innerHTML = '<p style="color: red;">No availability data found</p>';
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById('response').textContent = 'ERROR: ' + error + '\n' + xhr.responseText;
                    console.error('AJAX Error:', error, xhr);
                }
            });
        }
    </script>
</body>
</html>
