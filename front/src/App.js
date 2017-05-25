import React, { Component } from 'react';

class App extends Component {
  constructor() {
      super();
      this.state = {'status': 'offline', site: ''};

      this.connection = new WebSocket('ws://localhost:8080');

      this.connection.onmessage = event => {
          var result = JSON.parse(event.data);

          if (typeof result !== 'object') {
              return;
          }

          if (result.status) {
              if (result.status === 'ready') {
                  this.setState({status: 'online'});
              } else if (result.status === 'nobot') {
                  this.setState({status: 'offline'});
              }
          } else {
              console.log(result);
          }
      };

      this.connection.onopen = function(event) {
          this.send(JSON.stringify({cmd: 'info'}));
      };

      this.parse = this.parse.bind(this);
  }

  draw() {
      var container = document.getElementById('graph');
      var canvas = document.querySelector('#graph canvas');
      var context = canvas.getContext('2d');
      var scale = 2;
      var offset = 0;

      canvas.width = container.offsetWidth * scale;
      canvas.height = container.offsetHeight;

      if (canvas.width < 100) {
          return;
      }

      var width = canvas.width - 50,
          length = 60 * 30, // 60 frames, 30 sec
          step = width / length,
          current = 0;

      var points = [];

      context.beginPath();
      context.moveTo(width / scale, points[0]);
      context.lineWidth = 1;
      context.strokeStyle = '#b7cff7';

      var moveWindow = function () {
          if (offset + length < current) {
              context.closePath();
              offset = current;

              if (points.length > length) {
                  points = points.slice(points.length - length);
              }

              context.beginPath();
              context.moveTo((width - points.length) / scale, points[0]);

              for (var i = 1; i < points.length; i++) {
                  context.lineTo((width - points.length + i) / scale, points[i]);
              }

              context.clearRect(0, 0, canvas.width, canvas.height);
              context.stroke();
          }
      }

      var last = 0;

     setInterval(function(){
        if (Math.random() < 0.05) {
         last = Math.round(Math.random() * 150);
        }

        points.push(last);

        current += 1;
        canvas.style.left = - (current - offset) / scale + "px";
        context.lineTo((width + current - offset) / scale, points[points.length - 1]);
        context.stroke();

        moveWindow();
      }, 100 * scale / (6 * step));
  }

  parse(e) {
      console.log(e, this);
      this.connection.send(JSON.stringify({cmd: 'parse', site: document.querySelector('[name=site]').value}));
  }

    offlineRender() {
        return (
            <h1>Offline</h1>
        )
    }

  render() {
    return (
      <div className="App">
          State: {this.state.status}
          {this.state.status === 'online' &&
              <div>
                <input type="text" name="site" />
                <a className="btn" onClick={this.parse}>Parse</a>
              </div>
          }
          <div id="graph"><canvas></canvas></div>
          <a className="btn" onClick={this.draw}>Draw</a>
      </div>
    );
  }
}

export default App;
