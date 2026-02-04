<!DOCTYPE html>
<html>
<head>
    <title>Simple AJAX Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Cottage Handler Test</h1>
    <button onclick="test()">Test Handler</button>
    <hr>
    <h2>Raw Response:</h2>
    <pre id="rawResponse" style="background: #f5f5f5; padding: 10px; border: 1px solid #ccc;"></pre>
    
    <h2>Parsed Data:</h2>
    <div id="parsed"></div>
    
    <script>
        function test() {
            console.clear();
            console.log('=== Testing AJAX ===');
            
            const url = 'handlers/cottage_handler.php?action=get&id=22';
            console.log('URL:', url);
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'text', // Request as text first to see raw response
                success: function(text) {
                    console.log('Raw response received, length:', text.length);
                    console.log('First 200 chars:', text.substring(0, 200));
                    
                    document.getElementById('rawResponse').textContent = text;
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON successfully');
                        console.log('Cottage ID:', data.id);
                        console.log('Cottage number:', data.cottage_number);
                        console.log('Availability records:', data.availability ? data.availability.length : 0);
                        
                        let html = '<h3>Parsed Successfully!</h3>';
                        html += '<p>Cottage: ' + data.cottage_number + '</p>';
                        html += '<p>Availability records: ' + (data.availability ? data.availability.length : 0) + '</p>';
                        if (data.availability && data.availability.length > 0) {
                            html += '<p><strong>Time slots:</strong></p><ul>';
                            const slots = {};
                            data.availability.forEach(function(slot) {
                                const key = slot.available_time_start + '-' + slot.available_time_end;
                                if (!slots[key]) {
                                    slots[key] = true;
                                    html += '<li>' + slot.available_time_start + ' to ' + slot.available_time_end + '</li>';
                                }
                            });
                            html += '</ul>';
                        }
                        document.getElementById('parsed').innerHTML = html;
                    } catch(e) {
                        console.error('JSON parse error:', e);
                        document.getElementById('parsed').innerHTML = '<p style="color: red;">Error parsing JSON: ' + e.message + '</p>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('HTTP Status:', xhr.status);
                    console.error('Response:', xhr.responseText);
                    document.getElementById('rawResponse').textContent = 'ERROR: ' + status + ' ' + error + '\n\n' + xhr.responseText;
                }
            });
        }
    </script>
</body>
</html>
