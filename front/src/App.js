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

      setInterval(function(){
          points.push(Math.round(Math.random() * 150));

          current += 1;
          canvas.style.left = - current + "px";
          context.lineTo((width + current) / scale, points[points.length - 1]);
          context.stroke();
      }, 100 / (6 * step));
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
