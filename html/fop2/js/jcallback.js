/* Callback functions for events received by fop2server

Some of the interesting events you can intercept are:

agentconnect, astdbcust, chat, clearentries, details, devtype, direction
ip, leavingvoicemail, link, lock, managerproblem, members, Monitor, note
notifybroadcast, notifyconnect, notifyringing, notioffline, notionline
PauseMonitor, presence, qualify, queueentry, queuemembers, reload, rename
settext, settimer, setvar, smsfail, smsok, state, StopMonitor, unlock
UnpauseMonitor, usersonline, vmaildetail, voicemail, voicemailcount,
waitingcalls, xstatus

Every event has 3 parameters:

nro   = button number in grid
data  = variable data depending on event
slot  = line number on button if applicable

*/

function jCallBack() {
    function reload(nro,data,slot) {
        debug("reload inside jCallBack");
    }

    function link(nro,data,slot) {
        debug("received link command from server with "+data+", button number"+nro+" in slot "+slot);
    }

    this.reload  = reload;
    this.link    = link;
}

mycallback = new jCallBack();
