/* @charset "UTF-8" */
/*
 * author: Jozef Dvorský
 */

var iot;

(function ($) {
	'use strict';

	var namespace;

	namespace = {

		// Vertical align center - Modal
		centerModal: function () {
			var modal = $(this),
				dialog = modal.find('.modal-dialog');
			modal.css('display', 'block');

			// Dividing by two centers the modal exactly, but dividing by three 
			// or four works better for larger screens.
			dialog.css("margin-top", Math.max(0, ($(window).height() - dialog.height()) / 2));
		},

		// Activate/Deactivate unit
		toggleUnit: function (el, status) {

			// Check switch statuses
			var switchValues = JSON.parse(localStorage.getItem('switchValues')) || {};

			// Change basic appearance
			$('[data-unit="' + el + '"]').toggleClass("active");
			$('#' + el).closest("label").toggleClass("checked", status);

			// Update localStorage
			if (localStorage) {
				switchValues[el] = status;
				localStorage.setItem("switchValues", JSON.stringify(switchValues));
			}

		},

		// Switch ON/OFF (checkbox) elemet "on change" functionality
		switchSingle: function (el, status) {

			// Apply extended functionality to unique units
			switch (el) {
				// Security system
				case 'switch-house-lock':
					iot.toggleUnit(el, status);
					// Show "EXIT NOW" only if Security system has been activated
					if (status) {
						iot.armingModal(el);
					}
					break;
				// Security system  - SVG + IMAGE
				case 'switch-house-lock-pin':
					// Show keyboard to activate/deactivate Security system
					iot.armingModalPIN(el, status);
					break;

				case 'switch-light-doorway':
					iot.toggleUnit(el, status);
					$('#doorway-light').toggleClass('active');
					break;

				case 'switch-light-garage':
					iot.toggleUnit(el, status);
					$('#garage-light').toggleClass('active');
					break;

				// Cameras - ON/OFF
				case 'switch-camera-1':
				case 'switch-camera-2':
					// ON status - connect cam
					if (status) {
						iot.connectCam(el);
						// OFF status - disconnect cam
					} else {
						$('[data-unit="' + el + '"] video')[0].pause();
					}
					iot.toggleUnit(el, status);
					break;
				default:
					iot.toggleUnit(el, status);
			}

		},

		// Switch ON/OFF all unit elements in group
		switchGroup: function (parent, action) {

			// Group
			var el = '[data-unit-group="' + parent + '"]',

				// Get last stored states
				switchValues = JSON.parse(localStorage.getItem('switchValues')) || {};

			// Apply changes based on action
			switch (action) {

				case 'all-on':
					$(el + ' [data-unit]').each(function () {

						var key = $(this).data('unit');

						if (!$("#" + key).prop('disabled')) {

							$(this).addClass("active");
							$("#" + key).prop('checked', true);
							$("#" + key).closest("label").addClass("checked");

							switchValues[key] = true;

						}

					});
					break;
				case 'all-off':
					$(el + ' [data-unit]').each(function () {
						var key = $(this).data('unit');

						if (!$("#" + key).prop('disabled')) {

							$(this).removeClass("active");
							$("#" + key).prop('checked', false);
							$("#" + key).closest("label").removeClass("checked");

							switchValues[key] = false;

						}
					});
					break;
			}

			// Save current states in localStorage
			if (localStorage) {
				localStorage.setItem("switchValues", JSON.stringify(switchValues));
			}

		},

		// EXIT NOW - contdown modal
		armingModal: function (unit) {

			// Check if "alarm unit" has class "active" - Android native browser bug
			if (!$('[data-unit="' + unit + '"]').hasClass("active")) {
				$('[data-unit="' + unit + '"]').addClass("active");
			}

			$('#armModal').modal('show');

			// Activate countdown
			$('#armTimer .timer').timer({
				countdown: true,
				format: '%s',
				duration: '10s', // Here you can set custom time to exit
				callback: function () {
					$('#armModal').modal('hide'); // Automaticaly hide modal on countdown end
				}
			});

		},

		// KEYBOARD modal
		armingModalPIN: function (unit, status) {

			// Show keyboard in modal
			$('#armModalPIN').modal('show');

			// Check for 4 entered numbers
			var hits = 0;

			$('[data-action="enter-key"]').click(function () {

				hits += 1;

				if (hits == 4) {
					// Add last dot
					$('#hidden-key').append(" &#9679;");
					$('[data-action="enter-key"]').off();
					iot.toggleUnit(unit, status);

					setTimeout(function () {
						if (status) {
							//Arming
							iot.armingModal(unit);
							// Change part of SVG
							$('#house-disarmed').css('opacity', '0');
							$('#house-armed').css('opacity', '1');

						} else {
							//Disarming
							// Change part of SVG
							$('#house-disarmed').css('opacity', '1');
							$('#house-armed').css('opacity', '0');
						}

						// Hide keyboard modal
						$('#armModalPIN').modal('hide');

						// Reset
						$('#hidden-key').html("&nbsp;");
					}, 300);


				} else {
					// Add dot
					$('#hidden-key').append(" &#9679;");
				}
			});

			// PIN clearing
			$('[data-action="clear-key"]').click(function () {
				hits = 0;
				$('#hidden-key').html("&nbsp;");
			});
		},

		// Open/close garage doors - SVG
		garageDoorsRoll: function (parent, action) {

			var unit = '[data-unit="' + parent + '"]',
				doors = $('#roll-doors');
			switch (action) {
				case 'open':
					$(doors).toggleClass('active');
					$(doors).timer({
						attribute: 'aria-valuenow',
						style: 'stroke-dashoffset',
						style_unit: '',
						duration: $(doors).attr('aria-valuemax') + 's',
						callback: function () {
							$(doors).timer('remove');
							$(doors).toggleClass('active');
							$(unit).toggleClass('active');
							$(unit + ' .status').html('Open');
							$(unit + ' [data-action="pause"]').hide();
							$(unit + ' [data-action="close"]').show();
						}
					});
					$(unit + ' .status').toggleClass('text-secondary');
					$(unit + ' .status').html('In progress');
					$(unit + ' [data-action="open"]').hide();
					$(unit + ' [data-action="pause"]').show();
					break;
				case 'pause':
					$(doors).timer('pause');
					$(unit + ' .status').html('Paused');
					$(unit + ' [data-action="pause"]').hide();
					$(unit + ' [data-action="resume"]').show();
					break;
				case 'resume':
					$(doors).timer('resume');
					$(unit + ' .status').html('In progress');
					$(unit + ' [data-action="resume"]').hide();
					$(unit + ' [data-action="pause"]').show();
					break;
				case 'close':
					$(doors).toggleClass('active');
					$(doors).timer({
						countdown: true,
						attribute: 'aria-valuenow',
						style: 'stroke-dashoffset',
						style_unit: '',
						duration: $(doors).attr('aria-valuemax') + 's',
						callback: function () {
							$(doors).timer('remove');
							$(unit).toggleClass('active');
							$(doors).toggleClass('active');
							$(unit + ' .status').toggleClass('text-secondary');
							$(unit + ' .status').html('Closed');
							$(unit + ' [data-action="pause"]').hide();
							$(unit + ' [data-action="open"]').show();
						}
					});
					$(unit + ' .status').html('In progress');
					$(unit + ' [data-action="close"]').hide();
					$(unit + ' [data-action="pause"]').show();
					break;
			}
		},

		// Open/close garage doors - Progress bar
		garageDoors: function (parent, action) {

			var el = '[data-unit="' + parent + '"]';
			switch (action) {
				case 'open':
					$(el + ' .timer').timer({
						attribute: 'aria-valuenow',
						style: 'width',
						style_unit: '%',
						duration: $(el + ' .timer').attr('aria-valuemax') + 's',
						callback: function () {
							$(el + ' .timer').timer('remove');
							$(el).toggleClass('active');
							$(el + ' .status').html('Open');
							$(el + ' [data-action="pause"]').hide();
							$(el + ' [data-action="close"]').show();
						}
					});
					$(el + ' .status').toggleClass('text-secondary');
					$(el + ' .status').html('In progress');
					$(el + ' [data-action="open"]').hide();
					$(el + ' [data-action="pause"]').show();
					break;
				case 'pause':
					$(el + ' .timer').timer('pause');
					$(el + ' .status').html('Paused');
					$(el + ' [data-action="pause"]').hide();
					$(el + ' [data-action="resume"]').show();
					break;
				case 'resume':
					$(el + ' .timer').timer('resume');
					$(el + ' .status').html('In progress');
					$(el + ' [data-action="resume"]').hide();
					$(el + ' [data-action="pause"]').show();
					break;
				case 'close':
					$(el + ' .timer').timer({
						countdown: true,
						attribute: 'aria-valuenow',
						style: 'width',
						style_unit: '%',
						duration: $(el + ' .timer').attr('aria-valuemax') + 's',
						callback: function () {
							$(el + ' .timer').timer('remove');
							$(el).toggleClass('active');
							$(el + ' .status').toggleClass('text-secondary');
							$(el + ' .status').html('Closed');
							$(el + ' [data-action="pause"]').hide();
							$(el + ' [data-action="open"]').show();
						}
					});
					//          $(el + ' .status').toggleClass('text-warning');
					$(el + ' .status').html('In progress');
					$(el + ' [data-action="close"]').hide();
					$(el + ' [data-action="pause"]').show();
					break;
			}
		},

		// Pause/resume Wash machine Program
		washMachine: function (parent, action) {

			var el = '[data-unit="' + parent + '"]';
			switch (action) {
				case 'pause':
					$('#wash-machine').timer('pause');
					$(el + ' .status').html('Paused');
					$(el + ' .status').addClass('text-muted');
					$(el + ' [data-action="pause"]').hide();
					$(el + ' [data-action="resume"]').show();
					break;
				case 'resume':
					$('#wash-machine').timer('resume');
					$(el + ' .status').html('ON');
					$(el + ' .status').removeClass('text-muted');
					$(el + ' [data-action="resume"]').hide();
					$(el + ' [data-action="pause"]').show();
					break;
			}
		},



		// FAB button position base on scrollbar visibility
		positionFab: function () {

			var main = $('#main'),
				sis = main.get(0) ? main.get(0).scrollHeight > main.innerHeight() : false;

			if (sis) {
				$('#info-toggler').css('right', '40px');
			}
		},
		// Connect to camera with preloader. Use ajax request instead of timeout function.
		connectCam: function (cam) {
			$('[data-unit="' + cam + '"] .card-preloader').css("display", "block");
			setTimeout(function () {
				$('[data-unit="' + cam + '"] .card-preloader').fadeOut();
				$('[data-unit="' + cam + '"] video')[0].play();
			}, 800);
		},
		// Clear active countdown 
		clearCountdown: function () {
			$('#' + this.id + '  .timer').timer('remove');
		},
		// Clear keyboard
		clearKeyboard: function () {

			$('#hidden-key').html("&nbsp;");
			$('[data-action="enter-key"]').off();

			// Check if action is initialized by switch (checkbox element)
			var checkbox = $("#switch-house-lock-pin"),
				isCheckbox = checkbox.is(':checkbox');

			if (isCheckbox) {

				// Change back checkbox status to prevert incorrect behaviors
				var isChecked = checkbox.is(':checked');

				if (isChecked) {
					checkbox.prop('checked', false);

				} else {
					checkbox.prop('checked', true);

				}
			}
			$('#armModalPIN').modal('hide');

		}
	};

	window.iot = namespace;

}(this.jQuery));
