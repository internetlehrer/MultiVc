function constructModal(opts = {}) {

    const args = Object.assign({
        title: '',
        body: '',
        id: 'default',
        animation: 'fade',
        btnAccept: true,
        txtAccept: 'OK',
        fnAccept: function () {},
        btnAbort: true,
        txtAbort: 'Abort',
        fnAbort: function () {}
    }, opts);


    /*
     * Modal
     */
    const modal = document.createElement('div');

    Object.assign(modal.style, {
        position: 'fixed',
        inset: '0',
        zIndex: '1050',
        display: 'flex',
        alignItems: 'flex-start',
        justifyContent: 'center',
        overflowX: 'hidden',
        overflowY: 'auto',
        padding: '1.75rem 1rem',
        boxSizing: 'border-box',
        backgroundColor: 'transparent',
        opacity: '0',
        transition: 'opacity 150ms ease'
    });

    modal.id = `${args.id}Modal`;
    modal.tabIndex = -1;

    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute(
        'aria-labelledby',
        `${args.id}ModalLabel`
    );


    /*
     * Modal Dialog
     */
    const modalDialog = document.createElement('div');

    Object.assign(modalDialog.style, {
        position: 'relative',
        width: '100%',
        maxWidth: '500px',
        margin: 'auto',
        boxSizing: 'border-box'
    });


    /*
     * Modal Content
     */
    const modalContent = document.createElement('div');

    Object.assign(modalContent.style, {
        position: 'relative',
        display: 'flex',
        flexDirection: 'column',
        width: '100%',
        backgroundColor: '#fff',
        backgroundClip: 'padding-box',
        border: '1px solid rgba(0, 0, 0, .2)',
        borderRadius: '.3rem',
        outline: '0',
        boxShadow: '0 .5rem 1rem rgba(0, 0, 0, .15)',
        boxSizing: 'border-box'
    });


    /*
     * Header
     */
    const modalHeader = document.createElement('div');

    Object.assign(modalHeader.style, {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '1rem',
        borderBottom: '1px solid #dee2e6',
        boxSizing: 'border-box'
    });


    /*
     * Title
     */
    const modalTitle = document.createElement('h5');

    Object.assign(modalTitle.style, {
        margin: '0',
        fontSize: '1.25rem',
        fontWeight: '500',
        lineHeight: '1.5'
    });

    modalTitle.id = `${args.id}ModalLabel`;
    modalTitle.textContent = args.title;

    modalHeader.appendChild(modalTitle);


    /*
     * Body
     */
    const modalBody = document.createElement('div');

    Object.assign(modalBody.style, {
        position: 'relative',
        flex: '1 1 auto',
        padding: '1rem',
        boxSizing: 'border-box'
    });

    /*
     * body kann HTML, Text oder ein DOM-Element sein
     */
    if (args.body instanceof Node) {
        modalBody.appendChild(args.body);
    } else {
        modalBody.innerHTML = args.body;
    }


    /*
     * Footer
     */
    const modalFooter = document.createElement('div');

    Object.assign(modalFooter.style, {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'flex-end',
        padding: '1rem',
        borderTop: '1px solid #dee2e6',
        boxSizing: 'border-box',
        gap: '.5rem'
    });


    /*
     * Button erstellen
     */
    function createButton(text, type, callback) {

        const button =
            document.createElement('button');

        button.type = 'button';
        button.textContent = text;

        Object.assign(button.style, {
            display: 'inline-block',
            fontWeight: '400',
            textAlign: 'center',
            verticalAlign: 'middle',
            userSelect: 'none',
            border: '1px solid transparent',
            padding: '.25rem .5rem',
            fontSize: '.875rem',
            lineHeight: '1.5',
            borderRadius: '.25rem',
            cursor: 'pointer',
            transition:
                'color .15s ease-in-out, background-color .15s ease-in-out',
            boxSizing: 'border-box'
        });


        if (type === 'accept') {

            Object.assign(button.style, {
                color: '#212529',
                backgroundColor: '#f8f9fa',
                borderColor: '#ccc'
            });

        } else {

            Object.assign(button.style, {
                color: '#fff',
                backgroundColor: '#dc3545',
                borderColor: '#dc3545'
            });
        }


        /*
         * Entspricht jQuery .one('click', ...)
         */
        button.addEventListener(
            'click',
            function handler(event) {

                button.removeEventListener(
                    'click',
                    handler
                );

                callback.call(button, event);
            }
        );

        return button;
    }


    /*
     * Accept Button
     */
    if (args.btnAccept) {

        const modalBtnAccept = createButton(
            args.txtAccept,
            'accept',
            args.fnAccept
        );

        modalBtnAccept.id = 'btnAccept';

        modalFooter.appendChild(
            modalBtnAccept
        );
    }


    /*
     * Abort Button
     */
    if (args.btnAbort) {

        const modalBtnAbort = createButton(
            args.txtAbort,
            'abort',
            args.fnAbort
        );

        modalBtnAbort.id = 'btnAbort';

        modalFooter.appendChild(
            modalBtnAbort
        );
    }


    /*
     * Modal zusammenbauen
     */
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modalContent.appendChild(modalFooter);

    modalDialog.appendChild(modalContent);
    modal.appendChild(modalDialog);


    /*
     * Referenzen für removeModal()
     */
    modal._backdrop = null;
    modal._keyHandler = null;


    return modal;
}


/*
 * Modal anzeigen
 */
function showModal(opts = {}) {

    const args = Object.assign({
        keyboard: false,
        backdrop: 'static'
    }, opts);


    const modal = constructModal(args);

    const container =
        document.getElementById(
            'form_plugin_configuration'
        );


    if (!container) {
        console.error(
            '#form_plugin_configuration not found'
        );

        return null;
    }


    /*
     * Backdrop
     */
    const backdrop =
        document.createElement('div');

    Object.assign(backdrop.style, {
        position: 'fixed',
        inset: '0',
        zIndex: '1040',
        backgroundColor: '#000',
        opacity: '0',
        transition: 'opacity 150ms ease'
    });


    /*
     * DOM
     */
    container.appendChild(backdrop);
    container.appendChild(modal);


    /*
     * Body Scroll sperren
     */
    document.body.style.overflow = 'hidden';


    /*
     * Anzeigen
     */
    requestAnimationFrame(() => {

        backdrop.style.opacity = '.5';
        modal.style.opacity = '1';

    });


    /*
     * ESC-Taste
     */
    const keyHandler = function (event) {

        if (
            event.key === 'Escape' &&
            args.keyboard
        ) {
            removeModal(args.id);
        }
    };

    document.addEventListener(
        'keydown',
        keyHandler
    );


    /*
     * Backdrop-Klick
     */
    if (args.backdrop !== 'static') {

        backdrop.addEventListener(
            'click',
            function () {
                removeModal(args.id);
            }
        );
    }


    /*
     * Referenzen speichern
     */
    modal._backdrop = backdrop;
    modal._keyHandler = keyHandler;


    /*
     * Fokus
     */
    modal.focus();


    return modal;
}


/*
 * Modal entfernen
 */
function removeModal(id) {

    const modal =
        document.getElementById(
            `${id}Modal`
        );

    if (!modal) {
        return;
    }


    const backdrop =
        modal._backdrop;


    /*
     * Keyboard Handler entfernen
     */
    if (modal._keyHandler) {

        document.removeEventListener(
            'keydown',
            modal._keyHandler
        );
    }


    /*
     * Fade-Out
     */
    modal.style.opacity = '0';

    if (backdrop) {
        backdrop.style.opacity = '0';
    }


    /*
     * Nach Animation entfernen
     */
    setTimeout(() => {

        modal.remove();

        if (backdrop) {
            backdrop.remove();
        }

        /*
         * Scroll wieder freigeben
         */
        document.body.style.overflow = '';

    }, 150);
}


/*
 * Meeting löschen
 */
function deleteMeeting(rawData) {

    const json = unescape(rawData);
    const data = JSON.parse(json);

    console.log('data');
    console.dir(data);


    const opts = {

        id: data.modal.id,

        title: data.modal.title,

        body: data.modal.body,

        txtAccept: data.modal.txtAccept,

        txtAbort: data.modal.txtAbort,


        /*
         * DELETE
         */
        fnAccept: function () {

            const form =
                document.getElementById(
                    'form_meeting_create'
                );

            if (!form) {
                return;
            }


            const btnSubmit =
                form.querySelector(
                    'input[type="submit"]'
                );


            const fields = {
                meetingTitle:
                    document.getElementById(
                        'meeting_title'
                    ),

                refId:
                    document.getElementById(
                        'delete_scheduled_meeting__ref_id__'
                    ),

                start:
                    document.getElementById(
                        'delete_scheduled_meeting__start__'
                    ),

                end:
                    document.getElementById(
                        'delete_scheduled_meeting__end__'
                    ),

                timezone:
                    document.getElementById(
                        'delete_scheduled_meeting__timezone__'
                    )
            };


            if (fields.meetingTitle) {
                fields.meetingTitle.value =
                    data.ref_id;
            }

            if (fields.refId) {
                fields.refId.value =
                    data.ref_id;
            }

            if (fields.start) {
                fields.start.value =
                    data.start;
            }

            if (fields.end) {
                fields.end.value =
                    data.end;
            }

            if (fields.timezone) {
                fields.timezone.value =
                    data.timezone;
            }


            if (btnSubmit) {

                btnSubmit.name =
                    'cmd[delete_scheduled_meeting]';

                btnSubmit.click();
            }
        },


        /*
         * ABORT
         */
        fnAbort: function () {

            removeModal(
                data.modal.id
            );
        }
    };


    const args = Object.assign({

        id: 'default',

        title: 'Delete',

        body: 'Are you sure?',

        animation: 'fade',

        btnAccept: true,

        txtAccept: 'OK',

        fnAccept: function () {},

        btnAbort: true,

        txtAbort: 'Abort'

    }, opts);


    return showModal(args);
}


/*
 * Token User Modal
 */
function editTokenUser(modalData) {

    const opts = {

        body: modalData.body,

        fnAccept: function () {
            // removeModal(modalData.id);
        },

        btnAbort: false
    };


    const args = Object.assign({

        id: 'default',

        title: 'Delete',

        body: 'Are you sure?',

        animation: 'fade',

        btnAccept: true,

        txtAccept: 'OK',

        fnAccept: function () {},

        btnAbort: true,

        txtAbort: 'Abort'

    }, modalData);


    return showModal(args);
}


/*
 * DOM Ready
 */
document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
         * Token User
         */
        const tokenUser =
            document.getElementById(
                'token_user'
            );

        const jsonTokenUser =
            JSON.parse(tokenUser.value);


        /*
         * Elemente
         */
        const listTokenUser =
            document.querySelector(
                '#il_prop_cont_list_token_user > div'
            );

        const newTokenUserEmail =
            document.getElementById(
                'new_token_user_email'
            );

        const newTokenUserAccessToken =
            document.getElementById(
                'new_token_user_access_token'
            );

        const formPluginConfiguration =
            document.getElementById(
                'form_plugin_configuration'
            );

        const btnSubmit =
            formPluginConfiguration.querySelector(
                'input[type="submit"]'
            );


        /*
         * Button-Daten
         */
        const btnEditTokenUser =
            document.getElementById(
                'btn_edit_token_user'
            );

        const btnEditTokenJson =
            unescape(
                btnEditTokenUser.value
            );

        const btnEditTokenData =
            JSON.parse(btnEditTokenJson);


        /*
         * Add Token User Button
         */
        btnEditTokenUser.type =
            'button';

        btnEditTokenUser.className =
            'btn btn-default';

        Object.assign(
            btnEditTokenUser.style,
            {
                marginBottom: '8px'
            }
        );

        btnEditTokenUser.value =
            btnEditTokenData.formBtnAddToken;


        btnEditTokenUser.addEventListener(
            'click',
            function (e) {

                if (
                    !newTokenUserEmail.value.length
                ) {

                    e.preventDefault();
                    e.stopPropagation();

                    return false;
                }


                const args = {};

                args.id =
                    'modalTokenUser';

                args.title =
                    btnEditTokenData
                        .modalAddTokenTitle;


                /*
                 * Access Token Input
                 */
                Object.assign(
                    newTokenUserAccessToken.style,
                    {
                        boxSizing: 'border-box',
                        width: '100%'
                    }
                );

                newTokenUserAccessToken.className =
                    '';

                newTokenUserAccessToken.type =
                    'text';

                newTokenUserAccessToken.value =
                    '';


                args.body =
                    newTokenUserAccessToken;


                /*
                 * Accept
                 */
                args.txtAccept =
                    btnEditTokenData
                        .modalBtnStore;


                args.fnAccept =
                    function () {

                        btnSubmit.name =
                            'cmd[update_token_user]';

                        btnSubmit.click();
                    };


                /*
                 * Abort
                 */
                args.txtAbort =
                    btnEditTokenData
                        .modalBtnAbort;


                args.fnAbort =
                    function () {

                        newTokenUserEmail.value =
                            '';

                        newTokenUserAccessToken.value =
                            '';

                        removeModal(
                            args.id
                        );
                    };


                return editTokenUser(args);
            }
        );


        /*
         * Button einfügen
         */
        const newTokenUserContainer =
            document.querySelector(
                '#il_prop_cont_new_token_user_email > div'
            );

        if (newTokenUserContainer) {

            newTokenUserContainer.appendChild(
                btnEditTokenUser
            );
        }


        /*
         * Liste
         */
        if (listTokenUser) {

            listTokenUser.style.marginBottom =
                '8px';


            for (const i in jsonTokenUser) {

                if (
                    !Object.prototype.hasOwnProperty
                        .call(jsonTokenUser, i)
                ) {
                    continue;
                }


                /*
                 * Alte Liste entfernen
                 */
                const existingList =
                    document.getElementById(
                        'list_token_user'
                    );

                if (existingList) {
                    existingList.remove();
                }


                /*
                 * User
                 */
                const user =
                    document.createElement(
                        'div'
                    );

                user.className =
                    'form-control';

                user.textContent =
                    jsonTokenUser[i];


                Object.assign(
                    user.style,
                    {
                        display: 'inline-block',
                        width: 'auto',
                        padding: '6px 0 6px 12px',
                        marginRight: '8px',
                        marginBottom: '8px',
                        boxSizing: 'border-box',
                        verticalAlign: 'middle',
                        border: '1px solid #ced4da',
                        borderRadius: '.25rem',
                        backgroundColor: '#fff',
                        lineHeight: '1.5'
                    }
                );


                /*
                 * Delete Button
                 */
                const delBtn =
                    document.createElement(
                        'div'
                    );

                delBtn.id =
                    jsonTokenUser[i];

                delBtn.className =
                    'btn btn-danger';

                delBtn.textContent =
                    'X';


                Object.assign(
                    delBtn.style,
                    {
                        display: 'inline-block',
                        padding: '.25rem .5rem',
                        marginTop: '-3px',
                        marginLeft: '8px',
                        float: 'right',
                        color: '#fff',
                        backgroundColor: '#dc3545',
                        border: '1px solid #dc3545',
                        borderRadius: '.25rem',
                        cursor: 'pointer',
                        fontSize: '.875rem',
                        lineHeight: '1.5',
                        userSelect: 'none'
                    }
                );


                delBtn.addEventListener(
                    'click',
                    function (e) {

                        console.dir(e.target);
                        console.log(
                            'delBtn clicked'
                        );


                        const args = {};

                        args.id =
                            'modalTokenUser';

                        args.title =
                            btnEditTokenData
                                .modalDeleteTokenTitle;

                        args.body =
                            jsonTokenUser[i];

                        args.txtAccept =
                            btnEditTokenData
                                .modalBtnDelete;


                        /*
                         * Delete
                         */
                        args.fnAccept =
                            function () {

                                newTokenUserEmail.value =
                                    jsonTokenUser[i];

                                btnSubmit.name =
                                    'cmd[delete_token_user]';

                                btnSubmit.click();
                            };


                        /*
                         * Abort
                         */
                        args.txtAbort =
                            btnEditTokenData
                                .modalBtnAbort;


                        args.fnAbort =
                            function () {

                                newTokenUserEmail.value =
                                    '';

                                removeModal(
                                    args.id
                                );
                            };


                        return editTokenUser(
                            args
                        );
                    }
                );


                user.appendChild(delBtn);

                listTokenUser.prepend(user);
            }
        }


        /*
         * Autocomplete
         */
        setTimeout(
            function () {

                newTokenUserEmail.autocomplete =
                    'new-password';

            },
            1000
        );
    }
);
