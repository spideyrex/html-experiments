<?php ob_start(); ?>
<html>
  <head>
    <?php include('../../php/head.php'); writeHead('A/V Loop', 'A synchronized WebAudio audio/visual experiment', 'http://cacheflowe.com/code/html/experiment/av-loop/preview.gif'); ?>

    <link rel="stylesheet" href="../../stylesheets/font-awesome.min.css" type="text/css" />
    <style>
      html, body {
        background: #000;
        overflow: hidden;
        text-align: center;
        top: 0;
        left: 0;
      }
      canvas {
        background: black;
      }
      button.play {
        position: absolute;
        left: 50%;
        top: 50%;
        margin-left: -5rem;
        margin-top: -5rem;
        color: #fff;
        border: 0;
        background: transparent;
        font-size: 10rem;
        z-index: 3;
        width: 10rem;
        height: 10rem;
        padding: 0;
      }
      #debug {
        color: white;
        text-align: left;
        position: absolute;
        top: 0;
        left: 0;
        display: none;
      }
    </style>
  </head>
  <body>
    <header>
  		<h1>A/V Loop</h1>
  	</header>
    <div id="debug"></div>
    <!-- <audio src="my-recording-2-loop-normalized.wav" loop autoplay></video> -->
    <!-- <video src="render-2015-11-10-08-46-20-with-sound.mov" loop autoplay></video> -->
    <canvas id="draw-canvas" class="full-width" width="400" height="400"></canvas>
    <button class="play fa fa-play-circle fa-3"></button>
    <script>
      document.ontouchmove = function(e) { e.preventDefault(); };

      var debug = document.getElementById('debug');
      var startTime = 0;
      var audioTimeOffset = 0.1; // helps with the visual timing
      var ticks = 64;
      var tickTime = 0;
      var curTick = 0;
      var lastTick = 0;

      // perfect webaudio looping from: http://stackoverflow.com/questions/29882907/how-to-seamlessly-loop-sound-with-web-audio-api
      var url = 'my-recording-2-loop-normalized.wav';
      var vol = 1.0;
      // var url = 'data:audio/wav;base64,';
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      var context = new AudioContext();
      var source = context.createBufferSource();
      var gainNode = context.createGain();
      source.connect(gainNode);
      gainNode.gain.value = vol;
      gainNode.connect(context.destination);

      var request = new XMLHttpRequest();
      request.open('GET', url, true);
      request.responseType = 'arraybuffer';
      request.onload = function() {
        context.decodeAudioData(request.response, function(response) {
          source.buffer = response;
          console.log('playing:');
          console.log('source.buffer.duration', source.buffer.duration);
          console.log('context.currentTime', context.currentTime);
        }, function () { console.error('The request failed.'); } );
      }
      request.send();

      function playAudio(){
        source.start(0);
        source.loop = true;
        console.log(source);
        startTime = context.currentTime;
        tickTime = source.buffer.duration / ticks;
        requestAnimationFrame(animate);
        document.querySelector('button.play').style.display = 'none';
      }
      document.addEventListener('touchend', playAudio);
      document.addEventListener('mouseup', playAudio);

      // setup draw canvas
      var canvasDraw = document.getElementById('draw-canvas');
      canvasDraw.width = window.innerWidth;
      canvasDraw.height = window.innerHeight;
      var ctx = canvasDraw.getContext('2d');

      // stupid simple particle object
      function Particle() {
        var _x = 0,
            _y = 0,
            _xLast = 0,
            _yLast = 0,
            _size = 0,
            _color = '',
            _rads = 0;
        return {
          reset: function(x, y, size, color){
            _x = x;
            _y = y;
            _xLast = x;
            _yLast = y;
            _size = size;
            _color = color;
            _rads = Math.PI/2; // Math.PI/4 * Math.floor(Math.random() * 8)

            // drop a square on the beat
            ctx.strokeStyle = _color;
            ctx.lineWidth = 3;
            ctx.save();
            ctx.beginPath();
            size = size * 7;
            ctx.translate(_x - size/2, _y - size/2);
            if(Math.random() > 0.3) ctx.rotate(Math.PI / 4);
            ctx.rect(0, 0, size, size);
            ctx.stroke();
            ctx.restore();
          },
          active: function(){
            return _size > 0.001;
          },
          update: function(){
            _size *= 0.999;
            _x += Math.sin(_rads) * 10;
            _y += Math.cos(_rads) * 10;
            if(_x < 0) _x = ctx.canvas.width;
            if(_x > ctx.canvas.width) _x = 0;
            if(_y < 0) _x = ctx.canvas.height;
            if(_y > ctx.canvas.height) _y = 0;
            if(Math.abs(_xLast - _x) < 50 && Math.abs(_yLast - _y) < 50) {
              ctx.strokeStyle = _color;
              ctx.lineWidth = _size;
              ctx.beginPath();
              ctx.moveTo(_xLast, _yLast);
              ctx.lineTo(_x, _y);
              ctx.stroke();
            }
            _xLast = _x;
            _yLast = _y;
          },
          beat: function(likeliness) {
            if(Math.random() < likeliness) {
              _rads += (Math.random() > 0.5) ? Math.PI/4 : Math.PI/-4; // Math.PI/4 * Math.floor(Math.random() * 8)
            }
          }
        };
      }
      var particles = [];


      function newParticle(newX, newY, color) {
        var foundParticle = false;
        for(var i=0; i < particles.length; i++) {
          if(particles[i].active() == false) {
            foundParticle = true;
            return particles[i];
            break;
          }
        }
        if(foundParticle == false) {
          var newParticle = new Particle();
          particles.push(newParticle);
          return newParticle;
        }
      }


      function animate() {
        requestAnimationFrame(animate);
        if(source.buffer) {
          // calculate audio timing
          var playbackTime = (context.currentTime - startTime) % source.buffer.duration + audioTimeOffset;
          curTick = Math.floor(playbackTime / tickTime) % ticks;
          debug.innerHTML =
            'context.currentTime = ' + playbackTime + '<br>' +
            'tick = ' + curTick;


          // draw fading overlay
          ctx.beginPath();
          ctx.fillStyle = "rgba(0,0,0,0.05)";
          ctx.rect(0, 0, canvasDraw.width, canvasDraw.height);
          ctx.fill();

          // update active particles
          for(var i=0; i < particles.length; i++) {
            if(particles[i].active() == true) {
              particles[i].update();
            }
          }

          // find a dead particle, or add a new one
          var newX = ((ctx.canvas.width / ticks) * (curTick * 2)) % ctx.canvas.width;

          if(curTick != lastTick) {
            // turn on the beat
            for(var i=0; i < particles.length; i++) {
              if(particles[i].active() == true) {
                if(curTick % 4 == 2) particles[i].beat(0.25);
                if(curTick % 8 == 4) particles[i].beat(0.75);
              }
            }

            // random particles
            // newParticle().reset(newX, ctx.canvas.height * Math.random(), 2, 'rgba(255,255,255,0.3)');
            // hats
            if(curTick % 4 == 2) {
              newParticle().reset(newX, ctx.canvas.height * 0.2, 10, 'rgba(255,255,255,1)');
            }
            // smashes
            if(curTick <= 5) {
              newParticle().reset(newX, ctx.canvas.height * 0.6, 10, 'rgba(100,200,150,1)');
            }
            // snare
            if(curTick % 8 == 4) {
              // full flash
              ctx.beginPath();
              ctx.fillStyle = "rgba(255,255,255,0.2)";
              ctx.rect(0, 0, canvasDraw.width, canvasDraw.height);
              ctx.fill();
              // line
              newParticle().reset(newX, ctx.canvas.height * 0.4, 10, 'rgba(190,200,250,1)');
            }
            // kick
            if(curTick % 32 == 0 || curTick % 32 == 5 || curTick % 32 == 7 || curTick % 32 == 8 || curTick % 32 == 21 || curTick % 32 == 23) {  // repeats for both measures, hence % 32 for 64 ticks
              // flash bar
              var flashW = 20 + Math.random() * 100;
              var flashH = canvasDraw.height * 3;
              ctx.beginPath();
              ctx.save();
              ctx.translate(newX, ctx.canvas.height * 0.8);
              (Math.random() > 0.5) ? ctx.rotate(Math.PI / 4) : ctx.rotate(-Math.PI / 4);
              ctx.fillStyle = ctx.strokeStyle = "rgba(235,255,235,0.3)";
              ctx.lineWidth = 2;
              ctx.rect(-flashW/2, -flashH/2, flashW, flashH);
              (Math.random() > 0.5) ? ctx.fill() : ctx.stroke();
              ctx.restore();

              // line
              newParticle().reset(newX, ctx.canvas.height * 0.8, 10, 'rgba(190,200,250,1)');
            }
          }

          lastTick = curTick;
        }
      }
    </script>
  </body>
</html>
<?php file_put_contents('./index.html', ob_get_contents()); ob_end_flush(); ?>
