import React, { Component } from 'react';
//import Graph from 'Graph.js';

class Graph extends Component {
    constructor(props) {
        super(props);

        this.scaleX = 2;
        this.scaleY = 1;
        this.points = [];
        this.offset = 0;
        this.current = 0;
        this.length = 60 * 30; // 60 frames, 30 sec
    }

    componentDidMount() {
        var container = document.getElementById(this.props.id);

        this.canvas = document.querySelector('#' + this.props.id + ' canvas');
        this.canvas.width = container.offsetWidth * this.scaleX;
        this.canvas.height = container.offsetHeight;

        if (this.canvas.width < 100) {
            return;
        }

        var context = this.canvas.getContext('2d'),
            width = this.canvas.width,
            step = width / this.length;

        context.beginPath();
        context.moveTo(width / this.scaleX, this.canvas.height - this.points[0]);
        context.lineWidth = 1;
        context.strokeStyle = '#b7cff7';

        var app = this;

        this.interval = setInterval(function() {
            if (!app.props.last) {
                return;
            }

            var last = app.props.last;

            app.points.push(last);
            if (last > app.canvas.height * app.scaleY) {
                app.scaleY = last / app.canvas.height;
                app.redraw();
            }

            app.current += 1;
            app.canvas.style.left = - (app.current - app.offset) / app.scaleX + "px";

            context.lineTo(
                (width + app.current - app.offset) / app.scaleX,
                app.canvas.height - app.points[app.points.length - 1] / app.scaleY
            );
            context.stroke();

            app.moveWindow();
        }, 100 * this.scaleX / (6 * step));
    }

    componentWillUnmount() {
        clearInterval(this.interval);
    }

    redraw() {
        var context = this.canvas.getContext('2d'),
            width = this.canvas.width;

        context.closePath();

        context.beginPath();
        context.moveTo(
            (width - this.points.length) / this.scaleX,
            this.canvas.height - this.points[0] / this.scaleY
        );

        for (var i = 1; i < this.points.length; i++) {
            context.lineTo(
                (width - this.points.length + i) / this.scaleX,
                this.canvas.height - this.points[i] / this.scaleY
            );
        }

        context.clearRect(0, 0, this.canvas.width, this.canvas.height);
        context.stroke();
    }

    moveWindow() {
        if (this.offset + this.length < this.current) {
            this.offset = this.current;

            if (this.points.length > this.length) {
                this.points = this.points.slice(this.points.length - this.length);
            }

            this.redraw();
        }
    }

    render() {
        return (
            <div id={this.props.id}><canvas></canvas></div>
        )
    }
}

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
              }
          } else if (result.cmd === 'stat') {
              this.setState({count: result.stat.count, speed: result.stat.speed})
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
                      <Graph id="graph_speed" last={this.state.speed}></Graph>
                      <Graph id="graph_count" last={this.state.count}></Graph>
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
