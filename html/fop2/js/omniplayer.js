var omniplayerfadeout;
var curplay;

async function loadServerFileIntoFileObject(url) {
  const response = await fetch(url);
  const blob = await response.blob();
  const filename = url.split('/').pop();
  const file = new File([blob], filename, { type: blob.type });
  return file;
}

const play = () => {
  const i = audio.i || 0;

  const done = buffer => {
    audio.file.count = (audio.file.count || 0) + 1;
    audio.play(buffer);
  };

  if (audio.file) {
    if (audio.file.href) {
      fetch(audio.file.href).then(r => r.arrayBuffer()).then(done).catch(e => { $('#omniplayer').fadeOut(); parent.alertify.alert(e.message)});
    }
    else {
      const reader = new FileReader();
      reader.onload = () => {
        done(reader.result);
      };
      reader.readAsArrayBuffer(audio.file);
    }
  }
};

const add = files => {
  audio.i = audio.i || 0;
  audio.files = audio.files || [];

  if (audio.files.length && files.length) {
    audio.i += 1;
  }

  for (const file of files) {
    const n = audio.files.push(file);
  }

  if (audio.files.length) {
    play()
    audio.focus();
  }
};

function doplay(file, title) {
  curplay=title;
  $('.playpause').addClass('fa-play-circle').removeClass('fa-spinner').removeClass('fa-spin');
  $('#omniplayer').fadeIn();
  audio.files = [];
  loadServerFileIntoFileObject(file).then(file => {
      add([file])
  });
  return false;
};

document.addEventListener("DOMContentLoaded", function(event) {

    audio = document.getElementById('omniplayer');
    if(audio===null) return;

    Object.defineProperty(audio, 'file', {
      get() {
        return audio.files ? audio.files[audio.i || 0] : undefined;
      }
    });

    {
        audio.addEventListener('error', e => {
            console.error(e);
            $('#omniplayer').fadeOut();
            parent.alertify.alert(e.detail);
        });
        audio.addEventListener('play', () => { console.log('play'); clearTimeout(omniplayerfadeout); $('#'+curplay).removeClass('fa-play-circle').addClass('fa-spinner').addClass('fa-spin'); });
        audio.addEventListener('ended', () => { console.log('end'); $('#'+curplay).addClass('fa-play-circle').removeClass('fa-spinner').removeClass('fa-spin'); omniplayerfadeout = setTimeout(function() { $('#omniplayer').fadeOut();  },5000);});
        // audio.addEventListener('volumechange', () => { console.log('volume change'); });
    }


    $('#omniplayer').on('hidden.bs.modal', function (e) {
        audio.pause();
    })



});

/* global decoder */

const htime = delta => {
  const days = Math.floor(delta / 86400);
  delta -= days * 86400;
  const hours = Math.floor(delta / 3600) % 24;
  delta -= hours * 3600;
  const minutes = Math.floor(delta / 60) % 60;
  delta -= minutes * 60;
  const seconds = (delta % 60).toFixed(0);

  if (days) {
    return days + ':' + hours + ':' + minutes + ':' + ('0' + seconds).substr(-2);
  }
  else if (hours) {
    return hours + ':' + minutes + ':' + ('0' + seconds).substr(-2);
  }
  else {
    return minutes + ':' + ('0' + seconds).substr(-2);
  }
};

class AudioView extends HTMLElement {
  constructor() {
    super();
    const shadow = this.attachShadow({
      mode: 'open'
    });
    shadow.innerHTML = `
      <style>
        #parent {
          display: flex;
          align-items: center;
          background-color: var(--bg-color, #f1f3f4);
          border-radius: 30px;
          padding: 12px 10px;
          font-size: 13px;
          user-select: none;
          outline: none;
          --height: 5px;
          --progress-bg-color: #000;
        }
        #parent > * {
          margin: 0 5px;
        }
        ::slotted(*),
        #volume,
        #parent > svg {
          cursor: pointer;
          border-radius: 20px;
          padding: 5px;
        }
        ::slotted(*),
        #volume > svg,
        #parent > svg {
          height: 20px;
          width: 20px;
        }
        ::slotted(*:hover),
        #volume:hover,
        #parent > svg:hover {
          background-color: rgba(0, 0, 0, 0.05);
        }
        #progress {
          flex: 1;
        }
        #parent[mode=play] #play path:nth-child(2) {
          display: none;
        }
        #parent:not([mode=play]) #play path:nth-child(1) {
          display: none;
        }
        #parent[mode=nosrc] > svg {
          opacity: 0.2;
          pointer-events: none;
        }
        #volume {
          display: flex;
          align-items: center;
        }
        #sound {
          width: 0;
          padding-left: 5px;
          --percent: 50%;
          transition: width .5s;
          position: relative;
        }
        #volume:hover > #sound {
          width: 48px;
        }
        #volume:hover > #sound:after {
          content: '';
          position: absolute;
          left: 50%;
          top: 0;
          width: 1px;
          height: 5px;
          background-color: #ccc;
          z-index: 1;
          pointer-events: none;
        }
      </style>
      <div id="parent" mode="nosrc" data-ready=false tabindex="-1">
        <slot name="before-play"></slot>
        <svg id="play" viewBox="0 0 48 48">
          <path d="M16 10v28l22-14z"/>
          <path d="M12 38h8V10h-8v28zm16-28v28h8V10h-8z"/><path d="M0 0h48v48H0z" fill="none"/>
          <title>Use Space to toggle play and pause</title>
        </svg>
        <slot name="before-stat"></slot>
        <span id="current">0:00</span>
        <progress-view id="progress" title="Use Arrow Left/Right to seek 10 seconds backward/forward
Use Meta + Arrow Left/Right to seek 30 seconds backward/forward"></progress-view>
        <span id="duration">0:00</span>
        <slot name="before-sound"></slot>
        <div id="volume" title="Use Arrow Up/Down to increase/decrease 10%
Use Meta + Arrow Up/Down to increase/decrease 30%">
          <progress-view id="sound"></progress-view>
          <svg viewBox="0 0 48 48">
            <path d="M14 18v12h8l10 10V8L22 18h-8z"/><path d="M0 0h48v48H0z" fill="none"/>
          </svg>
        </div>
      </div>
    `;
    this.stat = {
      start: 0,
      stop: 0
    };
  }
  connectedCallback() {
    const progress = this.shadowRoot.getElementById('progress');
    progress.addEventListener('seek', e => {
      if (this.audioBuffer) {
        const offset = this.audioBuffer.duration * e.detail / 100;
        this.currentTime = offset;
        progress.seek(e.detail);
      }
    });
    const play = this.shadowRoot.getElementById('play');
    play.addEventListener('click', () => this.toggle());
    let id;
    const parent = this.shadowRoot.getElementById('parent');
    this.addEventListener('pause', () => {
      clearInterval(id);
      parent.setAttribute('mode', 'play');
    });
    const current = this.shadowRoot.getElementById('current');
    this.addEventListener('play', () => {
      parent.setAttribute('mode', 'pause');
      clearInterval(id);
      id = setInterval(() => {
        const p = this.currentTime / this.audioBuffer.duration * 100;
        progress.seek(p);

        current.textContent = htime(this.currentTime);

        this.dispatchEvent(new Event('progress'));
      }, 200);
    });
    this.addEventListener('ended', () => progress.seek(100));

    this.addEventListener('keydown', e => {
      if (e.code === 'KeyB' && (e.metaKey || e.ctrlKey) && e.shiftKey) {
        this.currentTime = 0;
      }
      else if (e.code === 'Space') {
        this.toggle();
      }
      else if (e.code === 'ArrowRight') {
        const shift = (e.metaKey || e.ctrlKey) ? 30 : 10;
        if (this.currentTime + shift < this.audioBuffer.duration) {
          this.currentTime += shift;
        }
      }
      else if (e.code === 'ArrowLeft') {
        const shift = (e.metaKey || e.ctrlKey) ? 30 : 10;
        if (this.currentTime - shift > 0) {
          this.currentTime -= shift;
        }
      }
      else if (e.code === 'ArrowUp') {
        this.volume += (e.metaKey || e.ctrlKey) ? 0.3 : 0.1;
        this.volume = Math.round(this.volume * 10) / 10;
      }
      else if (e.code === 'ArrowDown') {
        this.volume -= (e.metaKey || e.ctrlKey) ? 0.3 : 0.1;
        this.volume = Math.round(this.volume * 10) / 10;
      }
    });

    const sound = this.shadowRoot.getElementById('sound');
    sound.addEventListener('seek', e => {
      this.volume = e.detail / 50;
    });
  }
  static get observedAttributes() {
    return ['src'];
  }
  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'src' && newValue) {
      const parent = this.shadowRoot.getElementById('parent');
      parent.setAttribute('mode', 'play');
    }
    if (name === 'src') {
      if (this.source) {
        this.reset();
      }
    }
  }
  reset() {
    this.pause();
    delete this.audioBuffer;
    delete this.context;
    delete this.meta;
    this.stat.start = 0;
    this.stat.end = 0;
    this.shadowRoot.getElementById('progress').seek(0);
    this.shadowRoot.getElementById('current').textContent = '0:00';
    this.shadowRoot.getElementById('duration').textContent = '0:00';
  }
  async play(arrayBuffer) {
    const session = this.session = Math.random();
    if (arrayBuffer) {
      this.reset();
    }
    const next = () => {
      if (session !== this.session) {
        return console.log('skipped');
      }

      const {context, audioBuffer} = this;
      const source = this.source = context.createBufferSource();

      source.onended = () => {
        if (this.currentTime >= audioBuffer.duration) {
          this.pause();
          this.dispatchEvent(new Event('ended'));
        }
      };

      source.buffer = audioBuffer;
      this.shadowRoot.getElementById('duration').textContent = htime(audioBuffer.duration);

      const sound = this.sound = context.createGain();
      sound.gain.value = this.volume;
      sound.connect(context.destination);
      source.connect(sound);

      const offset = this.currentTime >= audioBuffer.duration ? 0 : this.currentTime;
      context.resume();
      source.start(0, offset);
      this.stat.start = context.currentTime - offset;

      this.dispatchEvent(new Event('play'));
    };
    if (this.audioBuffer && this.context) {
      return next();
    }
    else {
      const parent = this.shadowRoot.getElementById('parent');
      arrayBuffer = arrayBuffer || await fetch(this.getAttribute('src')).then(r => r.arrayBuffer());

      try {
        parent.setAttribute('mode', 'nosrc');
        const decoded = await decoder.decode({
          name: 'input',
          arrayBuffer
        });
        Object.assign(this, decoded);
      }
      catch (e) {
        console.log('FFmpeg Decoder Failed', e);
        try {
          const context = new AudioContext();
          const audioBuffer = await context.decodeAudioData(arrayBuffer);
          Object.assign(this, {
            context,
            audioBuffer,
            meta: {}
          });
        }
        catch (ee) {
          this.reset();
          return this.dispatchEvent(new CustomEvent('error', {
            detail: e.message
          }));
        }
      }
      this.dispatchEvent(new Event('loadedmetadata'));
      next();
    }
  }
  pause() {
    const {source, context} = this;
    if (source) {
      delete this.source;
      context.suspend();
      source.disconnect();
      this.stat.stop = context.currentTime - this.stat.start;
      source.stop();
      this.dispatchEvent(new Event('pause'));
    }
  }
  toggle() {
    this[this.source ? 'pause' : 'play']();
  }
  get currentTime() {
    if (this.source && this.context.state === 'running') {
      return this.context.currentTime - this.stat.start;
    }
    else {
      return this.stat.stop;
    }
  }
  set currentTime(v) {
    if (this.source) {
      this.pause();
    }
    this.stat.stop = Math.max(0, Math.min(this.audioBuffer.duration, v));
    this.play();
  }
  get volume() {
    return this.shadowRoot.getElementById('sound').percent / 50;
  }
  set volume(v) {
    v = Math.max(0, Math.min(2, v));
    this.shadowRoot.getElementById('sound').seek(v * 50);
    if (this.sound) {
      this.sound.gain.value = v;
    }
    this.dispatchEvent(new Event('volumechange'));
  }
}
window.customElements.define('audio-view', AudioView);
class ProgressView extends HTMLElement {
  constructor() {
    super();
    const shadow = this.attachShadow({
      mode: 'open'
    });
    shadow.innerHTML = `
      <style>
        #parent {
          background-color: var(--box-bg-color, #eaeaea);
          height: var(--height, 2px);
          display: flex;
          position: relative;
          cursor: pointer;
          border-radius: 5px;
        }
        #parent * {
          pointer-events: none;
        }
        #progress {
          background-color: var(--progress-bg-color, #8594ff);
          width: var(--percent, 0%);
          z-index: 1;
          border-radius: 5px;
        }
        .buffer {
          background-color: var(--buffer-bg-color, #ccc);
          position: absolute;
          height: 100%;
        }
      </style>
      <div id="parent">
        <span id="progress"></span>
      </div>
    `;
  }
  addBuffer(range) {
    const span = document.createElement('span');
    span.classList.add('buffer');
    span.style.width = range.width + '%';
    span.style.left = range.start + '%';
    this.shadowRoot.getElementById('parent').appendChild(span);
  }
  seek(percent) {
    this.shadowRoot.getElementById('progress').style.setProperty('--percent', percent + '%');
  }
  connectedCallback() {
    this.shadowRoot.getElementById('parent').addEventListener('click', e => {
      const {width} = e.target.getBoundingClientRect();
      this.dispatchEvent(new CustomEvent('seek', {
        detail: e.offsetX / width * 100
      }));
    });
  }
  get percent() {
    return parseInt(getComputedStyle(this.shadowRoot.getElementById('progress')).getPropertyValue('--percent'));
  }
}
window.customElements.define('progress-view', ProgressView);


