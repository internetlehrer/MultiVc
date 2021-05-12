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
    $('#form_plugin_configuration',document).append(modal);
}

function removeModal(id) {
    $('#form_plugin_configuration',document).remove('#' + id + 'Modal');
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

function editTokenUser(modalData) {
    let opts = {
        'body': modalData.body,
        'fnAccept': function() {
            //return removeModal(modalData.id);
        },
        'btnAbort': false
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
    }, modalData);
    return showModal(args);
}


$(document).ready(function() {
    let jsonTokenUser = $.parseJSON($('#token_user', document).val());
    let listTokenUser = $('#il_prop_cont_list_token_user > div', document).get(0);

    let newTokenUserEmail = $('#new_token_user_email', document).get(0);
    let newTokenUserAccessToken = $('#new_token_user_access_token', document).get(0);

    let btnSubmit = $('#form_plugin_configuration input[type=submit]', document).eq(0);
    let btnEditTokenJson = unescape($('#btn_edit_token_user', document).val());
    let btnEditTokenData = $.parseJSON(btnEditTokenJson);

    let btnEditTokenUser = $('#btn_edit_token_user', document).prop({
        'type': 'button',
        'class': 'btn btn-default',
        'style': 'margin-bottom: 8px;',
        'value': btnEditTokenData.formBtnAddToken
    }).on('click', function(e) {
        if( !(($(newTokenUserEmail).val()).length) ) {
            e.target.preventDefault();
            e.target.stopPropagation();
            return false;
        }
        let args = {};
        args.id = 'modalTokenUser';
        args.title = btnEditTokenData.modalAddTokenTitle;
        $(newTokenUserAccessToken).prop({
            'class': 'form-control',
            'type': 'text',
        }).val('');

        args.body = $(newTokenUserAccessToken);
        args.txtAccept = btnEditTokenData.modalBtnStore;;
        args.fnAccept = function() {
            let btnSubmit = $('#form_plugin_configuration input[type=submit]', document).eq(0);
            $(btnSubmit).prop('name', 'cmd[update_token_user]');
            $(btnSubmit).trigger('click');
        };
        args.txtAbort = btnEditTokenData.modalBtnAbort;
        args.fnAbort = function() {
            $(newTokenUserEmail).prop('value', '').val('');
            $(newTokenUserAccessToken).prop('value', '').val('');
            return removeModal(args.id);
        }


        //args.body = elemAutoComplete;
        return editTokenUser(args);
    });

    $('#il_prop_cont_new_token_user_email > div', document).append(btnEditTokenUser);

    // ilNonEditableInputGui List Email
    $(listTokenUser).prop({
        'style': 'margin-bottom: 8px;'
    });

    // ilNonEditableInputGui Entries
    for (let i in jsonTokenUser) {
        $('#list_token_user', listTokenUser).remove();
        let user = $('<div class="col-sm-9 form-control">');
        $(user).text(jsonTokenUser[i]).prop({
            'style': 'width: auto; padding-right: 0; margin-right: 8px; margin-bottom: 8px;'
        });

        let delBtn = $('<div>').on('click', function (e) {
            //e.target.stopPropagation();
            console.dir(e.target);
            console.log('delBtn clicked');
            //return false;
            let args = {};
            args.id = 'modalTokenUser';
            args.title = btnEditTokenData.modalDeleteTokenTitle;
            args.body = jsonTokenUser[i];
            args.txtAccept = btnEditTokenData.modalBtnDelete;
            args.fnAccept = function() {
                // fnAccept
                $(newTokenUserEmail).prop('value', jsonTokenUser[i]);
                $(btnSubmit).prop('name', 'cmd[delete_token_user]');
                $(btnSubmit).trigger('click');
            };
            args.txtAbort = btnEditTokenData.modalBtnAbort;
            args.fnAbort = function() {
                $(newTokenUserEmail).prop('value', '');
                return removeModal(args.id);
            }
            return editTokenUser(args);
        }).prop({
            'id': jsonTokenUser[i],
            'class': 'btn btn-danger',
            'style': 'margin-top: -3px; margin-left: 8px; float: right'
        }).text('X');

        $(user).append(delBtn);
        $(listTokenUser).prepend(user);

    }


    setTimeout(function() {
        $(newTokenUserEmail).prop({
            'autocomplete': 'new-password',
        });
    }, 1000)


    //newTokenUserEmail.attributes.autocomplete = 'new-password';
});