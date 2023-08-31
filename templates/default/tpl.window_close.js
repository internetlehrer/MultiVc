if (window.opener && typeof(window.opener) != "undefined") {
  let parent = window.opener.document;

  $(window).on('beforeunload', function() {
  	$('#join_btn', parent).prop('class', '');
  	$('#msg_running', parent).prop('class', 'hidden');
  	$('#userInvitation', parent).prop('class', 'hidden');
  });

  window.setTimeout(function() {
    window.close();
  }, 10);
}
