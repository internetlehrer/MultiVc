if (window.opener && typeof(window.opener) != "undefined") {
  let parent = window.opener.document;
  window.opener.logChildren(window);

  $(window).on('beforeunload', function() {
    $('#msg_running', parent).prop('class', '');
  });

  $(window).on('beforeClose', function() {
    $('#join_btn', parent).prop('class', '');
    $('#msg_running', parent).prop('class', 'hidden');
  });
}
window.setTimeout(function() {
  window.location.href = "{REDIRECTURL}";
}, 2000);
