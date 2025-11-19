var html5Wav = !!(document.createElement('audio').canPlayType && document.createElement('audio').canPlayType('audio/wav; codecs="1"').replace(/no/, ''));
soundObject = new Object;

function debug(message) {
    if(window.console !== undefined) {
        console.log(message);
    }
};

audioExtension();

function audioExtension() {

    var myAudio = document.createElement('audio');

    if (myAudio.canPlayType('audio/ogg')) {
        audioExt='ogg';
        html5Wav = true;
    }

    if (myAudio.canPlayType('audio/mp3')) {
        audioExt='mp3';
        html5Wav = true;
    }

    return audioExt;
}

function TinyWav(pid, trigger, icon) {

    this.pid = pid;
    this.State = "STOPPED";
    this.initCnt = 0;
    this.player = undefined;
    this.playlist = [];
    this.trigger_buffer = trigger;
    this.doplaylist = false;
    this.icon = icon;

    // If one string passed -- 
    //         Stop any current playback, clear playlist and run play of only file
    // If no argument passed --
    //        Start/resume playback of playlist
    // If list passed --
    //        replace playlist with it, and start playback of it
    this.Play = function(file,icon) {
        if(icon !== null) { 
            this.icon = icon;
        } 
        var player = this.getPlayer();
        if (!file) {
            this.doplaylist = true;
            if (this.State != "STOPPED" || !this.playlist.length)
                return;
            file = this.playlist[0];
        } else 
        if (typeof file == "object") {
            this.playlist = file;
            this.doplaylist = true;
            if (this.State != "STOPPED" || !this.playlist.length)
                return;
            file = this.playlist[0];
        } else {
            this.doplaylist = false;
        }
        this.Stop();
        player.doPlay(file, this.trigger_buffer);
    }
    // Add file(s) in playlist; does not starts playback
    this.Enqueue = function(file) {
        if (typeof file == "object") {
            this.playlist = this.playlist.concat(file);
        }
        else if (file) {
            if (!this.playlist || !this.playlist.length)
                this.playlist = [file];
            else
                this.playlist[this.playlist.length] = file;
        }
    }
    // Stop playback
    this.Stop = function () {
        var player = this.getPlayer();
        player.doStop();
    }
    // Pause playback
    this.Pause = function (file) {
        var player = this.getPlayer();
        player.doPause();
    }
    // Continue playback
    this.Resume = function () {
        var player = this.getPlayer();
        player.doResume();
    }
    // Advance to next playlist track
    this.Next = function() {
        var player = this.getPlayer();
        if(this.playlist.length) this.playlist.shift();
        if (!this.playlist.length)
            return;
        file = this.playlist[0];
        player.doStop();
        player.doPlay(file, this.trigger_buffer);
    }
    // ============= END OF API ==========
    // Find player object in page
    this.getPlayer = function() {
        if(this.player!=undefined) return this.player;
        var obj = $('#'+this.pid);
        if (obj.length==0) { return null; }
        if (obj[0].doPlay) {
            this.player = obj[0];
            return obj;
        } 
        for(i=0; i<obj[0].childNodes.length; i++) {
            var child = obj[0].childNodes[i];
            if (child.tagName == "EMBED") {
                this.player = child;
                return child;
            }
        }
    }

    this.SoundState = function (state, position) {
        if (position != undefined) this.SoundPos = position;
        if (this.State == "PLAYING" && state=="STOPPED" && this.doplaylist) {
            window.setTimeout((function(t){ 
                return function(){ t.Next(); };
            })(this), 50);
        }
        this.State = state;

        if($('#'+this.icon).length == 0) { return; } 

        if(state == "PLAYING") {
            $('#'+this.icon).removeClass('playicon');
            $('#'+this.icon).addClass('pauseicon');
        } else if(state=="STOPPED") {
            $('#'+this.icon).addClass('playicon');
            $('#'+this.icon).removeClass('pauseicon');
        } else if(state=="PAUSED") {
            $('#'+this.icon).addClass('playicon');
            $('#'+this.icon).removeClass('pauseicon');
        }
    }
    this.init = function (icon) {
        this.icon = icon;
        var player = this.getPlayer();
        this.initCnt++;
        if (!player || !player.attachHandler) {
            if (this.initCnt < 50) {
                setTimeout((function(t){ return function(){ return t.init(icon); } })(this), 100); // Wait for load
            }
        } else {
            player.attachHandler("PLAYER_BUFFERING", "TinyWavSoundState", "BUFFERING_"+this.pid);
            player.attachHandler("PLAYER_PLAYING", "TinyWavSoundState", "PLAYING_"+this.pid);
            player.attachHandler("PLAYER_STOPPED", "TinyWavSoundState", "STOPPED_"+this.pid);
            player.attachHandler("PLAYER_PAUSED", "TinyWavSoundState", "PAUSED_"+this.pid);
        }
    }
}

function TinyWavSoundState() {

    var partes = arguments[0].split("_");
    var estado = partes.shift();
    var elemento = partes.join("_");
    var iconid;

    var passed_state = arguments[1];
    var html = arguments[2];

    if(soundObject[elemento]===undefined) {
       iconid = elemento;
    } else { 
       iconid = soundObject[elemento].icon;
    }

    debug("tinywav sound state "+estado+" elemento "+elemento+" icon id "+iconid);

    if(iconid!='') {
        if(estado=="PLAYING") {
            $('#'+iconid).addClass('playing');
            $('#'+iconid).removeClass('loading');
            $('#'+iconid).removeClass('error');
            $('#'+iconid).removeClass('paused');
        } else if(estado=="PAUSED") {
            $('#'+iconid).removeClass('playing');
            $('#'+iconid).removeClass('loading');
            $('#'+iconid).removeClass('error');
            $('#'+iconid).addClass('paused');
        } else if(estado=="BUFFERING") {
            $('#'+iconid).removeClass('pauseicon');
            $('#'+iconid).removeClass('playicon');
            $('#'+iconid).removeClass('error');
            $('#'+iconid).addClass('loading');
            $('#'+iconid).removeClass('paused');
        } else if(estado=="STOPPED") {
            $('#'+iconid).removeClass('playing');
            $('#'+iconid).removeClass('error');
            $('#'+iconid).removeClass('loading');
            $('#'+iconid).removeClass('paused');
        } else if(estado=="ERROR") {
            $('#'+iconid).removeClass('playing');
            $('#'+iconid).removeClass('loading');
            $('#'+iconid).addClass('error');
            $('#'+iconid).removeClass('paused');
        }
    }
}

jQuery( document ).ready(function( $ ) {

   if(html5Wav===false) {

      debug("Lack of HTML Wav Support, falling back AUDIO to flash wavplayer");
      var vars = {}; var params = {'scale': 'noscale', 'bgcolor': '#FFFFFF'};

    } else {

    }

    soundObject['tinyblock'] = new TinyWav('tinyblock',2);
    var Player = document.createElement("div");
    $(Player).css('display','block').attr('id','tinyblock');
    document.body.appendChild(Player);

    var vars = {}; var params = {'scale': 'noscale', 'bgcolor': '#FFFFFF'};
    if(typeof(swfobject)!='undefined') {
      swfobject.embedSWF("wavplayer.swf?gui=none", "tinyblock", "1", "1", "10.0.32.18", "embed/expressInstall.swf", vars, params, params);
    }
    debug('antes de tinyblock init');
    soundObject['tinyblock'].init();
    debug('despues de tinyblock init');

    var Player2 = document.createElement("audio");
    $(Player2).css('display','block').attr('id','audioblock');
    //$(Player2).attr('src','');

    debug('add event listener on player2! '+$(this).attr('data-icon'));
    debug('add event listener on player2! '+$(this).attr('data-icon'));
    debug('add event listener on player2! '+$(this).attr('data-icon'));
    debug('add event listener on player2! '+$(this).attr('data-icon'));
    debug('add event listener on player2! '+$(this).attr('data-icon'));

    Player2.addEventListener("play",    function() { TinyWavSoundState("PLAYING_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("playing", function() { TinyWavSoundState("PLAYING_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("pause",   function() { TinyWavSoundState("PAUSED_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("ended",   function() { TinyWavSoundState("ENDED_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("error",   function() { TinyWavSoundState("ERROR_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("load",    function() { TinyWavSoundState("LOAD_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("abort",   function() { TinyWavSoundState("ABORT_"+$(this).attr('data-icon'));}, true);
    Player2.addEventListener("oncanplay",        function() { debug('on can play');}, true);
    Player2.addEventListener("onloadeddata",     function() { debug('on loaded');}, true);
    Player2.addEventListener("oncanplaythrough", function() { debug('on can play th ');}, true);
    document.body.appendChild(Player2);

});

function soundPlay(soundplayer,soundfile,iconfile) {

    debug("comienzo sound play player: "+soundplayer+", file "+soundfile+", icon "+iconfile);
    var partes      = soundfile.split(".");
    var soundFormat = partes.pop();
    var basename    = partes.join(".");
    var extension   = soundFormat;

    var playHtml5 = html5Wav;
    debug('play html5 = '+playHtml5);

    if(soundFormat=='gsm' || soundFormat=="au" || soundFormat=="raw") {
        soundplayer = $('#TinyWavBlock');
        $('#TinyWavBlock').attr('data-icon',iconfile);
        playHtml5=false;
    }

    if(soundFormat=="mp3" || soundFormat=="ogg") {
        soundFormat=audioExtension();
        soundfile=basename+'.'+soundFormat;
    }

    debug("sound play file "+soundfile);

    if(playHtml5===false) {
        debug("usar tinywav "+soundfile+' player '+soundplayer);
        $('#'+soundplayer).attr('data-icon',iconfile);
        if($('#'+iconfile).hasClass('playing')) {
            debug('soundengine estaba playing, lo pongo en pausa');
            soundObject[soundplayer].Pause(soundfile,iconfile); 
        } else {
            if($('#'+iconfile).hasClass('paused')) {
                debug('soundengine tenia clase paused, hago un resume');
                soundObject[soundplayer].Resume(soundfile,iconfile); 
            } else {
                debug('soundengine empiezo un play');
                $('.audioButton').removeClass('playing loading'); 
                soundObject[soundplayer].Play(soundfile,iconfile); 
            }
        }
    } else {
        debug("usar html5 "+soundfile+" player "+soundplayer+" icon "+iconfile);
        if($('#'+soundplayer)[0].src.indexOf(soundfile)==-1) {
            debug("source distinto "+$('#'+soundplayer)[0].src+" distinto de "+soundfile);
            $('#'+soundplayer)[0].src=soundfile;
            $('#'+soundplayer).attr('data-icon',iconfile);
            $('#'+soundplayer)[0].load();
            debug($('#'+soundplayer)[0]);
        } else {
           debug("el mismo source!");
           $('#'+soundplayer)[0].load();
        }

        try {
            debug('try play');

            if($('#'+iconfile).hasClass('playing')) {
               debug('has pause');
                $('#'+soundplayer)[0].pause();
            } else {
               debug('do not pause');
               $('.audioButton').removeClass('playing loading'); 
               $('#'+soundplayer)[0].play();
            }
        } catch (e) {
            debug('catch error play');
            $('#'+soundplayer)[0].currentTime = 0.01;
            debug($('#'+soundplayer));
            $('#'+soundplayer)[0].play();
        }
        /*var source = document.createElement( 'source' );
        source.src = soundfile;
        if(extension=="mp3") {
           source.type = "audio/mpeg";
        } else if (extension=="ogg") {
           source.type = "audio/ogg; codecs='vorbis'";
        } else if (extension=="wav") {
           source.type = "audio/wav; codecs='1'";
        }
        $(soundplayer).appendChild( source );
*/

    }
}
