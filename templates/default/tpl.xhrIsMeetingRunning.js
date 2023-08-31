{VAL_1}
let xmvcUrl = "{URL_TARGET}";
let xhrObs = {};
$(xhrObs).bind('checkIsMeetingRunning', function() {
    $.ajax({
        url: xmvcUrl,
        dataType: 'json',
        async: true,
    }).done(function(jsonResponse, httpStatus){
        if( httpStatus === 'success' && jsonResponse.state === 'running' ) {
            $(xhrObs).unbind('checkIsMeetingRunning');
            $($('#join_brtn', document).get(0)).html(jsonResponse.button);
        }
    });
});

window.setTimeout(function() {
    $(xhrObs).trigger('checkIsMeetingRunning');
}, 10000);

//console.dir(xmvcUrl);


