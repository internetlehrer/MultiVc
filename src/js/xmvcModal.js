function constructModal(opts) {
    let args = $.extend({
        'title': '',
        'body': '',
        'id': 'default',
        'animation': 'fade',
        'btnAccept': true,
        'txtAccept': 'OK',
        'fnAccept': function() {},
        'btnAbort': true,
        'txtAbort': 'Abort',
        'fnAbort': function() {},
    }, opts);
    let modal = $('<div class="modal ' + args.animation + '" id="' + args.id + 'Modal" tabindex="-1" role="dialog" aria-labelledby="' + args.id + 'ModalLabel" aria-hidden="true">');
    let modalDialog = $('<div class="modal-dialog" role="document">');
    let modalContent = $('<div class="modal-content">');
    let modalHeader = $('<div class="modal-header">');
    let modalTitle = $('<h5 class="modal-title">');
    let modalBody = $('<div class="modal-body">');
    let modalFooter = $('<div class="modal-footer">');
    let modalBtnAccept = $('<button type="button" class="btn btn-default btn-sm" data-dismiss="modal" id="btnAccept">');
    let modalBtnAbort = $('<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal" id="btnAbort">');

    // ModalFooter - Buttons triggers functions
    if( args.btnAccept ) {
        $(modalBtnAccept).text(args.txtAccept).one('click', args.fnAccept);
        $(modalFooter).append( $(modalBtnAccept) );
    }
    if( args.btnAbort ) {
        $(modalBtnAbort).text(args.txtAbort).one('click', args.fnAbort);
        $(modalFooter).append( $(modalBtnAbort) );
    }
    $(modalContent).append(modalFooter);

    // ModalBody
    $(modalBody).html(args.body);
    $(modalContent).prepend(modalBody);

    // ModalTitle
    $(modalTitle).text(args.title);
    $(modalHeader).append(modalTitle);
    $(modalContent).prepend(modalHeader);

    // ModalDocument
    $(modalDialog).append(modalContent);
    $(modal).append(modalDialog);

    return $(modal);
}

function showModal(opts) {
    let modal = constructModal(opts);
    let args = $.extend({
        'keyboard': false,
        'backdrop': 'static'
    }, opts);
    $(modal).modal({
        keyboard: args.keyboard,
        backdrop: args.backdrop
    });
    $(document.body).append(modal);
}

function removeModal(id) {
    $(document).remove('#' + id + 'Modal');
}


function relateMeeting(rawData) {
    let json = unescape(rawData);
    let data = $.parseJSON(json);
    console.log('data');
    console.dir(data);
    let btnSubmit = $('#form_meeting_create input[type=submit]', document).eq(0);

    $('#meeting_title', document).prop('value', data.ref_id);
    $('#relate_meeting__ref_id__', document).prop('value', data.ref_id);
    $('#relate_meeting__start__', document).prop('value', data.start);
    $('#relate_meeting__end__', document).prop('value', data.end);
    $('#relate_meeting__timezone__', document).prop('value', data.timezone);
    $('#relate_meeting__rel_id__', document).prop('value', data.rel_id);
    $('#relate_meeting__rel_data__', document).prop('value', JSON.stringify(data.rel_data));

    $(btnSubmit).prop('name', 'cmd[meeting_relate]');
    $(btnSubmit).trigger('click');
}




function deleteMeeting(rawData) {
    let json = unescape(rawData);
    let data = $.parseJSON(json);
    console.log('data');
    console.dir(data);
    let opts = {
        'id': data.modal.id,
        'title': data.modal.title,
        'body': data.modal.body,
        'txtAccept': data.modal.txtAccept,
        'txtAbort': data.modal.txtAbort,
        'fnAccept': function() {
            let btnSubmit = $('#form_meeting_create input[type=submit]', document).eq(0);
            $('#meeting_title', document).prop('value', data.ref_id);
            $('#delete_scheduled_meeting__ref_id__', document).prop('value', data.ref_id);
            $('#delete_scheduled_meeting__delete_local_only__', document).prop('value', data.delete_local_only);
            $('#delete_scheduled_meeting__start__', document).prop('value', data.start);
            $('#delete_scheduled_meeting__end__', document).prop('value', data.end);
            $('#delete_scheduled_meeting__timezone__', document).prop('value', data.timezone);
            $(btnSubmit).prop('name', 'cmd[delete_scheduled_meeting]');
            $(btnSubmit).trigger('click');
        },
        'fnAbort': function() {
            return removeModal(data.modal.id);
        }
    };
    let args = $.extend({
        'id': 'default',
        'title': 'Delete',
        'body': 'Are you sure?',
        'animation': 'fade',
        'btnAccept': true,
        'txtAccept': 'OK',
        'fnAccept': function() {},
        'btnAbort': true,
        'txtAbort': 'Abort',
    }, opts);
    return showModal(args);
}

function showDetails(rawData) {
    let json = unescape(rawData);
    console.log('json');
    console.dir(json);
    let data = $.parseJSON(json);
    console.log('data');
    console.dir(data);
    let opts = {
        'id': data.modal.id,
        'title': data.modal.title,
        'body': $('<pre>').text(data.modal.body),
        'txtAccept': data.modal.txtAccept,
        'fnAccept': function() {
            return removeModal(data.modal.id);
        },
    };
    let args = $.extend({
        'id': 'default',
        'title': 'Delete',
        'body': 'Are you sure?',
        'animation': 'fade',
        'btnAccept': true,
        'txtAccept': 'OK',
        'fnAccept': function() {},
        'btnAbort': false,
    }, opts);
    return showModal(args);
}

$(document).ready(function() {
    /*
    let fmlNode = $('#tfil_scheduled_meetings', document).get(0);
    let iddParent = $('.input-group.date.noop', fmlNode).get();
    let iddStart =  $('input.form-control', iddParent[0]).get(0);
    let iddEnd =  $('input.form-control', iddParent[1]).get(0);

    $(iddStart).on('change input blur', function(e) {
        let elem = e.target;
        $('#fml_date_duration__start__', document).val($(elem).val());
    });

    $(iddEnd).on('change input blur', function(e) {
        let elem = e.target;
        $('#fml_date_duration__end__', document).val($(elem).val());
    });

    $('#data_source', fmlNode).on('change input blur', function(e) {
        let elem = e.target;
        $('#fml_data_source', document).val($(elem).val());
    });
    */
});