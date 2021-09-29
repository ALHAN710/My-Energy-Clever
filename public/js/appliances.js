

// Wash machine controls
$('.wash-control').click(function () {

    var target = $(this).closest('.timer-controls').data('controls');
    var action = $(this).data('action');

    iot.washMachine(target, action);
});

// Data binding for numeric representation of Fridge range slider
$(document).on('input', 'input[type="range"]#fridge-temp', function () {
    var temperatureFar = this.value;
    var temperatureCel = (temperatureFar - 32) * 5 / 9;
    var roundCel = Number(Math.round(temperatureCel + 'e2') + 'e-2');
    $('[data-rangeslider="' + this.id + '"] #fridge-temp-F').html(temperatureFar);
    $('[data-rangeslider="' + this.id + '"] #fridge-temp-C').html(roundCel);
});

// Data binding for numeric representation of TV Volumee range slider
$(document).on('input', 'input[type="range"].volume', function () {
    $('[data-rangeslider="' + this.id + '"] .range-output').html(this.value);
});
