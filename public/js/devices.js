var streamingUrl = $("#streamingUrl");
var in_streamingUrl = $('#devices_streamingUrl');
var _alert = $("#alert");
var in_alert = $('#devices_alerte');
var _appliancespec = $("#appliancespec");
var in_appliancespec = $('#devices_appliancespec');
var _smartmeterspec = $("#smartmeterspec");
var in_smartmeterspec = $('#devices_smartmeterspec');
var notifMess = $("#notificationMessage");
var in_notifMess = $('#devices_notificationMessage');
var urlTab = $("#cameraUrlTab");
var ipAddress = $("#Ipaddress");
var in_ipAddress = $("#devices_Ipaddress");

if ($('#devices_type').val() === 'Camera') {
    //Affichage des champs propre à l'équipement de type Camera
    streamingUrl.removeClass("d-none");
    in_streamingUrl.attr('required', 'required');
    urlTab.removeClass("d-none");

    notifMess.removeClass("d-none");
    in_notifMess.attr('required', 'required');

    //Masquer les champs non utile pour l'équipement de type camera
    _alert.removeClass("d-none");
    in_alert.removeAttr("required");
    _alert.addClass("d-none");

    _appliancespec.removeClass("d-none");
    in_appliancespec.removeAttr("required");
    in_appliancespec.val("");
    _appliancespec.addClass("d-none");

    _smartmeterspec.removeClass("d-none");
    in_smartmeterspec.removeAttr("required");
    in_smartmeterspec.val("");
    _smartmeterspec.addClass("d-none");
}
else {

    //Masquer les champs propre à l'équipement de type camera
    streamingUrl.removeClass("d-none");
    //$('#devices_streamingUrl').attr('required', false);
    in_streamingUrl.removeAttr("required");
    streamingUrl.addClass("d-none");
    //$("#streamingUrl").hide(); //then hide
    urlTab.addClass("d-none");
    if ($('#devices_type').val() === 'Alarm') {
        //Masquer les champs non utile pour l'équipement de type Alarm
        notifMess.removeClass("d-none");
        in_notifMess.removeAttr("required");
        notifMess.addClass("d-none");

        _appliancespec.removeClass("d-none");
        in_appliancespec.removeAttr("required");
        in_appliancespec.val("");
        _appliancespec.addClass("d-none");

        _smartmeterspec.removeClass("d-none");
        in_smartmeterspec.removeAttr("required");
        in_smartmeterspec.val("");
        _smartmeterspec.addClass("d-none");

        ipAddress.removeClass("d-none");
        in_ipAddress.attr('required', 'required');

        _alert.removeClass("d-none");
        in_alert.removeAttr("required");
        _alert.addClass("d-none");
    }
    else {

        if ($('#devices_type').val() === 'Light' || $('#devices_type').val() === 'Appliance' || $('#devices_type').val() === 'Climate') {
            _alert.removeClass("d-none");
            in_alert.removeAttr("required");
            in_alert.val("");
            _alert.addClass("d-none");

            notifMess.removeClass("d-none");
            in_notifMess.removeAttr("required");
            notifMess.addClass("d-none");

            ipAddress.removeClass("d-none");
            in_ipAddress.removeAttr("required");
            ipAddress.addClass("d-none");

            _smartmeterspec.removeClass("d-none");
            in_smartmeterspec.removeAttr("required");
            in_smartmeterspec.val("");
            _smartmeterspec.addClass("d-none");

            _appliancespec.removeClass("d-none");
            in_appliancespec.attr("required", "required");

            if ($('#devices_type').val() === 'Light') {
                $("#appliancespec label[for*='devices_appliancespec']").text("Light type");
                in_appliancespec
                    .find('option')
                    .remove()
                    .end()
                    .append('<option selected="selected" value="Interior">INTÉRIEUR</option>')
                    .append('<option value="Exterior">EXTÉRIEUR</option>')
                    ;

            }
            else if ($('#devices_type').val() === 'Appliance') {
                $("#appliancespec label[for*='devices_appliancespec']").text("Appliance type");
                in_appliancespec
                    .find('option')
                    .remove()
                    .end()
                    .append('<option selected="selected" value="Tv">TV</option>')
                    .append('<option  value="Wash Machine">MACHINE À LAVER</option>')
                    .append('<option  value="Fridge">RÉFRIGÉRATEUR</option>')
                    ;

            }
            else if ($('#devices_type').val() === 'Climate') {
                $("#appliancespec label[for*='devices_appliancespec']").text("Climate type");
                in_appliancespec
                    .find('option')
                    .remove()
                    .end()
                    .append('<option selected="selected" value="Clim">CLIMATISEUR</option>')
                    .append('<option  value="Fan">VENTILATEUR</option>')
                    ;

            }

            //alert('Test');

        }
        else if ($('#devices_type').val() === 'Bell' || $('#devices_type').val() === 'Smart Meter') {
            _alert.removeClass("d-none");
            in_alert.removeAttr("required");
            in_alert.val("");
            _alert.addClass("d-none");

            notifMess.removeClass("d-none");
            in_notifMess.removeAttr("required");
            notifMess.addClass("d-none");

            _appliancespec.removeClass("d-none");
            in_appliancespec.removeAttr("required");
            in_appliancespec.val("");
            _appliancespec.addClass("d-none");

            _smartmeterspec.removeClass("d-none");
            in_smartmeterspec.attr("required", "required");

            if ($('#devices_type').val() === 'Bell') {
                ipAddress.removeClass("d-none");
                in_ipAddress.attr('required', 'required');

                _smartmeterspec.removeClass("d-none");
                in_smartmeterspec.removeAttr("required");
                in_smartmeterspec.val("");
                _smartmeterspec.addClass("d-none");
            }
            else if ($('#devices_type').val() === 'Smart Meter') {
                ipAddress.removeClass("d-none");
                in_ipAddress.removeAttr("required");
                ipAddress.addClass("d-none");

                //$("#smartmeterspec label[for*='devices_smartmeterspec']").text("Light type");
                in_smartmeterspec
                    .find('option')
                    .remove()
                    .end()
                    .append('<option selected="selected" value="Socket">SOCKET</option>')
                    .append('<option value="Home">HOME</option>')
                    ;
            }

        }
        else {
            //Affichage des champs utile aux équipements de type Sensor
            notifMess.removeClass("d-none");
            in_notifMess.attr("required", "required");

            _alert.removeClass("d-none");
            in_alert.attr("required", "required");
            in_alert
                .find('option')
                .remove()
                .end()
                .append('<option selected="selected" value="Intrusion">INTRUSION</option>')
                .append('<option value="Fire">INCENDIE</option>')
                .append('<option value="Flood">INONDATION</option>')
                .append('<option value="Opening">OUVERTURE</option>')
                ;

            _appliancespec.removeClass("d-none");
            in_appliancespec.removeAttr("required");
            in_appliancespec.val("");
            _appliancespec.addClass("d-none");

            _smartmeterspec.removeClass("d-none");
            in_smartmeterspec.removeAttr("required");
            in_smartmeterspec.val("");
            _smartmeterspec.addClass("d-none");
        }
    }

}

$('#devices_type').change(function () {
    Str = String($('#devices_type').val());
    Name = $('#devices_type option[value=\"' + Str + '\"]').text();
    console.log('devices_type val ' + Str);
    console.log('Option selected : ' + String(Name));
    //console.log('Type of devices_type : ' + jQuery.type($('#devices_type').val()));

    ipAddress.removeClass("d-none");
    in_ipAddress.removeAttr("required");
    ipAddress.addClass("d-none");

    if ($('#devices_type').val() === 'Camera') {
        //Affichage des champs propre à l'équipement de type Camera
        streamingUrl.removeClass("d-none");
        in_streamingUrl.attr('required', 'required');
        urlTab.removeClass("d-none");

        notifMess.removeClass("d-none");
        in_notifMess.attr('required', 'required');

        ipAddress.removeClass("d-none");
        in_ipAddress.attr('required', 'required');

        //Masquer les champs non utile pour l'équipement de type camera
        _alert.removeClass("d-none");
        in_alert.removeAttr("required");
        in_alert.val("");
        _alert.addClass("d-none");

        _appliancespec.removeClass("d-none");
        in_appliancespec.removeAttr("required");
        in_appliancespec.val("");
        _appliancespec.addClass("d-none");

        _smartmeterspec.removeClass("d-none");
        in_smartmeterspec.removeAttr("required");
        in_smartmeterspec.val("");
        _smartmeterspec.addClass("d-none");
    }
    else {

        //Masquer les champs propre à l'équipement de type camera
        streamingUrl.removeClass("d-none");
        //$('#devices_streamingUrl').attr('required', false);
        in_streamingUrl.removeAttr("required");
        streamingUrl.addClass("d-none");
        //$("#streamingUrl").hide(); //then hide
        urlTab.addClass("d-none");
        if ($('#devices_type').val() === 'Alarm') {
            //Masquer les champs non utile pour l'équipement de type Alarm
            notifMess.removeClass("d-none");
            in_notifMess.removeAttr("required");
            notifMess.addClass("d-none");

            _appliancespec.removeClass("d-none");
            in_appliancespec.removeAttr("required");
            in_appliancespec.val("");
            _appliancespec.addClass("d-none");

            _smartmeterspec.removeClass("d-none");
            in_smartmeterspec.removeAttr("required");
            in_smartmeterspec.val("");
            _smartmeterspec.addClass("d-none");

            ipAddress.removeClass("d-none");
            in_ipAddress.attr('required', 'required');

            _alert.removeClass("d-none");
            in_alert.removeAttr("required");
            _alert.addClass("d-none");
        }
        else {

            if ($('#devices_type').val() === 'Light' || $('#devices_type').val() === 'Appliance' || $('#devices_type').val() === 'Climate') {
                _alert.removeClass("d-none");
                in_alert.removeAttr("required");
                in_alert.val("");
                _alert.addClass("d-none");

                notifMess.removeClass("d-none");
                in_notifMess.removeAttr("required");
                notifMess.addClass("d-none");

                ipAddress.removeClass("d-none");
                in_ipAddress.removeAttr("required");
                ipAddress.addClass("d-none");

                _smartmeterspec.removeClass("d-none");
                in_smartmeterspec.removeAttr("required");
                in_smartmeterspec.val("");
                _smartmeterspec.addClass("d-none");

                _appliancespec.removeClass("d-none");
                in_appliancespec.attr("required", "required");

                if ($('#devices_type').val() === 'Light') {
                    $("#appliancespec label[for*='devices_appliancespec']").text("Light type");
                    in_appliancespec
                        .find('option')
                        .remove()
                        .end()
                        .append('<option selected="selected" value="Interior">INTÉRIEUR</option>')
                        .append('<option value="Exterior">EXTÉRIEUR</option>')
                        ;

                }
                else if ($('#devices_type').val() === 'Appliance') {
                    $("#appliancespec label[for*='devices_appliancespec']").text("Appliance type");
                    in_appliancespec
                        .find('option')
                        .remove()
                        .end()
                        .append('<option selected="selected" value="Tv">TV</option>')
                        .append('<option  value="Wash Machine">MACHINE À LAVER</option>')
                        .append('<option  value="Fridge">RÉFRIGÉRATEUR</option>')
                        ;

                }
                else if ($('#devices_type').val() === 'Climate') {
                    $("#appliancespec label[for*='devices_appliancespec']").text("Climate type");
                    in_appliancespec
                        .find('option')
                        .remove()
                        .end()
                        .append('<option selected="selected" value="Clim">CLIMATISEUR</option>')
                        .append('<option  value="Fan">VENTILATEUR</option>')
                        ;

                }
                //alert('Test');
            }
            else if ($('#devices_type').val() === 'Bell' || $('#devices_type').val() === 'Smart Meter') {
                _alert.removeClass("d-none");
                in_alert.removeAttr("required");
                in_alert.val("");
                _alert.addClass("d-none");

                notifMess.removeClass("d-none");
                in_notifMess.removeAttr("required");
                notifMess.addClass("d-none");

                _appliancespec.removeClass("d-none");
                in_appliancespec.removeAttr("required");
                in_appliancespec.val("");
                _appliancespec.addClass("d-none");

                _smartmeterspec.removeClass("d-none");
                in_smartmeterspec.attr("required", "required");

                if ($('#devices_type').val() === 'Bell') {
                    ipAddress.removeClass("d-none");
                    in_ipAddress.attr('required', 'required');

                    _smartmeterspec.removeClass("d-none");
                    in_smartmeterspec.removeAttr("required");
                    in_smartmeterspec.val("");
                    _smartmeterspec.addClass("d-none");
                }
                else {
                    ipAddress.removeClass("d-none");
                    in_ipAddress.removeAttr("required");
                    ipAddress.addClass("d-none");

                    //$("#smartmeterspec label[for*='devices_smartmeterspec']").text("Light type");
                    in_smartmeterspec
                        .find('option')
                        .remove()
                        .end()
                        .append('<option selected="selected" value="Socket">SOCKET</option>')
                        .append('<option value="Home">HOME</option>')
                        ;
                }

            }
            else {
                //Affichage des champs utile aux équipements de type Sensor
                notifMess.removeClass("d-none");
                in_notifMess.attr("required", "required");

                _alert.removeClass("d-none");
                in_alert.attr("required", "required");
                in_alert
                    .find('option')
                    .remove()
                    .end()
                    .append('<option selected="selected" value="Intrusion">INTRUSION</option>')
                    .append('<option value="Fire">INCENDIE</option>')
                    .append('<option value="Flood">INONDATION</option>')
                    .append('<option value="Opening">OUVERTURE</option>')
                    ;


                _appliancespec.removeClass("d-none");
                in_appliancespec.removeAttr("required");
                in_appliancespec.val("");
                _appliancespec.addClass("d-none");

                _smartmeterspec.removeClass("d-none");
                in_smartmeterspec.removeAttr("required");
                in_smartmeterspec.val("");
                _smartmeterspec.addClass("d-none");
            }
        }
    }
});