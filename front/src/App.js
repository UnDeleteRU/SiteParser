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
              } else if (result.status === 'running') {
                  this.setState({status: 'running'});
                  this.draw();
              }
          } else if (result.cmd === 'stat') {
              console.log(result.stat);
              this.last = result.stat.count * 4;
          } else {
              console.log(result);
          }
        };

        this.connection.onopen = function(event) {
          this.send(JSON.stringify({cmd: 'info'}));
        };

        this.parse = this.parse.bind(this);
        this.last = 0;
    }

    draw() {
        var container = document.getElementById('graph_speed');
        var canvas = document.querySelector('#graph_speed canvas');
        var context = canvas.getContext('2d');
        var scale = 2;
        var offset = 0;

        canvas.width = container.offsetWidth * scale;
        canvas.height = container.offsetHeight;

        if (canvas.width < 100) {
          return;
        }

        var width = canvas.width,
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

        var app = this;

        setInterval(function(){
            points.push(app.last);

            current += 1;
            canvas.style.left = - (current - offset) / scale + "px";
            context.lineTo((width + current - offset) / scale, points[points.length - 1]);
            context.stroke();

            moveWindow();
        }, 100 * scale / (6 * step));
    }

    parse(e) {
      this.connection.send(JSON.stringify({cmd: 'parse', site: document.querySelector('[name=site]').value}));
    }

    render() {
        return (
            <div className="App">
              {this.state.status === 'offline' &&
                  <h1>Bot is offline</h1>
              }
              {this.state.status === 'online' &&
                  <div className="site-form-wrap">
                    <input type="text" name="site" />
                    <a className="btn" onClick={this.parse}>Parse</a>
                  </div>
              }
              {this.state.status === 'running' &&
                  <div>
                      <div id="graph_speed"><canvas></canvas></div>
                      <div id="graph_count"><canvas></canvas></div>
                      <div id="result_table"><this.Table /></div>
                  </div>
              }
            </div>
        );
    }

    Table() {
        return <h1>parsing...</h1>
    }
}

export default App;
