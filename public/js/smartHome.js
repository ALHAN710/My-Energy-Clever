
/*var print_first = pipe('#first', function(data) {
    message = new Paho.MQTT.Message(data);
    //message.destinationName = "test/all";
    message.destinationName = "to/device/esp/3164842";
    console.log("SEND ON " + message.destinationName + " PAYLOAD " + data);
    client.send(message);
});*/

var isConnected = false;
var wsbroker = "portal-myenergyclever.com";//"127.0.0.1";//"192.168.10.40";//location.hostname;  //mqtt websocket enabled broker
var wsport = 15675; //9001;// port for above 1883

var client = new Paho.MQTT.Client(wsbroker, wsport, "/ws", "user_" + $('#usr').val() + '_' + parseInt(Math.random() * 100, 10));

client.onConnectionLost = function (responseObject) {
    isConnected = false;
    console.log("CONNECTION LOST - " + responseObject.errorMessage);
    // Log disconnection state
    console.log("Disconnected");
    contextLedHome.strokeStyle = "red";
    contextLedHome.stroke();
    contextLedHome.fillStyle = "red";
    contextLedHome.fill();
    resetLedConnexionStatus();

};

client.onMessageArrived = function (message) {
    console.log("RECEIVE ON " + message.destinationName + " PAYLOAD " + message.payloadString);
    //print_first(message.payloadString);
    var result;
    var parse = true;
    // Print out our received message
    //console.log("type of message.payloadString: " + typeof message.payloadString);
    //console.log("Received: " + message.payloadString);
    var str = String(message.payloadString);
    //console.log("str: " + str);
    if (str.indexOf("{\"From\":") >= 0) {
        var json;
        try {
            json = JSON.parse(message.payloadString);
            //result = JSON.parse(message.payloadString);
        } catch (e) {
            parse = false;
            console.error("Parsing error:", e);
        }
        //console.log("json : " + json);
        //$("#" + json.To).prop('checked', parseInt(json.message));
        //$("#" + key).closest("label").addClass("checked");
        //console.log(parseInt(json.message));
        var status;
        if (parse) {
            switch (json.To) {
                case "user":
                    if (json.Object === "Connection Status") {
                        contextLedHome.strokeStyle = "red";
                        contextLedHome.stroke();
                        contextLedHome.fillStyle = "red";
                        contextLedHome.fill();
                    }
                    else if (json.Object === "Device Output Status") {
                        status = (parseInt(json.message) === 1) ? true : false;
                        console.log("status = " + status)
                        //$('#' + json.To).closest("label").toggleClass("checked", status);
                        $("#" + json.From).prop('checked', status);
                        if (status) {
                            $('[data-unit="' + json.From + '"]').addClass("active");
                            $("#" + json.From).closest("label").addClass("checked");
                            console.log("status True ")
                        }
                        else {
                            $('[data-unit="' + json.From + '"]').removeClass("active");
                            $("#" + json.From).closest("label").removeClass("checked");
                            console.log("status False ")
                        }

                        var led = json.From;

                        //alert(led);
                        //console.log(led);
                        if (context[led]) {
                            //console.log(context[led]);
                            context[led].strokeStyle = "green";
                            context[led].stroke();
                            context[led].fillStyle = "green";
                            context[led].fill();

                        }
                    }
                    else if (json.Object === "Device Connexion Status") {
                        var led = json.From;

                        //alert(led);
                        //console.log(led);
                        if (context[led]) {
                            //console.log(context[led]);
                            context[led].strokeStyle = "green";
                            context[led].stroke();
                            context[led].fillStyle = "green";
                            context[led].fill();

                        }

                        //Get the device's output status
                        // mess.Object = "Device Output Status";
                        // mess.To = "Devices";
                        // doSend(JSON.stringify(mess));

                        //Get the ac-device's remaining time
                        // mess.Object = "Remaining Time";
                        // mess.To = "Devices";
                        // doSend(JSON.stringify(mess));

                    }
                    else if (json.Object === "Connected Devices") {
                        arr = json.message;
                        resetLedConnexionStatus();
                        $.each(arr, function (key, value) {
                            //alert(value);
                            //console.log(value);
                            if (context[value]) {
                                //console.log(context[value]);
                                context[value].strokeStyle = "green";
                                context[value].stroke();
                                context[value].fillStyle = "green";
                                context[value].fill();
                            }
                        });
                        // //Get the device's output status
                        // mess.Object = "Device Output Status";
                        // mess.To = "Devices";
                        // doSend(JSON.stringify(mess));
                    }
                    else if (json.Object === "Disconnected Device") {
                        var led = json.message;
                        if (context[led]) {
                            context[led].strokeStyle = "red";
                            context[led].stroke();
                            context[led].fillStyle = "red";
                            context[led].fill();
                        }

                        $('[data-unit="' + json.message + '"]').removeClass("active");
                        $("#" + json.message).closest("label").removeClass("checked");

                        mess.To = "Box";
                        mess.Object = "Connected clients";
                        doSend(JSON.stringify(mess));
                    }
                    else if (json.Object === "Remaining Time") {//Init the remaining time countdown
                        var TimeInMs = parseInt(json.message);
                        var id = json.From;
                        acTimer(id, TimeInMs);
                    }
                    break;
                default:
                    break;
            }
        }
    }

};

options = {
    timeout: 3,
    keepAliveInterval: 30,
    userName: "mqtt-ESP-device",
    password: "mqtt-ESP-device",
    onSuccess: function () {
        isConnected = true;
        console.log("CONNECTION SUCCESS");
        // client.subscribe("/#", {qos: 1}); 
        client.subscribe("from/CleverBox/" + $('#bx').val(), { qos: 1 });
        console.log("Connected");
        mess.To = "Box";
        mess.Object = "Connection Status";
        doSend(JSON.stringify(mess));

        mess.To = "Devices";
        mess.Object = "Device Connexion Status";
        doSend(JSON.stringify(mess));

        //var d = new Date();
        //var strDate = d.toString();
        // console.log(d.getDate());
        // console.log(d.getDay());
        // console.log(d.getFullYear());
        // console.log(strDate.substring(0, strDate.indexOf(' (')));
        //$('.deviceClock').html(strDate.substring(0, strDate.indexOf(' (')));
        //mess.From = "user";
        //mess.To = "Box";
        //mess.Object = "Device Output Status";
        //mess.To = "Devices";
        //doSend(JSON.stringify(mess));
    },
    onFailure: function (message) {
        console.log("CONNECTION FAILURE - " + message.errorMessage);
        // Log disconnection state
        console.log("Disconnected");
        //$('#homeWsConnStatus').addClass('text-danger');
        //$('#homeWsConnStatus').removeClass('text-success');
    }
};
var useSSL = false;
if (location.protocol == "https:") {
    options.useSSL = true;
    //useSSL = true;
}

setTimeout(function () {
    if (isConnected === false) {
        console.log("CONNECT TO " + wsbroker + ":" + wsport);
        client.connect({
            timeout: 3,
            keepAliveInterval: 30,
            userName: "mqtt-ESP-device",
            password: "mqtt-ESP-device",
            useSSL: useSSL,
            onSuccess: function () {
                isConnected = true;

                contextLedHome.strokeStyle = "#5c1ac3";
                contextLedHome.stroke();
                contextLedHome.fillStyle = "#5c1ac3";
                contextLedHome.fill();

                console.log("CONNECTION SUCCESS");
                // client.subscribe("/#", {qos: 1}); 
                // client.subscribe("from/CleverBox/" + $('#bx').val() + "/#", { qos: 2 });
                client.subscribe("to/CleverBox/" + $('#bx').val() + "/#", { qos: 2 });
                console.log("Connected");
                /*mess.To = "Box";
                mess.Object = "Connection Status";
                doSend(JSON.stringify(mess));*/

                mess.To = "Devices";
                mess.Object = "Device Connexion Status";
                doSend(JSON.stringify(mess));

                //var d = new Date();
                //var strDate = d.toString();
                // console.log(d.getDate());
                // console.log(d.getDay());
                // console.log(d.getFullYear());
                // console.log(strDate.substring(0, strDate.indexOf(' (')));
                //$('.deviceClock').html(strDate.substring(0, strDate.indexOf(' (')));

                setTimeout(function () {
                    //Get the device's output status
                    mess.Object = "Device Output Status";
                    mess.To = "Devices";
                    doSend(JSON.stringify(mess));
                }, 800)
            },
            onFailure: function (message) {
                contextLedHome.strokeStyle = "red";
                contextLedHome.stroke();
                contextLedHome.fillStyle = "red";
                contextLedHome.fill();
                resetLedConnexionStatus();
                console.log("CONNECTION FAILURE - " + message.errorMessage);
                // Log disconnection state
                console.log("Disconnected");
                //$('#homeWsConnStatus').addClass('text-danger');
                //$('#homeWsConnStatus').removeClass('text-success');
            }
        });
    }
}, 500)

//console.log("CONNECT TO " + wsbroker + ":" + wsport);
//client.connect(options);
// Try to reconnect after a few seconds
setInterval(function () {
    if (isConnected === false) {
        console.log("CONNECT TO " + wsbroker + ":" + wsport);
        client.connect({
            timeout: 3,
            keepAliveInterval: 30,
            userName: "mqtt-ESP-device",
            password: "mqtt-ESP-device",
            useSSL: useSSL,
            onSuccess: function () {
                isConnected = true;

                contextLedHome.strokeStyle = "#5c1ac3";
                contextLedHome.stroke();
                contextLedHome.fillStyle = "#5c1ac3";
                contextLedHome.fill();

                console.log("CONNECTION SUCCESS");
                // client.subscribe("/#", {qos: 1}); 
                // client.subscribe("from/CleverBox/" + $('#bx').val() + "/#", { qos: 2 });
                client.subscribe("to/CleverBox/" + $('#bx').val() + "/#", { qos: 2 });
                console.log("Connected");
                /*mess.To = "Box";
                mess.Object = "Connection Status";
                doSend(JSON.stringify(mess));*/

                mess.To = "Devices";
                mess.Object = "Device Connexion Status";
                doSend(JSON.stringify(mess));

                //var d = new Date();
                //var strDate = d.toString();
                // console.log(d.getDate());
                // console.log(d.getDay());
                // console.log(d.getFullYear());
                // console.log(strDate.substring(0, strDate.indexOf(' (')));
                //$('.deviceClock').html(strDate.substring(0, strDate.indexOf(' (')));

                setTimeout(function () {
                    //Get the device's output status
                    mess.Object = "Device Output Status";
                    mess.To = "Devices";
                    doSend(JSON.stringify(mess));
                }, 800)
            },
            onFailure: function (message) {
                contextLedHome.strokeStyle = "red";
                contextLedHome.stroke();
                contextLedHome.fillStyle = "red";
                contextLedHome.fill();
                resetLedConnexionStatus();
                console.log("CONNECTION FAILURE - " + message.errorMessage);
                // Log disconnection state
                console.log("Disconnected");
                //$('#homeWsConnStatus').addClass('text-danger');
                //$('#homeWsConnStatus').removeClass('text-success');
            }
        });
    }
    /*else {
        resetLedConnexionStatus();

        mess.To = "Box";
        mess.Object = "Connection Status";
        doSend(JSON.stringify(mess));

        mess.To = "Devices";
        mess.Object = "Device Connexion Status";
        doSend(JSON.stringify(mess));
    }*/
}, 10000);

setInterval(function () {
    mess.To = "Devices";
    mess.Object = "Device Connexion Status";
    resetLedConnexionStatus();
    doSend(JSON.stringify(mess));

    setTimeout(function () {
        //Get the device's output status
        mess.Object = "Device Output Status";
        mess.To = "Devices";
        doSend(JSON.stringify(mess));
    }, 500)

}, 120000);

var arr;
var canvas = [];
var context = [];

var mess = {
    From: "user",
    To: "light",
    Object: "Toggle",
    message: "on"
};

// Sends a message to the server (and prints it to the console)
function doSend(data) {
    //console.log("Sending: " + message);
    //websocket.send(message);
    if (isConnected) {
        message = new Paho.MQTT.Message(data);
        //message.destinationName = "test/all";
        //message.destinationName = "remote/to/CleverBox/" + $('#bx').val();
        message.destinationName = "from/CleverBox/" + $('#bx').val() + "/user";
        console.log("SEND ON " + message.destinationName + " PAYLOAD " + data);
        client.send(message);
    }
}

// Select elements by their data attribute
const $entryLedElements = $('[data-entry-led]');
// Map over each element and extract the data value
const $entryLeds =
    $.map($entryLedElements, item => $(item).data('entryLed'));
// You'll now have array containing string values
//console.log($entryleds); // eg: ["1", "2", "3"]

// Select elements by their data attribute
const $entryLedIdElements = $('[data-entry-ledid]');
// Map over each element and extract the data value
const $entryLedIds =
    $.map($entryLedIdElements, item => $(item).data('entryLedid'));
// You'll now have array containing string values
//console.log($entryEmergencyValues); // eg: ["1", "2", "3"]

init();

// This is called when the page finishes loading
function init() {

    // Assign page elements to variables
    var led = "";
    var ledId = "";
    $.each($entryLeds, function (index, value) {
        led = "" + value;
        // console.log(led);
        ledId = "" + $entryLedIds[index];
        // Assign page elements to variables
        canvas[led] = document.getElementById(ledId);

        // Draw circle in canvas
        context[led] = canvas[led].getContext("2d");
        context[led].arc(15, 15, 5, 0, Math.PI * 2, false);
        context[led].lineWidth = 3;
        context[led].strokeStyle = "red";
        context[led].stroke();
        context[led].fillStyle = "red";
        context[led].fill();
    });

    // Connect to WebSocket server
    //wsConnect(wsUrl_);
}

function resetLedConnexionStatus() {
    var led = "";
    var ledId = "";
    $.each($entryLeds, function (index, value) {
        led = "" + value;
        ledId = "" + $entryLedIds[index];
        // Assign page elements to variables
        canvas[led] = document.getElementById(ledId);
        context[led].strokeStyle = "red";
        context[led].stroke();
        context[led].fillStyle = "red";
        context[led].fill();

    });
}

//Init the countdown timer of remaining time
function acTimer(id, ms) {
    //var ms = 298999;
    ms = 1000 * Math.round(ms / 1000); // round to nearest second
    var d = new Date(ms);
    var strTimer = d.getUTCHours() + 'h' + d.getUTCMinutes() + 'm' + d.getUTCSeconds() + 's';
    //var strTimer = msToTime(ms);
    console.log(strTimer);
    $('#RT_' + id).timer('remove');
    //$('#wash-machine').timer('pause');
    $('#RT_' + id).timer({
        countdown: true,
        format: '%H:%M:%S',
        duration: strTimer,
        callback: function () {
            //$('[data-unit="' + id + '"]').removeClass("active");
        }
    });

}

$(document).ready(function () {

    // $('#Save_Set').click(function () {
    //     SendClockSetting();
    // });

    // var deviceId;
    // $('.setDeviceClock').click(function () {
    //     deviceId = $(this).data('id');
    // })

    //Fonction qui va transférer les données de configuration de l'horloge entrer par l'utilisateur
    /*function SendClockSetting() {
        //console.log($('#setClock').val())
        var dt = new Date($('#setClock').val());
        if ($('#setClock').val()) {
            //console.log(dt);
            //console.log($('#applyforall').is(':checked'));
            //console.log(deviceId);
            if ($('#applyforall').is(':checked')) mess.To = "Devices";
            else mess.To = deviceId;

            mess.Object = "Setting Device's Clock";
            mess.message = "{\"success\":\"1\",\"Day\":" + dt.getDate() + ",\"Month\":" + (dt.getMonth() + 1) + ",\"Year\":" + dt.getFullYear() + ",\"Hour\":" + dt.getHours() + ",\"Mins\":" + dt.getMinutes() + ",\"Secs\":" + dt.getSeconds() + "}";

            //message.To = "moi";
            doSend(JSON.stringify(mess));
            $('#setDeviceCLKModal').modal('hide');

        }
        else {
            alert("Veuillez définir une date s'il vous plaît");
        }
    }*/

    // Get checkbox statuses from localStorage if available (IE)
    /*if (localStorage) {

        // Menu minifier status (Contract/expand side menu on large screens)
        var checkboxValue = localStorage.getItem('minifier');

        if (checkboxValue === 'true') {
            $('#sidebar,#menu-minifier').addClass('mini');
            $('#minifier').prop('checked', true);

        } else {

            if ($('#minifier').is(':checked')) {
                $('#sidebar,#menu-minifier').addClass('mini');
                $('#minifier').prop('checked', true);
            } else {
                $('#sidebar,#menu-minifier').removeClass('mini');
                $('#minifier').prop('checked', false);
            }
        }

        // Switch statuses
        //var switchValues = JSON.parse(localStorage.getItem('switchValues')) || {};

        // $.each(switchValues, function (key, value) {

        //     // Apply only if element is included on the page
        //     if ($('[data-unit="' + key + '"]').length) {

        //         if (value === true) {

        //             // Apply appearance of the "unit" and checkbox element
        //             $('[data-unit="' + key + '"]').addClass("active");
        //             $("#" + key).prop('checked', true);
        //             $("#" + key).closest("label").addClass("checked");

        //             //In case of Camera unit - play video
        //             if (key === "switch-camera-1" || key === "switch-camera-2") {
        //                 $('[data-unit="' + key + '"] video')[0].play();
        //             }

        //         } else {
        //             $('[data-unit="' + key + '"]').removeClass("active");
        //             $("#" + key).prop('checked', false);
        //             $("#" + key).closest("label").removeClass("checked");
        //             if (key === "switch-camera-1" || key === "switch-camera-2") {
        //                 $('[data-unit="' + key + '"] video')[0].pause();
        //             }
        //         }
        //     }
        // });

        // // Range Slider values
        // var rangeValues = JSON.parse(localStorage.getItem('rangeValues')) || {};

        // $.each(rangeValues, function (key, value) {

        //     // Apply only if element is included on the page
        //     if ($('[data-rangeslider="' + key + '"]').length) {

        //         if (key === 'fridge-temp') {
        //             // Update Range slider - special case Fridge
        //             var temperatureFar = value;
        //             var temperatureCel = (temperatureFar - 32) * 5 / 9;
        //             var roundCel = Number(Math.round(temperatureCel + 'e2') + 'e-2');
        //             $('[data-rangeslider="' + key + '"] #fridge-temp-F').html(temperatureFar);
        //             $('[data-rangeslider="' + key + '"] #fridge-temp-C').html(roundCel);

        //         } else {
        //             // Update Range slider - universal
        //             $('[data-rangeslider="' + key + '"] input[type="range"]').val(value);
        //             $('[data-rangeslider="' + key + '"] .range-output').html(value);
        //         }
        //     }
        // });

    }*/


    // Contract/expand side menu on click. (only large screens)
    $('#minifier').click(function () {

        $('#sidebar,#menu-minifier').toggleClass('mini');

        // Save side menu status to localStorage if available (IE)
        if (localStorage) {
            checkboxValue = this.checked;
            localStorage.setItem('minifier', checkboxValue);
        }

    });


    // Side menu toogler for medium and small screens
    $('[data-toggle="offcanvas"]').click(function () {
        $('.row-offcanvas').toggleClass('active');
    });




    // Reposition to center when a modal is shown
    $('.modal.centered').on('show.bs.modal', iot.centerModal);

    // Reset/Stop countdown timer (EXIT NOW)
    $('#armModal').on('hide.bs.modal', iot.clearCountdown);

    // Garage doors controls
    /*
    $('.doors-control').click(function () {
    
        var target = $(this).closest('.timer-controls').data('controls');
        var action = $(this).data('action');
    
        iot.garageDoors(target, action);
    });
    */

    // Notifications "Close" callback - hide modal and alert indicator dot when user closes all alerts
    $('#notifsModal .alert').on('close.bs.alert', function () {
        var nbAlert = parseInt($('#notifsModal div.alert').length) - 1;
        console.log('nb alert = ' + nbAlert);
        nbAlert = nbAlert - 1;
        $('#notifs-toggler').attr('data-alerts', nbAlert);
        nbAlert = nbAlert + 1;

        if (parseInt(nbAlert) === 0) {
            $('#notifsModal').modal('hide');
            console.log('nb alert = ' + nbAlert);
            // $('#notifs-toggler').attr('data-toggle', 'none');
            $('#notifs-toggler').addClass('d-none');

        }

    });

    // Alerts "Close" callback - hide modal and alert indicator dot when user closes all alerts
    $('#alertsModal .alert').on('close.bs.alert', function () {
        var sum = $('#alerts-toggler').attr('data-alerts');
        sum = sum - 1;
        $('#alerts-toggler').attr('data-alerts', sum);

        if (sum === 0) {
            $('#alertsModal').modal('hide');
            $('#alerts-toggler').attr('data-toggle', 'none');

        }

    });

    // Show/hide tips (popovers) - FAB button (right bottom on large screens)
    $('#info-toggler').click(function () {

        if ($('body').hasClass('info-active')) {
            $('[data-toggle="popover-all"]').popover('hide');
            $('body').removeClass('info-active');
        } else {
            $('[data-toggle="popover-all"]').popover('show');
            $('body').addClass('info-active');
        }
    });

    // Hide tips (popovers) by clicking outside
    $('body').on('click', function (pop) {

        if (pop.target.id !== 'info-toggler' && $('body').hasClass('info-active')) {
            $('[data-toggle="popover-all"]').popover('hide');
            $('body').removeClass('info-active');
        }

    });

});

// Apply necessary changes, functionality when content is loaded
$(window).on('load', function () {
    // Switch (checkbox element) toogler
    $('.switch input[type="checkbox"]').on("change", function (t) {
        console.log("Toggle Switch")
        // Check the time between changes to prevert Android native browser execute twice
        // If you dont need support for Android native browser - just call "switchSingle" function
        if (this.last) {

            this.diff = t.timeStamp - this.last;

            // Don't execute if the time between changes is too short (less than 250ms) - Android native browser "twice bug"
            // The real time between two human taps/clicks is usually much more than 250ms"
            if (this.diff > 250) {

                this.last = t.timeStamp;
                mess.To = this.id;
                mess.Object = "Toggle";
                mess.message = (this.checked ? 1 : 0);

                //message.To = "moi";
                doSend(JSON.stringify(mess));
                this.checked = !this.checked;
                //$('[data-unit="' + this.id + '"]').toggleClass("active");
                //$('#' + this.id).closest("label").toggleClass("checked", this.checked);
                console.log('id = ' + this.id);
                console.log('checked = ' + this.checked);
                //iot.switchSingle(this.id, this.checked);

            } else {
                return false;
            }

        } else {

            // First attempt on this switch element
            this.last = t.timeStamp;
            mess.To = this.id;
            mess.Object = "Toggle";
            mess.message = (this.checked ? 1 : 0);

            doSend(JSON.stringify(mess));
            this.checked = !this.checked;
            //$('[data-unit="' + this.id + '"]').toggleClass("active");
            //$('#' + this.id).closest("label").toggleClass("checked", this.checked);
            console.log('id = ' + this.id);
            console.log('checked = ' + this.checked);
            //iot.switchSingle(this.id, this.checked);

        }
    });

    // All ON/OFF controls
    $('.lights-control').click(function () {

        var target = $(this).closest('.lights-controls').data('controls');
        var action = $(this).data('action');
        console.log('target = ' + target);
        //iot.switchGroup(target, action);
        var el = '[data-unit-group="' + target + '"]';
        var key;
        var id;
        var status;
        // Apply changes based on action
        switch (action) {

            case 'all-on':
                console.log('action = ' + action);
                $(el + ' [data-unit]').each(function () {//On parcours tous les élts du groupe
                    //console.log(this);
                    key = $(this).data('unit');//On récupère l'id de l'élt
                    id = "#" + key;//On formate l'id pour récupérer l'élt html avec jQuery
                    //$(this).addClass("active");
                    //$("#" + key).prop('checked', true);
                    //$("#" + key).closest("label").addClass("checked");

                    status = $(id).is(':checked');//On récupère l'état du switch(checkbox) associé à l'id récupéré
                    if (!status) {//On envoi une commande de démarrage ou allumage si l'élt est inactif ou éteint
                        mess.To = key;
                        mess.message = 1;
                        mess.Object = "Toggle";
                        //message.To = "moi";
                        doSend(JSON.stringify(mess));
                        //$(id).prop('checked', !status);
                        //$('[data-unit="' + this.id + '"]').toggleClass("active");
                        //$('#' + this.id).closest("label").toggleClass("checked", this.checked);
                        console.log('id = ' + key);
                        console.log('checked = ' + status);
                        //switchValues[key] = true;
                    }

                });
                break;
            case 'all-off':
                console.log('action = ' + action);
                $(el + ' [data-unit]').each(function () {//On parcours tous les élts du groupe
                    key = $(this).data('unit');//On récupère l'id de l'élt
                    id = "#" + key;//On formate l'id pour récupérer l'élt html avec jQuery
                    //$(this).addClass("active");
                    //$("#" + key).prop('checked', true);
                    //$("#" + key).closest("label").addClass("checked");

                    status = $(id).is(':checked');//On récupère l'état du switch(checkbox) associé à l'id récupéré
                    if (status) {//On envoi une commande d'arrêt ou extinction si l'élt est actif ou allumé
                        mess.To = key;
                        mess.message = 0;
                        mess.Object = "Toggle";

                        //message.To = "moi";
                        doSend(JSON.stringify(mess));
                        //$(id).prop('checked', !status);
                        //$('[data-unit="' + this.id + '"]').toggleClass("active");
                        //$('#' + this.id).closest("label").toggleClass("checked", this.checked);
                        console.log('id = ' + key);
                        console.log('checked = ' + status);
                        //switchValues[key] = true;
                    }
                });
                break;
        }

    });

    //console.log("Doc Ready")
    // This script is necessary for cross browsers icon sprite support (IE9+, ...) 
    /*svg4everybody();
    var ms = 298999;
    ms = 1000 * Math.round(ms / 1000); // round to nearest second
    var d = new Date(ms);
    var strTimer = d.getHours() + 'h' + d.getMinutes() + 'm' + d.getSeconds() + 's';
    //console.log(strTimer); // "4:59"
    // Washing machine - demonstration of running program/cycle
    $('#wash-machine').timer({
        countdown: true,
        format: '%H:%M:%S',
        duration: strTimer,//'1h17m10s',
        callback: function () {
            $('[data-unit="wash-machine"]').removeClass("active");
        }
    });*/

    // Washing machine - demonstration of running program/cycle
    /*$('#wash-machine').timer({
        countdown: true,
        format: '%H:%M:%S',
        duration: '1m10s',
        //                duration: '1h17m10s',
        callback: function () {
            $('[data-unit="wash-machine"]').removeClass('active');
            $('[data-unit="wash-machine"] .status').html('OFF');
        }
    });*/

    $('[data-unit="wash-machine"] .timer-controls button[data-action="pause"]').css("display", "block");


    // "Timeout" function is not neccessary - important is to hide the preloader overlay
    setTimeout(function () {

        // Hide preloader overlay when content is loaded
        $('#iot-preloader,.card-preloader').fadeOut();
        $("#wrapper").removeClass("hidden");

        // Initialize range sliders
        /*$('input[type="range"]').rangeslider({
            polyfill: false,
            onSlideEnd: function (position, value) {

                var rangeValues = JSON.parse(localStorage.getItem('rangeValues')) || {};
                // Update localStorage
                if (localStorage) {
                    rangeValues[this.$element[0].id] = value;
                    localStorage.setItem("rangeValues", JSON.stringify(rangeValues));
                }
            }

        });*/

        // Check for Main contents scrollbar visibility and set right position for FAB button
        //iot.positionFab();

    }, 800);

});

// Apply necessary changes if window resized
$(window).on('resize', function () {

    // Modal reposition when the window is resized
    $('.modal.centered:visible').each(iot.centerModal);

    // Check for Main contents scrollbar visibility and set right position for FAB button
    iot.positionFab();
});