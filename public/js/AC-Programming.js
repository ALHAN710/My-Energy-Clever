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


if (tabClim) {
    //$('.card-preloader').fadeIn();

    RefreshProg('clim');
}
//if (tabFan) RefreshProg('fan');
$('#clim_select').change(function () {
    Str = String($('#clim_select').val());
    //$('#outputname').attr('disabled', true);

    Name = $('#clim_select option[value=\"' + Str + '\"]').text();
    console.log('outselect val ' + Str);
    console.log('Option selected : ' + String(Name));
    //$("#outputname").val(String(Name));
    //console.log('OutputName : ' + $("#outputname").val());

    RefreshProg('clim');

});

$('#scenario').change(function () {
    switch (String($('#scenario').val())) {
        case "Prog":
            $('#timeRegFields').addClass('d-none');
            break;
        case "Prog&Break":
            $('#timeRegFields').addClass('d-none');
            break;
        case "Break":
            $('#timeRegFields').addClass('d-none');
            break;
        case "Break&Reg":
            $('#timeRegFields').removeClass('d-none');
            break;
        case "Reg":
            $('#timeRegFields').removeClass('d-none');
            break;
        case "Prog&Reg":
            $('#timeRegFields').removeClass('d-none');
            break;
        case "All":
            $('#timeRegFields').removeClass('d-none');
            break;

        default:
            break;
    }
});

$('#Save_ACProg').click(function () {
    SendProg('clim');

});

//Fonction qui va transférer les données de programmation horaire entrer par l'utilisateur
function SendProg(select) {
    var prog;
    var brk;
    var reg;
    var jsonProg = "";
    var Mode = 0;
    var BTMode = 0;
    var hh = "";
    var mm = "";
    var autoOn = 0;
    var timer = String("");
    timeON = document.getElementById("timeON");
    timeOFF = document.getElementById("timeOFF");
    ngtimeON = document.getElementById("ngtimeON");
    ngtimeOFF = document.getElementById("ngtimeOFF");


    switch (String($('#scenario').val())) {
        case "Prog":
            prog = 1;
            brk = 0;
            reg = 0;
            break;
        case "Prog&Break":
            prog = 1;
            brk = 1;
            reg = 0;
            break;
        case "Break":
            prog = 0;
            brk = 1;
            reg = 0;
            break;
        case "Break&Reg":
            prog = 0;
            brk = 1;
            reg = 1;
            break;
        case "Reg":
            prog = 0;
            brk = 0;
            reg = 1;
            break;
        case "Prog&Reg":
            prog = 1;
            brk = 0;
            reg = 1;
            break;
        case "All":
            prog = 1;
            brk = 1;
            reg = 1;
            break;

        default:
            break;
    }

    var combinedReg;
    if ($('#combined_reg_check').is(':checked')) { combinedReg = 1; }
    else { combinedReg = 0; }
    var timerON = String(timeON.value);
    if (timerON === "") { timerON = 0; }
    var timerOFF = String(timeOFF.value);
    if (timerOFF === "") { timerOFF = 0; }

    var ngtimerON = String(ngtimeON.value);
    if (ngtimerON === "") { ngtimerON = 0; }
    var ngtimerOFF = String(ngtimeOFF.value);
    if (ngtimerOFF === "") { ngtimerOFF = 0; }

    jsonProg += '{"outName":"' + $('#' + select + '_select').val() + "\",\"ProgF\":" + prog + ",\"BreakF\":" + brk + ",\"RegF\":" + reg + ",\"CombRegF\":" + combinedReg + ",\"TON\":[" + timerON + "," + ngtimerON + "],\"TOFF\":[" + timerOFF + "," + ngtimerOFF + "]";
    for (let day = 1; day < 8; day++) {
        workingFrom[day] = document.getElementById(select + '_timeweek_from' + (day - 1));
        workingTo[day] = document.getElementById(select + '_timeweek_to' + (day - 1));
        autoon[day] = $('#' + select + '_auto_on_check' + (day - 1)).is(':checked');
        Timer[day] = document.getElementById(select + '_timer' + (day - 1));

        if ((day - 1) != 0 && (day - 1) != 6) {
            breakweek_from = document.getElementById("breakweek_from1");
            breakweek_to = document.getElementById("breakweek_to1");
        } else {
            breakweek_from = document.getElementById("breakweek_from" + (day - 1));
            breakweek_to = document.getElementById("breakweek_to" + (day - 1));

        }

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
        //jsonProg += hh + "," + mm + '],"Auto":' + autoOn + ',"Td":' + timer + '}';
        jsonProg += hh + "," + mm + "],\"Auto\":" + autoOn + ",\"Td\":" + timer + ", \"BF\":[";
        BTMode = 1;
        if (breakweek_from.value !== "") {
            hh = breakweek_from.value[0] + breakweek_from.value[1];
            mm = breakweek_from.value[3] + breakweek_from.value[4];
            BTMode = 1 * BTMode;
        }
        else {
            hh = -1;
            mm = -1;
            BTMode = 0 * BTMode;
        }
        jsonProg += hh + "," + mm + "],\"Bto\":[";
        if (breakweek_to.value !== "") {
            hh = breakweek_to.value[0] + breakweek_to.value[1];
            mm = breakweek_to.value[3] + breakweek_to.value[4];

        }
        else {
            hh = -1;
            mm = -1;

        }
        jsonProg += hh + "," + mm + "],\"BTMod\":" + BTMode + "}";
    }

    jsonProg += '}';
    //console.log(jsonProg);

    var $data = JSON.stringify({
        "action": "save",
        "prog": jsonProg,
        "type": "Climate"
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
                // if (String(myname) !== "" && String(myname) !== "S0" && String(myname) !== "S1") {
                //     $('#outselect option[value=\"' + Str + '\"]').text(String(myname));
                //     $('#' + Str + '_name').text(String(myname));
                // }
                //mess.From = "user";
                mess.To = "AC-" + $('#' + select + '_select').val();
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
        "name": $('#' + select + '_select').val(),
        "type": "Climate"
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
            var sel = "";

            if (data.success === 1 || data.success === "1") {

                //Str = String($('#' + select).val());
                //$('#outselect option[value=\"' + Str + '\"]').text(String(data.outName));
                //$('#outputname').attr('disabled', true);
                //Name = $('#outselect option[value=\"' + Str + '\"]').text();
                $data = JSON.parse(JSON.stringify(data.prog))
                if ($data.ProgF && $data.BreakF && $data.RegF) sel = "All";
                else if ($data.ProgF && !$data.BreakF && !$data.RegF) sel = "Prog";
                else if ($data.ProgF && $data.BreakF && !$data.RegF) sel = "Prog&Break";
                else if (!$data.ProgF && !$data.BreakF && $data.RegF) sel = "Reg";
                else if ($data.ProgF && !$data.BreakF && $data.RegF) sel = "Prog&Reg";
                else if (!$data.ProgF && $data.BreakF && !$data.RegF) sel = "Break";
                //else if (!$data.ProgF && $data.BreakF && $data.RegF) sel = "Break&Reg";
                $('#scenario option[value=\"' + sel + '\"]').attr('selected', true);
                $('#timeON').val($data.TON[0]);
                $('#ngtimeON').val($data.TON[1]);
                $('#timeOFF').val($data.TOFF[0]);
                $('#ngtimeOFF').val($data.TOFF[1]);
                //console.log($data.parseInt("1"));
                if ($data.CombRegF === 1 || String($data.CombRegF) === "1") { $('#combined_reg_check').prop("checked", true); }
                else { $('#combined_reg_check').prop("checked", false); }

                for (let day = 1; day < 8; day++) {
                    if ($data['' + (day - 1)].Mod === 1 || String($data['' + (day - 1)].Mod) === "1") {
                        console.log('day-' + day + ' Mod = 1');
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
                        $('#' + select + '_timeweek_from' + (day - 1)).val(String(str));
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
                        $('#' + select + '_timeweek_to' + (day - 1)).val(String(str));

                        h = "";
                        m = "";
                        if (parseInt($data['' + (day - 1)].BF[0]) >= 0 && parseInt($data['' + (day - 1)].BF[0]) < 10) {
                            h = "0" + $data['' + (day - 1)].BF[0];
                            console.log('weekBFh = ' + h);
                        }
                        else { h = $data['' + (day - 1)].BF[0]; }
                        if (parseInt($data['' + (day - 1)].BF[1]) >= 0 && parseInt($data['' + (day - 1)].BF[1]) < 10) {
                            m = "0" + $data['' + (day - 1)].BF[1];
                            console.log('weekBFm = ' + m);
                        }
                        else {
                            m = "" + $data['' + (day - 1)].BF[1];
                            console.log('weekBFm = ' + m);
                        }
                        str = String(h) + ":" + String(m);
                        if ((day - 1) != 0 && (day - 1) != 6) $('#breakweek_from1').val(String(str));
                        else if ((day - 1) == 6) $('#breakweek_from6').val(String(str));
                        h = "";
                        m = "";
                        if (parseInt($data['' + (day - 1)].Bto[0]) >= 0 && parseInt($data['' + (day - 1)].Bto[0]) < 10) {
                            h = "0" + $data['' + (day - 1)].Bto[0];
                            console.log('weekBtoh = ' + h);
                        }
                        else {
                            h = "" + $data['' + (day - 1)].Bto[0];
                            console.log('weekBtoh = ' + h);
                        }
                        if (parseInt($data['' + (day - 1)].Bto[1]) >= 0 && parseInt($data['' + (day - 1)].Bto[1]) < 10) {
                            m = "0" + $data['' + (day - 1)].Bto[1];
                            console.log('weekBtom = ' + m);
                        }
                        else {
                            m = "" + $data['' + (day - 1)].Bto[1];
                            console.log('weekBtom = ' + m);
                        }
                        str = String(h) + ":" + String(m);
                        console.log('Week Time Bto = ' + String(str));
                        if ((day - 1) != 0 && (day - 1) != 6) $('#breakweek_to1').val(String(str));
                        else if ((day - 1) == 6) $('#breakweek_to6').val(String(str));


                        if ($data['' + (day - 1)].Auto === 1 || String($data['' + (day - 1)].Auto) === "1") { $('#' + select + '_auto_on_check' + (day - 1)).prop("checked", true); }
                        else { $('#' + select + '_auto_on_check' + (day - 1)).prop("checked", false); }
                        $('#' + select + '_timer' + (day - 1)).val(parseInt($data['' + (day - 1)].Td));
                    }
                    else {
                        $('#' + select + '_timeweek_from' + (day - 1)).val("");
                        $('#' + select + '_timeweek_to' + (day - 1)).val("");
                        $('#' + select + '_auto_on_check' + (day - 1)).prop("checked", false);
                        $('#' + select + '_timer' + (day - 1)).val("");
                    }
                }


            }
            else if (data.success === 0 || data.success === "0") {
                for (let day = 0; day < 7; day++) {
                    $('#' + select + '_timeweek_from' + day).val("");
                    $('#' + select + '_timeweek_to' + day).val("");
                    $('#' + select + '_auto_on_check' + day).prop("checked", false);
                    $('#' + select + '_timer' + day).val("");

                }

            }

        },
        error: function (result) {
            console.log("Server don't send " + $('#' + select + '_select').val() + " program \n" + "err getJSON Device Prog " + JSON.stringify(err));
            $('.card-preloader').fadeOut();
            for (let day = 0; day < 7; day++) {
                $('#' + select + '_timeweek_from' + day).val("");
                $('#' + select + '_timeweek_to' + day).val("");
                $('#' + select + '_auto_on_check' + day).prop("checked", false);
                $('#' + select + '_timer' + day).val("");

            }

        }
    });
}

//});
