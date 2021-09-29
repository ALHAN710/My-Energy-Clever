//$(document).ready(function () {
// Get checkbox statuses from localStorage if available (IE)
/*    if (localStorage) {

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
    }


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
*/
var timeweek_from;
var timeweek_to;
var week_autoon;
var week_timer;
var sat_from;
var sat_to;
var sat_autoon;
var sat_timer;
var sun_from;
var sun_to;
var sun_autoon;
var sun_timer;
var myname;
var Name;
var workingFrom = [];
var workingTo = [];
var autoon = [];
var Timer = [];


RefreshProg('appliances');

$('#appliances_select').change(function () {
    Str = String($('#appliances_select').val());
    //$('#outputname').attr('disabled', true);

    Name = $('#appliances_select option[value=\"' + Str + '\"]').text();
    console.log('outselect val ' + Str);
    console.log('Option selected : ' + String(Name));
    //$("#outputname").val(String(Name));
    //console.log('OutputName : ' + $("#outputname").val());

    RefreshProg('appliances');

});


$('#SaveBtn').click(function () {
    SendProg('appliances');

});


//Fonction qui va transférer les données de programmation horaire entrer par l'utilisateur
function SendProg(select) {
    var jsonProg = "";
    var Mode = 0;
    var hh = "";
    var mm = "";
    var autoOn = 0;
    var timer = String("");

    jsonProg += '{"outName":"' + $('#appliances_select').val() + '"';
    for (let day = 1; day < 8; day++) {
        workingFrom[day] = document.getElementById('timeweek_from' + (day - 1));
        workingTo[day] = document.getElementById('timeweek_to' + (day - 1));
        autoon[day] = $('#auto_on_check' + (day - 1)).is(':checked');
        Timer[day] = document.getElementById('timer' + (day - 1));
        console.log('workingFrom[' + day + '].value = ' + workingFrom[day].value);
        if (workingFrom[day].value !== "" && workingFrom[day].value !== "") { Mode = 1; }
        else { Mode = 0; }
        if (workingFrom[day].value !== "") {
            hh = (workingFrom[day].value[0] === '0' ? "" : workingFrom[day].value[0]) + workingFrom[day].value[1];
            mm = (workingFrom[day].value[3] === '0' ? "" : workingFrom[day].value[3]) + workingFrom[day].value[4];

        }
        else {
            hh = -1;
            mm = -1;

        }
        timer = String(Timer[day].value);
        if (timer === "") { timer = 0; }// \"success\":\"1\",
        jsonProg += ',"' + (day - 1) + '":{"Mod":' + Mode + ',"WF":[' + hh + ',' + mm + '],"To":[';

        if (workingTo[day].value !== "") {
            hh = (workingTo[day].value[0] === '0' ? "" : workingTo[day].value[0]) + workingTo[day].value[1];
            mm = (workingTo[day].value[3] === '0' ? "" : workingTo[day].value[3]) + workingTo[day].value[4];

        }
        else {
            hh = -1;
            mm = -1;

        }
        if (autoon[day]) { autoOn = 1; }
        else { autoOn = 0; }
        jsonProg += hh + "," + mm + '],"Auto":' + autoOn + ',"Td":' + timer + '}';


    }
    jsonProg += '}';
    console.log(jsonProg);

    var $data = JSON.stringify({
        "action": "save",
        "prog": jsonProg,
        "type": "Appliance"
    });

    console.log($data);
    $('.card-preloader').fadeIn();
    $.ajax({
        type: "POST",//method type
        contentType: "application/json; charset=utf-8",
        url: $url,///Target function that will be return result
        data: $data,//parameter pass data is parameter name param is value 
        dataType: "json",
        success: function (data) {
            //alert("Success");
            console.log(data);
            if (data.success === "1" || data.success === 1) {
                console.log("Prog data done");
                console.log("Retour prg " + JSON.stringify(data));
                /*if (String(myname) !== "" && String(myname) !== "S0" && String(myname) !== "S1") {
                    $('#outselect option[value=\"' + Str + '\"]').text(String(myname));
                    $('#' + Str + '_name').text(String(myname));
                }*/
                //mess.From = "user";
                mess.To = "switch-socket-" + $('#appliances_select').val();
                mess.Object = "Programming";
                mess.message = jsonProg;
                doSend(JSON.stringify(mess));
                $('.card-preloader').fadeOut();
                alert('Programmation Done');
            }

        },
        error: function (result) {
            console.log(result);
            console.log("Prog data don't save ");
            $('.card-preloader').fadeOut();
            alert('Programmation don\'t save');
        }
    });

}

function RefreshProg(select) {

    var $data = JSON.stringify({
        "action": "get prog",
        "criteria": "name",
        "name": $('#appliances_select').val(),
        "type": "Appliance",
    });

    console.log($data);
    $('.card-preloader').fadeIn();
    $.ajax({
        type: "POST",//method type
        contentType: "application/json; charset=utf-8",
        url: $url,///Target function that will be return result
        data: $data,//parameter pass data is parameter name param is value 
        dataType: "json",
        success: function (data) {
            console.log("Retour Refresh Prog " + JSON.stringify(data));
            $('.card-preloader').fadeOut();
            var h = "";
            var m = "";
            var str = "";

            if (data.success === 1 || data.success === "1") {

                //Str = String($('#' + select).val());
                //$('#outselect option[value=\"' + Str + '\"]').text(String(data.outName));
                //$('#outputname').attr('disabled', true);
                //Name = $('#outselect option[value=\"' + Str + '\"]').text();
                $data = JSON.parse(JSON.stringify(data.prog))
                //console.log($data.parseInt("1"));
                for (let day = 1; day < 8; day++) {
                    if ($data['' + (day - 1)].Mod === 1 || String($data['' + (day - 1)].Mod) === "1") {
                        console.log('day-' + (day - 1) + ' Mod = 1');
                        if (parseInt($data['' + (day - 1)].WF[0]) >= 0 && parseInt($data['' + (day - 1)].WF[0]) < 10) {
                            h = "0" + $data['' + (day - 1)].WF[0];
                            console.log('day-' + (day - 1) + ' WFh = ' + h);
                        }
                        else { h = $data['' + (day - 1)].WF[0]; }
                        if (parseInt($data['' + (day - 1)].WF[1]) >= 0 && parseInt($data['' + (day - 1)].WF[1]) < 10) {
                            m = "0" + $data['' + (day - 1)].WF[1];
                            console.log('day-' + (day - 1) + ' WFm = ' + m);
                        }
                        else {
                            m = "" + $data['' + (day - 1)].WF[1];
                            console.log('day-' + (day - 1) + ' WFm = ' + m);
                        }
                        str = String(h) + ":" + String(m);
                        $('#timeweek_from' + (day - 1)).val(String(str));
                        h = "";
                        m = "";
                        if (parseInt($data['' + (day - 1)].To[0]) >= 0 && parseInt($data['' + (day - 1)].To[0]) < 10) {
                            h = "0" + $data['' + (day - 1)].To[0];
                            console.log('day-' + (day - 1) + ' Toh = ' + h);
                        }
                        else {
                            h = "" + $data['' + (day - 1)].To[0];
                            console.log('day-' + (day - 1) + ' Toh = ' + h);
                        }
                        if (parseInt($data['' + (day - 1)].To[1]) >= 0 && parseInt($data['' + (day - 1)].To[1]) < 10) {
                            m = "0" + $data['' + (day - 1)].To[1];
                            console.log('day-' + (day - 1) + ' Tom = ' + m);
                        }
                        else {
                            m = "" + $data['' + (day - 1)].To[1];
                            console.log('day-' + (day - 1) + ' om = ' + m);
                        }
                        str = String(h) + ":" + String(m);
                        console.log('day-' + (day - 1) + ' Time To = ' + String(str));
                        $('#timeweek_to' + (day - 1)).val(String(str));
                        if ($data['' + (day - 1)].Auto === 1 || String($data['' + (day - 1)].Auto) === "1") { $('#auto_on_check' + (day - 1)).prop("checked", true); }
                        else { $('#auto_on_check' + (day - 1)).prop("checked", false); }
                        $('#timer' + (day - 1)).val(parseInt($data['' + (day - 1)].Td));
                    }
                    else {
                        $('#timeweek_from' + (day - 1)).val("");
                        $('#timeweek_to' + (day - 1)).val("");
                        $('#auto_on_check' + (day - 1)).prop("checked", false);
                        $('#timer' + (day - 1)).val("");

                    }
                }


            }
            else if (data.success === 0 || data.success === "0") {
                for (let day = 0; day < 7; day++) {
                    $('#timeweek_from' + day).val("");
                    $('#timeweek_to' + day).val("");
                    $('#auto_on_check' + day).prop("checked", false);
                    $('#timer' + day).val("");

                }

            }

        },
        error: function (result) {
            console.log("Server don't send " + $('#appliances_select').val() + " program \n" + "err getJSON Device Prog " + JSON.stringify(err));
            $('.card-preloader').fadeOut();
            for (let day = 0; day < 7; day++) {
                $('#timeweek_from' + day).val("");
                $('#timeweek_to' + day).val("");
                $('#auto_on_check' + day).prop("checked", false);
                $('#timer' + day).val("");

            }

        }
    });
}

//});
