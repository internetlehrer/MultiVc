function showModalIntegration(clientId)
{
    console.log(clientId);
    if( !clientId ) {
        console.log('no client id');
        return false;
    }

    // Start loading iframe source
    //$('#oauthFrame').prop('src', 'https://webexapis.com/v1/authorize?client_id=C1cc0ecc0b6c4cf4adb19a0754a611456a4d8b38fced968c8631dba27c300fdc0&response_type=code&redirect_uri=https%3A%2F%2Fcass.aptum.net%2Frelease_6&scope=meeting%3Arecordings_read%20meeting%3Aadmin_schedule_write%20meeting%3Aschedules_read%20meeting%3Aparticipants_read%20meeting%3Aadmin_participants_read%20meeting%3Apreferences_write%20meeting%3Arecordings_write%20meeting%3Apreferences_read%20meeting%3Aadmin_recordings_read%20meeting%3Aschedules_write%20spark%3Akms%20meeting%3Acontrols_write%20meeting%3Aadmin_recordings_write%20meeting%3Acontrols_read%20meeting%3Aparticipants_write%20meeting%3Aadmin_schedule_read&state=wbxmvc_9876543210654321');

    #loadModal();
    return false;

}

function loadModal()
{
    $('#modalIntegration').modal({
        keyboard: false,
        backdrop: 'static'
    });
    $('#btn_accept_termsoffuse', document).on('click', function() {
        $('#terms_of_use', document).prop({value: 1});
    });
}


/*
let valTermsOfUse = {VAL_TERMSOFUSE};
if( valTermsOfUse === 1 ) {
    $('#terms_of_use', document).prop({value: valTermsOfUse});
    console.log('valTermsOfUse: ' + valTermsOfUse);
} else {
    $('#modalIntegration').modal({
        keyboard: false,
        backdrop: 'static'
    });
    $('#btn_accept_termsoffuse', document).on('click', function() {
        $('#terms_of_use', document).prop({value: 1});
    });
}
*/
