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

      this.parse = this.parse.bind(this)
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

      </div>
    );
  }
}

export default App;
