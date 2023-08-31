{VAL_1}
let xmvcUrl = "{LINK_TARGET}";
let xmvcNotification = {};
$.ajax({
    url: xmvcUrl,
    dataType: 'json',
    async: true,
}).done(function(jsonResponse, httpStatus){
    if( httpStatus === 'success' ) {
        xmvcNotification = jsonResponse;
        $($('.alert.alert-info', document).get(0)).prop('class', 'alert alert-success').text("{TXT_SENT}");
    }
});
console.dir(xmvcUrl);


