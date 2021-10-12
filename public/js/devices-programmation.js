$('.input-search').on('keyup', function () {
  var rex = new RegExp($(this).val(), 'i');
  $('.todo-box .todo-item').hide();
  $('.todo-box .todo-item').filter(function () {
    return rex.test($(this).text());
  }).show();
});

const taskViewScroll = new PerfectScrollbar('.task-text', {
  wheelSpeed: .5,
  swipeEasing: !0,
  minScrollbarLength: 40,
  maxScrollbarLength: 300,
  suppressScrollX: true
});

function dynamicBadgeNotification(setTodoCategoryCount) {
  var todoCategoryCount = setTodoCategoryCount;

  // Get Parents Div(s)
  var get_ParentsDiv = $('.todo-item');
  var get_TodoAllListParentsDiv = $('.todo-item.all-list');
  var get_TodoCompletedListParentsDiv = $('.todo-item.todo-task-done');
  var get_TodoImportantListParentsDiv = $('.todo-item.todo-task-important');

  // Get Parents Div(s) Counts
  var get_TodoListElementsCount = get_TodoAllListParentsDiv.length;
  var get_CompletedTaskElementsCount = get_TodoCompletedListParentsDiv.length;
  var get_ImportantTaskElementsCount = get_TodoImportantListParentsDiv.length;

  // Get Badge Div(s)
  var getBadgeTodoAllListDiv = $('#all-list .todo-badge');
  var getBadgeCompletedTaskListDiv = $('#todo-task-done .todo-badge');
  var getBadgeImportantTaskListDiv = $('#todo-task-important .todo-badge');


  if (todoCategoryCount === 'allList') {
    if (get_TodoListElementsCount === 0) {
      getBadgeTodoAllListDiv.text('');
      return;
    }
    if (get_TodoListElementsCount > 9) {
      getBadgeTodoAllListDiv.css({
        padding: '2px 0px',
        height: '25px',
        width: '25px'
      });
    } else if (get_TodoListElementsCount <= 9) {
      getBadgeTodoAllListDiv.removeAttr('style');
    }
    getBadgeTodoAllListDiv.text(get_TodoListElementsCount);
  }
  else if (todoCategoryCount === 'completedList') {
    if (get_CompletedTaskElementsCount === 0) {
      getBadgeCompletedTaskListDiv.text('');
      return;
    }
    if (get_CompletedTaskElementsCount > 9) {
      getBadgeCompletedTaskListDiv.css({
        padding: '2px 0px',
        height: '25px',
        width: '25px'
      });
    } else if (get_CompletedTaskElementsCount <= 9) {
      getBadgeCompletedTaskListDiv.removeAttr('style');
    }
    getBadgeCompletedTaskListDiv.text(get_CompletedTaskElementsCount);
  }
  else if (todoCategoryCount === 'importantList') {
    if (get_ImportantTaskElementsCount === 0) {
      getBadgeImportantTaskListDiv.text('');
      return;
    }
    if (get_ImportantTaskElementsCount > 9) {
      getBadgeImportantTaskListDiv.css({
        padding: '2px 0px',
        height: '25px',
        width: '25px'
      });
    } else if (get_ImportantTaskElementsCount <= 9) {
      getBadgeImportantTaskListDiv.removeAttr('style');
    }
    getBadgeImportantTaskListDiv.text(get_ImportantTaskElementsCount);
  }
}

new dynamicBadgeNotification('allList');
new dynamicBadgeNotification('completedList');
new dynamicBadgeNotification('importantList');

/*
  ====================
    Quill Editor
  ====================
*/

/*var quill = new Quill('#taskdescription', {
  modules: {
    toolbar: [
      [{ header: [1, 2, false] }],
      ['bold', 'italic', 'underline'],
      ['image', 'code-block']
    ]
  },
  placeholder: 'Compose an epic...',
  theme: 'snow'  // or 'bubble'
});*/

$('#addScenarioModal').on('hidden.bs.modal', function (e) {
  //Remise à zéro des champs du formulaire
  initInputScenarioModal(null, true);

  //On reset les data du device dans le modal
  $('#device-modal').attr('data-name', '')
  $('#device-modal').attr('data-device', '')
  $('#device-modal').attr('data-address', '')
})

$('.mail-menu').on('click', function (event) {
  $('.tab-title').addClass('mail-menu-show');
  $('.mail-overlay').addClass('mail-overlay-show');
})

$('.mail-overlay').on('click', function (event) {
  $('.tab-title').removeClass('mail-menu-show');
  $('.mail-overlay').removeClass('mail-overlay-show');
})

//Gestion du click sur le bouton Ajouter un Scénario
$('#addTask').on('click', function (event) {
  event.preventDefault();
  //Modification du Titre du Modal #addScenarioModal
  $('#addScenarioModalTitle').children('h5').html('Ajout d\'un Scénario de fonctionnement pour l\'équipement <strong class="badge badge-dark">#' + select_device_name + '</strong>');

  //On informe le Modal sur le device dont on veut modifier le scénario, ainsi que son address pour la wsConv
  $('#device-modal').attr('data-name', select_device_name)
  $('#device-modal').attr('data-device', select_device_slug)
  $('#device-modal').attr('data-address', select_address)

  $('.addTask').show();//Affichage de bouton Enregistrer du modal #addScenarioModal
  $('.addTask-only').show();//Affichage des radio button appliquer à ?
  $('.edit-scenario').hide();//On masque le bouton Modifier du modal #addScenarioModal
  $('#addScenarioModal').modal('show');//Affichage du modal #addScenarioModal
  const ps = new PerfectScrollbar('.todo-box-scroll', {
    suppressScrollX: true
  });
});

const ps = new PerfectScrollbar('.todo-box-scroll', {
  suppressScrollX: true
});

const todoListScroll = new PerfectScrollbar('.todoList-sidebar-scroll', {
  suppressScrollX: true
});

function checkCheckbox() {
  $('.todo-item input[type="checkbox"]').click(function () {
    if ($(this).is(":checked")) {
      $(this).parents('.todo-item').addClass('todo-task-done');
    }
    else if ($(this).is(":not(:checked)")) {
      $(this).parents('.todo-item').removeClass('todo-task-done');
    }
    new dynamicBadgeNotification('completedList');
  });
}

//Fonction de gestion du click sur le bouton Supprimer un scénario
function deleteDropdown() {
  $('.action-dropdown .dropdown-menu .delete.dropdown-item').click(function () {

    var getTodoParent = $(this).parents('.todo-item');
    var getTodoClass = getTodoParent.attr('class');

    //var device_name = String($(this).attr('data-name'));
    var device_slug = String($(this).attr('data-device'));
    var device_address = String($(this).attr('data-address'));
    var device_sc = parseInt($(this).attr('data-sc'));
    var scenarioOBJTemp = tab[device_slug]['sc'][device_sc];
    console.log("== Before ==");
    var tabTemp = JSON.parse(JSON.stringify(tab[device_slug]));
    console.log(tabTemp);
    tab[device_slug]['nb']--;
    tab[device_slug]['sc'].splice($.inArray(scenarioOBJTemp, tab[device_slug]['sc']), 1);
    console.log("== After ==");
    console.log(tab[device_slug]);

    swal.queue([{
      title: 'Confirmation de Suppression',
      confirmButtonText: 'Confirmer',
      cancelButtonText: 'Annuler',
      text: 'Etes-vous sûr de vouloir supprimer ce scénario ?',
      showCancelButton: true,
      showLoaderOnConfirm: true,
      //confirmButtonClass: 'btn btn-primary',
      //cancelButtonClass: 'btn btn-danger ml-2',
      preConfirm: function () {
        return new Promise(function (resolve) {

          jsonProg = JSON.stringify(tab[device_slug]);
          var $url = device_prog_url_prefix + '/' + device_slug;
          var $data = JSON.stringify({
            "prog": jsonProg,
          });

          $.ajax({
            type: "POST", // method type
            contentType: "application/json; charset=utf-8",
            url: $url, // /Target function that will be return result
            data: $data, // parameter pass data is parameter name param is value
            dataType: "json",
            timeout: 120000, // 64241
            success: function (result) {
              // $('.fa-sync').removeClass('fa-spin');
              //console.log(result);

              swal({
                type: 'success',
                title: 'La suppression a été effectué avec succès !',
                // html: 'Submitted email: ' + email
              });

              console.log("Delete done");
              //console.log("Retour prg " + JSON.stringify(data));

              //mess.From = "user";
              mess.To = device_address;
              mess.Object = "Programming";
              mess.message = jsonProg;
              doSend(JSON.stringify(mess));

              setTimeout(function () {
                resolve()
              }, 1000);

              var getFirstClass = getTodoClass.split(' ')[1];

              getTodoParent.removeClass(getFirstClass);

              console.log("add class trash")
              getTodoParent.addClass('todo-task-trash');

            },
            error: function (result) {
              // console.log("Error");
              // console.log(result);
              tab[device_slug] = Object.assign(tabTemp)
              // tab[device_slug]['sc'].push(scenarioOBJTemp);
              // tab[device_slug]['nb']++;
              //console.log(tab[device_slug]['sc']);
              swal('Oups...', "Une erreur s'est produite !", 'error'
                // footer: '<a href>Why do I have this issue?</a>'
              );

              setTimeout(function () {
                resolve()
              }, 5000);
            }
          });
        })
      },
      allowOutsideClick: false
    }]).then((result) => {
      if (result.isConfirmed) {

      } else if (
        /* Read more about handling dismissals below */
        result.dismiss === Swal.DismissReason.cancel
      ) {
        tab[device_slug]['sc'].push(scenarioOBJTemp);
        tab[device_slug]['nb']++;
        //console.log(tab[device_slug]['sc']);
        swal('Annulation', "Suppression annulé !", 'error'
          // footer: '<a href>Why do I have this issue?</a>'
        );

        setTimeout(function () {
          resolve()
        }, 5000);
      }
    });;

    /*if (!$(this).parents('.todo-item').hasClass('todo-task-trash')) {

      var getTodoParent = $(this).parents('.todo-item');
      var getTodoClass = getTodoParent.attr('class');

      var getFirstClass = getTodoClass.split(' ')[1];
      var getSecondClass = getTodoClass.split(' ')[2];
      var getThirdClass = getTodoClass.split(' ')[3];

      if (getFirstClass === 'all-list') {
        getTodoParent.removeClass(getFirstClass);
      }
      if (getSecondClass === 'todo-task-done' || getSecondClass === 'todo-task-important') {
        getTodoParent.removeClass(getSecondClass);
      }
      if (getThirdClass === 'todo-task-done' || getThirdClass === 'todo-task-important') {
        getTodoParent.removeClass(getThirdClass);
      }
      console.log("add class trash")
      $(this).parents('.todo-item').addClass('todo-task-trash');
    } else if ($(this).parents('.todo-item').hasClass('todo-task-trash')) {
      $(this).parents('.todo-item').removeClass('todo-task-trash');
    }*/
    // new dynamicBadgeNotification('allList');
    // new dynamicBadgeNotification('completedList');
    // new dynamicBadgeNotification('importantList');
  });
}

function deletePermanentlyDropdown() {
  $('.action-dropdown .dropdown-menu .permanent-delete.dropdown-item').on('click', function (event) {
    event.preventDefault();
    if ($(this).parents('.todo-item').hasClass('todo-task-trash')) {
      $(this).parents('.todo-item').remove();
    }
  });
}

function reviveMailDropdown() {
  $('.action-dropdown .dropdown-menu .revive.dropdown-item').on('click', function (event) {
    event.preventDefault();
    if ($(this).parents('.todo-item').hasClass('todo-task-trash')) {
      var getTodoParent = $(this).parents('.todo-item');
      var getTodoClass = getTodoParent.attr('class');
      var getFirstClass = getTodoClass.split(' ')[1];
      $(this).parents('.todo-item').removeClass(getFirstClass);
      $(this).parents('.todo-item').addClass('all-list');
      $(this).parents('.todo-item').hide();
    }
    new dynamicBadgeNotification('allList');
    new dynamicBadgeNotification('completedList');
    new dynamicBadgeNotification('importantList');
  });
}

function importantDropdown() {
  $('.important').click(function () {
    if (!$(this).parents('.todo-item').hasClass('todo-task-important')) {
      $(this).parents('.todo-item').addClass('todo-task-important');
      $(this).html('Back to List');
    }
    else if ($(this).parents('.todo-item').hasClass('todo-task-important')) {
      $(this).parents('.todo-item').removeClass('todo-task-important');
      $(this).html('Important');
      $(".list-actions#all-list").trigger('click');
    }
    new dynamicBadgeNotification('importantList');
  });
}

function priorityDropdown() {
  $('.priority-dropdown .dropdown-menu .dropdown-item').on('click', function (event) {

    var getClass = $(this).attr('class').split(' ')[1];
    var getDropdownClass = $(this).parents('.p-dropdown').children('.dropdown-toggle').attr('class').split(' ')[1];
    $(this).parents('.p-dropdown').children('.dropdown-toggle').removeClass(getDropdownClass);

    $(this).parents('.p-dropdown').children('.dropdown-toggle').addClass(getClass);
  })
}

//Procédure d'initialisation des inputs du modal de création ou d'édition d'un scénario
function initInputScenarioModal(sc, reset = false) {

  if (reset === false) {//Initialisation par les valeurs du scénario à modifier
    var h = "";
    var m = "";
    var str = "";
    //Initialisation du nom
    $('#scenario-name').val(sc.name);

    //Initialisation de l'heure de Début
    if (parseInt(sc.stAt[0]) >= 0 && parseInt(sc.stAt[0]) < 10) {
      h = "0" + sc.stAt[0];
      //console.log('time_startAth = ' + h);
    }
    else { h = sc.stAt[0]; }

    if (parseInt(sc.stAt[1]) >= 0 && parseInt(sc.stAt[1]) < 10) {
      m = "0" + sc.stAt[1];
      //console.log('time_startAtm = ' + m);
    }
    else {
      m = "" + sc.stAt[1];
      //console.log('time_startAtm = ' + m);
    }
    str = String(h) + ":" + String(m);
    $('#time_startAt').val(String(str));

    //Initialisation de l'heure de Fin
    h = "";
    m = "";
    if (parseInt(sc.enAt[0]) >= 0 && parseInt(sc.enAt[0]) < 10) {
      h = "0" + sc.enAt[0];
      //console.log('time_endAth = ' + h);
    }
    else {
      h = "" + sc.enAt[0];
      //console.log('time_endAth = ' + h);
    }
    if (parseInt(sc.enAt[1]) >= 0 && parseInt(sc.enAt[1]) < 10) {
      m = "0" + sc.enAt[1];
      //console.log('time_endAtm = ' + m);
    }
    else {
      m = "" + sc.enAt[1];
      //console.log('time_endAtm = ' + m);
    }
    str = String(h) + ":" + String(m);
    //console.log('time_endAt = ' + String(str));
    $('#time_endAt').val(String(str));

    //Initialisation du checbox AUTO-ON
    if (sc.auto === 1 || String(sc.auto) === "1") { $('#auto_on_check').prop("checked", true); }
    else { $('#auto_on_check').prop("checked", false); }

    //Initialisation du checbox Réallumage AUTO-ON
    if (sc.autoc === 1 || String(sc.autoc) === "1") { $('#cycle-auto_on_check').prop("checked", true); }
    else { $('#cycle-auto_on_check').prop("checked", false); }

    //Initialisation du TIME-ON
    $('#timeON').val(sc.ton);

    //Initialisation du TIME-OFF
    $('#timeOFF').val(sc.toff);

    //Initialisation des checkboxes des jours de la semaine
    var apply_day_str = "apply_day_check"
    for (let i = 0; i < 7; i++) {
      apply_day_tmp = "#" + apply_day_str + i

      //if (jQuery.inArray(i, sc.day) >= 0) $(apply_day_tmp).prop("checked", true)
      //if (sc.day.includes(i)) $(apply_day_tmp).prop("checked", true)
      if (sc.day[i] == 1) $(apply_day_tmp).prop("checked", true)
      else $(apply_day_tmp).prop("checked", false)

    }

  } else {//Initialisation par les valeurs du scénario à zéro
    $('#scenario-name').val("")
    $('#time_startAt').val("");
    $('#time_endAt').val("");
    $('#auto_on_check').prop("checked", false);
    $('#cycle-auto_on_check').prop("checked", false);
    $('#timeON').val("");
    $('#timeOFF').val("");
    var apply_day_str = "apply_day_check"
    for (let index = 0; index < 7; index++) {
      apply_day_tmp = "#" + apply_day_str + index

      $(apply_day_tmp).prop("checked", false)

    }
    $('#device-modal').attr('data-device', '')
    $('#device-modal').attr('data-sc', '')
    $('#device-modal').attr('data-address', '')
  }
}

//Procédure du CRUD des scénari d'un device
function crudDeviceScenario(jsonProg, deviceSlug, deviceAddress = 'switch-light-') {
  var $url = device_prog_url_prefix + '/' + deviceSlug
  var $data = JSON.stringify({
    "prog": jsonProg,
  });

  console.log($data);
  //$('.card-preloader').fadeIn();
  $.ajax({
    type: "POST",//method type
    contentType: "application/json; charset=utf-8",
    url: $url,//Target function that will be return result
    data: $data,//parameter pass data is parameter name param is value 
    dataType: "json",
    success: function (data) {
      //alert("Success");
      console.log(data);

      console.log("Prog data done");
      console.log("Retour prg " + JSON.stringify(data));

      //mess.From = "user";
      mess.To = deviceAddress;
      mess.Object = "Programming";
      mess.message = jsonProg;
      doSend(JSON.stringify(mess));
      //$('#iot-preloader').fadeOut();
      //alert('Programmation Done');
      return true

    },
    error: function (result) {
      console.log(result);
      console.log("Prog data don't save ");
      //$('#iot-preloader').fadeOut();
      /*swal('Oups...', "Une erreur s'est produite !", 'error'
        // footer: '<a href>Why do I have this issue?</a>'
      );
      setTimeout(function () {
        resolve()
      }, 5000);*/
      return false;
      //alert('Programmation don\'t save');
    }
  });
}

//Procédure de gestion du click sur le bouton Modifier un scénario
function editDropdown() {
  $('.action-dropdown .dropdown-menu .edit.dropdown-item').click(function () {

    event.preventDefault();

    /**
     * Structure de l'objet tab
     * tab = {
     *  device_slug: {
     *                  nb: int,
     *                  sc: [
     *                        {
     *                          auto: 0-1
     *                          day: [sun..sat],
     *                          enAt: [hh, mm],
     *                          mod: 0-1,
     *                          name: 'Scenario name',
     *                          stAt: [hh, mm],
     *                          toff: int,
     *                          ton: int
     *                        }
     *                      ]
     *               }
     * }
     */

    var device_name = String($(this).attr('data-name'));
    var device_slug = String($(this).attr('data-device'));
    var device_address = String($(this).attr('data-address'));
    var device_sc = parseInt($(this).attr('data-sc'));
    var scenarioJSONObj = tab[device_slug]['sc'][parseInt(device_sc)];

    var $_outerThis = $(this);
    //console.log(tab[device_slug]['sc'][parseInt(device_sc)])
    //console.log(scenarioJSONObj)

    //On informe le Modal sur le device(nom et slug) et son scénario dont on veut modifier, ainsi que son address pour la wsConv
    $('#device-modal').attr('data-name', device_name)
    $('#device-modal').attr('data-device', device_slug)
    $('#device-modal').attr('data-sc', device_sc)
    $('#device-modal').attr('data-address', device_address)
    console.log($('#device-modal').attr('data-address'))
    console.log(device_address)

    //Modification du Titre du Modal #addScenarioModal
    $('#addScenarioModalTitle').children('h5').text('Modification du scénario : ' + scenarioJSONObj.name);

    $('.addTask-only').hide();//On masque les radio button appliquer à ?
    $('.addTask').hide();//On masque le bouton Enregistrer du modal #addScenarioModal
    $('.edit-scenario').show();//Affichage du bouton Modifier du modal #addScenarioModal

    initInputScenarioModal(scenarioJSONObj, false)

    $('.edit-scenario').off('click').on('click', function (event) {
      console.log($('#device-modal').attr('data-address'))
      var scenarioOBJTemp = tab[device_slug]['sc'][device_sc];
      var apply_day_str = "apply_day_check";
      for (let i = 0; i < 7; i++) {
        apply_day_tmp = "#" + apply_day_str + i

        if ($(apply_day_tmp).is(':checked')) tab[device_slug]['sc'][device_sc].day[i] = 1;
        else tab[device_slug]['sc'][device_sc].day[i] = 0;

      }

      tab[device_slug]['sc'][device_sc].name = $('#scenario-name').val();
      tab[device_slug]['sc'][device_sc].auto = $('#auto_on_check').is(":checked") ? 1 : 0;
      tab[device_slug]['sc'][device_sc].autoc = $('#cycle-auto_on_check').is(":checked") ? 1 : 0;
      tab[device_slug]['sc'][device_sc].ton = parseInt($('#timeON').val());
      tab[device_slug]['sc'][device_sc].toff = parseInt($('#timeOFF').val());
      tab[device_slug]['sc'][device_sc].stAt = $('#time_startAt').val();
      tab[device_slug]['sc'][device_sc].enAt = $('#time_endAt').val();
      var hh = "";
      var mm = "";
      if ($('#time_startAt').val() !== "" && $('#time_endAt').val() !== "") { tab[device_slug]['sc'][device_sc].mod = 1; }
      else { tab[device_slug]['sc'][device_sc].mod = 0; }

      if ($('#time_startAt').val() !== "") {
        hh = $('#time_startAt').val()[0] + $('#time_startAt').val()[1];
        mm = $('#time_startAt').val()[3] + $('#time_startAt').val()[4];
        tab[device_slug]['sc'][device_sc].stAt = [parseInt(hh), parseInt(mm)]

      }

      else {
        hh = -1;
        mm = -1;
        tab[device_slug]['sc'][device_sc].stAt = [parseInt(hh), parseInt(mm)]
      }

      if ($('#time_endAt').val() !== "") {
        hh = $('#time_endAt').val()[0] + $('#time_endAt').val()[1];
        mm = $('#time_endAt').val()[3] + $('#time_endAt').val()[4];
        tab[device_slug]['sc'][device_sc].enAt = [parseInt(hh), parseInt(mm)]
      }
      else {
        hh = -1;
        mm = -1;
        tab[device_slug]['sc'][device_sc].enAt = [parseInt(hh), parseInt(mm)]
      }

      //On formate la chaîne de caractères contenant les jours d'application du scénario
      var day = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
      var scenario_applying_days_strHtml = 'Jours : ';
      var apply_day_str = "apply_day_check";
      for (let index = 1; index < 7; index++) {
        apply_day_tmp = "#" + apply_day_str + index

        if ($(apply_day_tmp).is(':checked')) {
          scenario_applying_days_strHtml += day[index];
          scenario_applying_days_strHtml += ', ';
        }

      }
      apply_day_tmp = "#" + apply_day_str + '0'
      if ($(apply_day_tmp).is(':checked')) scenario_applying_days_strHtml += day[0];

      var $_innerThis = $(this);
      var $_device_name = $('#device-modal').attr('data-name');
      var $_scenario_name = $('#scenario-name').val();//On récupère le nom du scénario
      var $_time_startAt = $('#time_startAt').val();//On récupère l'heure de démarrage du scénario
      var $_time_endAt = $('#time_endAt').val();//On récupère l'heure de fin du scénario
      var $_demarrage_auto = $('#auto_on_check').is(':checked') ? 1 : 0;//On récupère l'état du checkbox du démarrage automatique
      var $_reallumage_auto = $('#cycle-auto_on_check').is(':checked') ? 1 : 0;//On récupère l'état du checkbox du réallumage automatique
      var $_time_on = $('#timeON').val();//On récupère la durée ON
      var $_time_off = $('#timeOFF').val();//On récupère la durée OFF

      //Requête de MAJ du programme du device dans la BDD
      $('#iot-preloader').fadeIn();
      //crudDeviceScenario(jsonProg = JSON.stringify(tab[device_slug]), deviceSlug = $('#device-modal').attr('data-device'), deviceAddress = $('#device-modal').attr('data-address'))
      jsonProg = JSON.stringify(tab[device_slug]);
      var $url = device_prog_url_prefix + '/' + $('#device-modal').attr('data-device')
      var $data = JSON.stringify({
        "prog": jsonProg,
      });

      //console.log($data);
      //$('.card-preloader').fadeIn();
      $.ajax({
        type: "POST",//method type
        contentType: "application/json; charset=utf-8",
        url: $url,//Target function that will be return result
        data: $data,//parameter pass data is parameter name param is value 
        dataType: "json",
        success: function (data) {
          //alert("Success");
          //console.log(data);

          console.log("Programmation done");
          //console.log("Retour prg " + JSON.stringify(data));

          //mess.From = "user";
          mess.To = String(device_address);
          mess.Object = "Programming";
          mess.message = jsonProg;
          doSend(JSON.stringify(mess));
          mess.message = ""

          var scenario_name_strHtml = $_scenario_name + ' <small class="badge badge-dark">#' + $_device_name + '</span>';
          var scenario_time_strHtml = 'Début : ' + $_time_startAt + ', Fin : ' + $_time_endAt;
          var sceanrio_start_auto_strHtml = 'Démarrage Auto : <span class="badge badge-' + ($_demarrage_auto === 1 ? 'success">Oui' : 'danger">Non' + '</span>');
          var sceanrio_run_autoon_strHtml = 'Réallumage Auto : <span class="mt-1 badge badge-' + ($_reallumage_auto === 1 ? 'success">Oui' : 'danger">Non' + '</span>');
          var scenario_timeON_OFF_strHtml = 'Durée ON : ' + $_time_on + ' mins, Durée OFF : ' + $_time_off + ' mins';

          var $_scenarioEditedName = $_outerThis.parents('.todo-item').children().find('.todo-heading').html(scenario_name_strHtml);
          var $_scenarioEditedTime = $_outerThis.parents('.todo-item').children().find('.startAt-endAt').html(scenario_time_strHtml);
          var $_scenarioEditedStartAuto = $_outerThis.parents('.todo-item').children().find('.demarrage-auto').html(sceanrio_start_auto_strHtml);
          var $_scenarioEditedRunAutoOn = $_outerThis.parents('.todo-item').children().find('.reallumage-auto').html(sceanrio_run_autoon_strHtml);
          var $_scenarioEditedTimeONOFF = $_outerThis.parents('.todo-item').children().find('.time-on-off').html(scenario_timeON_OFF_strHtml);
          var $_scenarioEditedDays = $_outerThis.parents('.todo-item').children().find('.todo-text').html(scenario_applying_days_strHtml);

          var $_scenarioEditedNameDataAttr = $_outerThis.parents('.todo-item').children().find('.todo-heading').attr('data-todoHeading', $_scenario_name);
          // var $_scenarioEditedTextDataAttr = $_outerThis.parents('.todo-item').children().find('.todo-text').attr('data-todoText', $_textDelta);
          var $_scenarioEditedTextDataAttr = $_outerThis.parents('.todo-item').children().find('.todo-text').attr('data-todoHtml', '<p>' + scenario_applying_days_strHtml + '</p>');

          $('#iot-preloader').fadeOut();
          //alert('Programmation Done');
          return true

        },
        error: function (result) {
          console.log(result);
          console.log("Prog data don't save ");
          //$('#iot-preloader').fadeOut();
          tab[device_slug]['sc'][device_sc] = scenarioOBJTemp;
          $('#iot-preloader').fadeOut();
          swal('Oups...', "Une erreur s'est produite !", 'error'
            // footer: '<a href>Why do I have this issue?</a>'
          );
          return false;
          //alert('Programmation don\'t save');
        }
      });
      $('#addScenarioModal').modal('hide');
    })

    $('#addScenarioModal').modal('show');
  })
}

function todoItem() {
  $('.todo-item .todo-content').on('click', function (event) {
    event.preventDefault();

    var $_taskTitle = $(this).find('.todo-heading').attr('data-todoHeading');
    var $todoHtml = $(this).find('.todo-text').attr('data-todoHtml');

    $('.task-heading').text($_taskTitle);
    $('.task-text').html($todoHtml);

    $('#todoShowListItem').modal('show');
  });
}

var $btns = $('.list-actions').click(function () {

  var id = '#' + this.id + '-id';
  // On récupère les informations du device selectionné dans cette catégorie
  select_device_name = $(id).find(':selected').data('name');
  select_device_type = this.id;
  select_device_slug = $(id).val();
  select_address = $(id).find(':selected').data('address');
  var $el = $('.' + this.id).fadeIn();
  var _id = this.id + '-id';
  $('select').each(function () {
    if ($(this).attr('id') == _id) {
      //console.log('Show');
      console.log($(this).val())
      $(this).parents('.bootstrap-select').show()
      var $el = $('.' + $(this).val()).fadeIn();
      $("#ct > div").not($el).hide();
    }
    else {
      //console.log('hide');
      $(this).parents('.bootstrap-select').hide()

    }

  })
  $('#ct > div').not($el).hide();
  //$(".search > div").not($el).hide();

  //console.log($btns)
  $btns.removeClass('active');
  $(this).addClass('active');
})

//checkCheckbox();
deleteDropdown();
//deletePermanentlyDropdown();
//reviveMailDropdown();
//importantDropdown();
//priorityDropdown();
editDropdown();
todoItem();

//Gestion du click sur le bouton Enregistrer un nouveau scénario
$(".addTask").click(function () {

  //var scenarioOBJTemp = tab[device_slug]['sc'][device_sc];
  var scenarioOBJTemp = {
    "name": "",
    "mod": 0,
    //"state": JSONObj.state,
    "auto": 0,
    "autoc": 0,
    "stAt": [],
    "enAt": [],
    "ton": 0,
    "toff": 0,
    "day": []
  }
  var apply_day_str = "apply_day_check";
  for (let i = 0; i < 7; i++) {
    apply_day_tmp = "#" + apply_day_str + i

    if ($(apply_day_tmp).is(':checked')) scenarioOBJTemp.day[i] = 1;
    else scenarioOBJTemp.day[i] = 0;

  }

  scenarioOBJTemp.name = $('#scenario-name').val();
  scenarioOBJTemp.auto = $('#auto_on_check').is(":checked") ? 1 : 0;
  scenarioOBJTemp.autoc = $('#cycle-auto_on_check').is(":checked") ? 1 : 0;
  scenarioOBJTemp.ton = parseInt($('#timeON').val());
  scenarioOBJTemp.toff = parseInt($('#timeOFF').val());
  scenarioOBJTemp.stAt = $('#time_startAt').val();
  scenarioOBJTemp.enAt = $('#time_endAt').val();
  var hh = "";
  var mm = "";
  if ($('#time_startAt').val() !== "" && $('#time_endAt').val() !== "") { scenarioOBJTemp.mod = 1; }
  else { scenarioOBJTemp.mod = 0; }

  if ($('#time_startAt').val() !== "") {
    hh = $('#time_startAt').val()[0] + $('#time_startAt').val()[1];
    mm = $('#time_startAt').val()[3] + $('#time_startAt').val()[4];
    scenarioOBJTemp.stAt = [parseInt(hh), parseInt(mm)]

  }

  else {
    hh = -1;
    mm = -1;
    scenarioOBJTemp.stAt = [parseInt(hh), parseInt(mm)]
  }

  if ($('#time_endAt').val() !== "") {
    hh = $('#time_endAt').val()[0] + $('#time_endAt').val()[1];
    mm = $('#time_endAt').val()[3] + $('#time_endAt').val()[4];
    scenarioOBJTemp.enAt = [parseInt(hh), parseInt(mm)]
  }
  else {
    hh = -1;
    mm = -1;
    scenarioOBJTemp.enAt = [parseInt(hh), parseInt(mm)]
  }

  //On formate la chaîne de caractères contenant les jours d'application du scénario
  var day = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
  var scenario_applying_days_strHtml = 'Jours : ';
  var apply_day_str = "apply_day_check";
  for (let index = 1; index < 7; index++) {
    apply_day_tmp = "#" + apply_day_str + index

    if ($(apply_day_tmp).is(':checked')) {
      scenario_applying_days_strHtml += day[index];
      scenario_applying_days_strHtml += ', ';
    }

  }

  apply_day_tmp = "#" + apply_day_str + '0'
  if ($(apply_day_tmp).is(':checked')) scenario_applying_days_strHtml += day[0];

  //var $_innerThis = $(this);
  //var $_device_name = $('#device-modal').attr('data-name');
  var $_scenario_name = $('#scenario-name').val();//On récupère le nom du scénario
  var $_time_startAt = $('#time_startAt').val();//On récupère l'heure de démarrage du scénario
  var $_time_endAt = $('#time_endAt').val();//On récupère l'heure de fin du scénario
  var $_demarrage_auto = $('#auto_on_check').is(':checked') ? 1 : 0;//On récupère l'état du checkbox du démarrage automatique
  var $_reallumage_auto = $('#cycle-auto_on_check').is(':checked') ? 1 : 0;//On récupère l'état du checkbox du réallumage automatique
  var $_time_on = $('#timeON').val();//On récupère la durée ON
  var $_time_off = $('#timeOFF').val();//On récupère la durée OFF

  var selected_radio = $('input[name="apply-for"]:checked').val();
  var tabDevices = new Array();

  switch (selected_radio) {
    case 'apply-only-on-device':
      tabDevices.push({
        'name': select_device_name,
        'slug': select_device_slug,
        'address': select_address,
        'type': select_device_type,
      });
      break;

    case 'apply-for-all-same-type':
      var id = '#' + select_device_type + '-id';
      $(id).children('option').each(function () {
        tabDevices.push({
          'name': $(this).attr('data-name'),
          'slug': $(this).val(),
          'address': $(this).attr('data-address'),
          'type': select_device_type,
        });
      });
      //console.log(tabDevices);
      break;

    case 'apply-for-all':
      //console.log(tab);
      $.each(tab, function (key, value) {
        //console.log('============= key = ' + key + ' =============')

        //console.log($('.' + key).attr('data-name'));
        var getTodoClass = $('.' + key).attr('class');
        // console.log(getTodoClass);

        var getDeviceTypeClass = getTodoClass.split(' ')[1];
        // console.log(getDeviceTypeClass);
        tabDevices.push({
          'name': $('.' + key).attr('data-name'),
          'slug': key,
          'address': $('.' + key).attr('data-address'),
          'type': getDeviceTypeClass,
        });
      });
      //console.log(tabDevices);
      // console.log(tab);
      // console.log(tab[tabDevices[0]]);
      break;
  }

  //nb_fail = tabDevices.length;
  swal.queue([{
    title: "Confirmation d'Ajout",
    confirmButtonText: 'Oui',
    cancelButtonText: 'Non',
    text: 'Etes-vous sûr de vouloir ajouter ce scénario ?',
    showCancelButton: true,
    showLoaderOnConfirm: true,
    //confirmButtonClass: 'btn btn-primary',
    //cancelButtonClass: 'btn btn-danger ml-2',
    preConfirm: function () {
      return new Promise(function (resolve) {
        /*$.each(tabDevices, function (index, value) {

          jsonProg = JSON.stringify(tab[value.slug]);
          var $url = device_prog_url_prefix + '/' + value.slug;
          var $data = JSON.stringify({
            "prog": jsonProg,
          });

          $.ajax({
            type: "POST", // method type
            contentType: "application/json; charset=utf-8",
            url: $url, // /Target function that will be return result
            data: $data, // parameter pass data is parameter name param is value
            dataType: "json",
            timeout: 120000, // 64241
            success: function (result) {
              // $('.fa-sync').removeClass('fa-spin');
              console.log(result);
              nb_success--;

              console.log("Add Scenario to device : " + value.name + " done");
              console.log("Add N°" + index + " done");
              //console.log("Retour prg " + JSON.stringify(data));

              //mess.From = "user";
              mess.To = value.address;
              mess.Object = "Programming";
              mess.message = jsonProg;
              doSend(JSON.stringify(mess));

              $html = '<div class="todo-item ' + value.type + ' ' + value.slug + '">' +
                '<div class="todo-item-inner">' +

                '<div class="todo-content">' +
                '<h5 class="todo-heading" data-todoHeading="' + $_scenario_name + '"> ' + $_scenario_name + '<small><span class="badge badge-dark">#' + value.name + '</span></small></h5>' +
                '<p class="meta-date startAt-endAt">Début : ' + $_time_startAt + ', Fin : ' + $_time_endAt + '</p>' +
                '<p class="meta-date demarrage-auto">Démarrage Auto : <span class="badge badge-' + ($_demarrage_auto ? 'success">Oui' : 'danger">Non') + '</span></p>' +
                '<p class="meta-date reallumage-auto">Réallumage Auto : <span class="mt-1 badge badge-' + ($_reallumage_auto ? 'success">Oui' : 'danger">Non') + '</span></p>' +
                '<p class="meta-date startAt-endAt">Durée ON : ' + $_time_on + ', Durée OFF : ' + $_time_off + '</p>' +
                "<p class='todo-text' data-todoHtml='<p>" + scenario_applying_days_strHtml + "</p>' data-todoText='" + '' + "'> " + scenario_applying_days_strHtml + "</p>" +
                '</div>' +

                '<div class="action-dropdown custom-dropdown-icon">' +
                '<div class="dropdown">' +
                '<a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink-4" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>' +
                '</a>' +

                '<div class="dropdown-menu" aria-labelledby="dropdownMenuLink-4">' +
                '<a class="dropdown-item edit" href="javascript:void(0);" data-name="' + value.name + '" data-device="' + value.slug + '" data-address="' + value.address + '" data-sc=' + tab[value.slug]['sc'].length + '><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-3 flaticon-notes"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg> Modifier</a>' +
                '<a class="dropdown-item delete" href="javascript:void(0);" data-name="' + value.name + '" data-device="' + value.slug + '" data-address="' + value.address + '" data-sc=' + tab[value.slug]['sc'].length + '><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Supprimerer</a>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

              //console.log(tab[select_device_slug]);
              tab[select_device_slug]['sc'].push(scenarioOBJTemp);
              tab[select_device_slug]['nb']++;
              //console.log(tab[select_device_slug]);
              $("#ct").prepend($html);
              //
            },
            error: function (result) {
              // console.log("Error");
              // console.log(result);

              swal('Oups...', "Une erreur s'est produite !", 'error'
                // footer: '<a href>Why do I have this issue?</a>'
              );

              setTimeout(function () {
                resolve()
              }, 5000);
            }
          });
        });*/

        var promises = [];
        console.log(tabDevices);
        $.each(tabDevices, function (index, value) {
          /* $.ajax returns a promise*/
          //console.log(value)
          tab[value.slug]['sc'].push(scenarioOBJTemp);
          tab[value.slug]['nb']++;
          var jsonProg_temp = tab[value.slug]
          //console.log(jsonProg_temp)
          var jsonProg = JSON.stringify(jsonProg_temp);
          var $url = device_prog_url_prefix + '/' + value.slug;
          var $data = JSON.stringify({
            "prog": jsonProg,
          });
          var request = $.ajax({
            type: "POST", // method type
            contentType: "application/json; charset=utf-8",
            url: $url, // /Target function that will be return result
            data: $data, // parameter pass data is parameter name param is value
            dataType: "json",
            timeout: 120000, // 64241
            success: function (result) {
              // $('.fa-sync').removeClass('fa-spin');
              //console.log(result);
              console.log("Add Scenario to device : " + value.name + " done");
              console.log("Add N°" + index + " done");
              //console.log("Retour prg " + JSON.stringify(data));

              //mess.From = "user";
              mess.To = value.address;
              mess.Object = "Programming";
              mess.message = jsonProg;
              doSend(JSON.stringify(mess));

              $html = '<div class="todo-item ' + value.type + ' ' + value.slug + '">' +
                '<div class="todo-item-inner">' +

                '<div class="todo-content">' +
                '<h5 class="todo-heading" data-todoHeading="' + $_scenario_name + '"> ' + $_scenario_name + '<small><span class="badge badge-dark">#' + value.name + '</span></small></h5>' +
                '<p class="meta-date startAt-endAt">Début : ' + $_time_startAt + ', Fin : ' + $_time_endAt + '</p>' +
                '<p class="meta-date demarrage-auto">Démarrage Auto : <span class="badge badge-' + ($_demarrage_auto ? 'success">Oui' : 'danger">Non') + '</span></p>' +
                '<p class="meta-date reallumage-auto">Réallumage Auto : <span class="mt-1 badge badge-' + ($_reallumage_auto ? 'success">Oui' : 'danger">Non') + '</span></p>' +
                '<p class="meta-date time-on-off">Durée ON : ' + $_time_on + ' mins, Durée OFF : ' + $_time_off + ' mins</p>' +
                "<p class='todo-text' data-todoHtml='<p>" + scenario_applying_days_strHtml + "</p>' data-todoText='" + '' + "'> " + scenario_applying_days_strHtml + "</p>" +
                '</div>' +

                '<div class="action-dropdown custom-dropdown-icon">' +
                '<div class="dropdown">' +
                '<a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink-' + value.slug + '-' + (jsonProg_temp['sc'].length - 1) + '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>' +
                '</a>' +

                '<div class="dropdown-menu" aria-labelledby="dropdownMenuLink-' + value.slug + '-' + (jsonProg_temp['sc'].length - 1) + '">' +
                '<a class="dropdown-item edit" href="javascript:void(0);" data-name="' + value.name + '" data-device="' + value.slug + '" data-address="' + value.address + '" data-sc=' + (jsonProg_temp['sc'].length - 1) + '><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-3 flaticon-notes"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg> Modifier</a>' +
                '<a class="dropdown-item delete" href="javascript:void(0);" data-name="' + value.name + '" data-device="' + value.slug + '" data-address="' + value.address + '" data-sc=' + (jsonProg_temp['sc'].length - 1) + '><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Supprimer</a>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

              //console.log(tab[select_device_slug]);
              //tab[select_device_slug]['sc'].push(scenarioOBJTemp);
              //tab[select_device_slug]['nb']++;
              //console.log(tab[select_device_slug]);
              $("#ct").prepend($html);
              deleteDropdown();
              editDropdown();
              todoItem();
              //
            },
            error: function (result) {
              // console.log("Error");
              // console.log(result);
              tab[value.slug]['nb']--;
              tab[value.slug]['sc'].splice($.inArray(scenarioOBJTemp, tab[value.slug]['sc']), 1);

            }
          });

          promises.push(request);
        });


        $.when.apply(null, promises).done(function () {
          deleteDropdown();
          editDropdown();
          todoItem();
          $(".list-actions#" + select_device_type).trigger('click');
          swal({
            type: 'success',
            title: 'L\'ajout du scénario a été effectué avec succès !',
            // html: 'Submitted email: ' + email
          });

          setTimeout(function () {
            resolve()
            $('#addScenarioModal').modal('hide');
          }, 1000);

          //alert('All done')
        }).fail(function () {

          swal('Oups...', "Une erreur s'est produite !", 'error'
            // footer: '<a href>Why do I have this issue?</a>'
          );

          setTimeout(function () {
            resolve()
          }, 5000);
        })

      })
    },
    allowOutsideClick: false
  }]);

  deleteDropdown();
  // deletePermanentlyDropdown();
  // reviveMailDropdown();
  editDropdown();
  //priorityDropdown();
  todoItem();
  //importantDropdown();
  //new dynamicBadgeNotification('allList');
  $(".list-actions#" + select_device_type).trigger('click');

});

$('.tab-title .nav-pills a.nav-link').on('click', function (event) {
  $(this).parents('.mail-box-container').find('.tab-title').removeClass('mail-menu-show')
  $(this).parents('.mail-box-container').find('.mail-overlay').removeClass('mail-overlay-show')
})

// Validation Process

var $_getValidationField = document.getElementsByClassName('validation-text');

getTaskTitleInput = document.getElementById('task');

getTaskTitleInput.addEventListener('input', function () {

  getTaskTitleInputValue = this.value;

  if (getTaskTitleInputValue == "") {
    $_getValidationField[0].innerHTML = 'Title Required';
    $_getValidationField[0].style.display = 'block';
  } else {
    $_getValidationField[0].style.display = 'none';
  }
})

getTaskDescriptionInput = document.getElementById('taskdescription');

getTaskDescriptionInput.addEventListener('input', function () {

  getTaskDescriptionInputValue = this.value;

  if (getTaskDescriptionInputValue == "") {
    $_getValidationField[1].innerHTML = 'Description Required';
    $_getValidationField[1].style.display = 'block';
  } else {
    $_getValidationField[1].style.display = 'none';
  }

})