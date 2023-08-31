$('#join_btn .btn.btn-default', document).on('click', function (e) {
  $('#join_btn', document).addClass('hidden');
  $('#msg_running', document).removeClass('hidden');
});

$('#copyUserInviteUrl', document).on('click', function (e) {
  let copyText = $('#userInviteUrl', document).get(0);
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/
  document.execCommand("copy");
});

$('#copyGuestLinkPw', document).on('click', function (e) {
  let copyText = $('#guestLinkPw', document).get(0);
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/
  document.execCommand("copy");
});
