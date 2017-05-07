<html>

<body>
<input type="text" name="site">
<button>Parse</button>
<script>
    var connection = new WebSocket('ws://localhost:8080');

    connection.onmessage = function (event) {
        console.log(JSON.parse(event.data));
    };

    connection.onopen = function(event) {
        connection.send(JSON.stringify({cmd: 'info'}));
    };

    var button = document.querySelector('button');
    button.addEventListener('click', function () {
        connection.send(JSON.stringify({cmd: 'parse', site: document.querySelector('[name=site]').value}));
    });
</script>
</body>
</html>
