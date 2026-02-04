<!DOCTYPE html>
<html>
<head>
    <title>Direct AJAX Test</title>
</head>
<body>
    <h1>Test Direct AJAX Call</h1>
    <button onclick="testDirect()">Test Direct Call</button>
    <button onclick="testJQuery()">Test jQuery Call</button>
    <div id="result"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function testDirect() {
            console.log('Testing direct fetch...');
            fetch('handlers/cottage_handler.php?action=get&id=22')
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON:', data);
                        console.log('Availability count:', data.availability ? data.availability.length : 'NO AVAILABILITY');
                    } catch(e) {
                        console.error('JSON parse error:', e);
                    }
                })
                .catch(error => console.error('Fetch error:', error));
        }
        
        function testJQuery() {
            console.log('Testing jQuery AJAX...');
            $.ajax({
                url: 'handlers/cottage_handler.php?action=get&id=22',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('AJAX Success:', data);
                    document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr.responseText);
                    document.getElementById('result').innerHTML = '<pre>Error: ' + error + '\n' + xhr.responseText + '</pre>';
                }
            });
        }
    </script>
</body>
</html>
