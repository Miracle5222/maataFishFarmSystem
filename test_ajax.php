<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test AJAX Call</h1>
    <button onclick="testAjax()">Test AJAX for Cottage 22</button>
    <pre id="result"></pre>
    
    <script>
        function testAjax() {
            console.log('Starting AJAX test...');
            $.ajax({
                url: 'handlers/cottage_handler.php?action=get&id=22',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('AJAX Success:', data);
                    document.getElementById('result').innerText = JSON.stringify(data, null, 2);
                    
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
                        console.log('Unique time slots found:');
                        for (let key in timeSlots) {
                            console.log('  ' + key);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    document.getElementById('result').innerText = 'Error: ' + error + '\n' + xhr.responseText;
                }
            });
        }
    </script>
</body>
</html>
