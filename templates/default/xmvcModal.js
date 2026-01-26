function extend(defaults, opts) {
  return Object.assign({}, defaults, opts);
}

function once(el, event, fn) {
  function handler(e) {
    fn(e);
    el.removeEventListener(event, handler);
  }
  el.addEventListener(event, handler);
}

function constructModal(opts) {
  const args = extend({
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

  const modal = document.createElement('div');
  // modal.className = 'modal ' + args.animation;
  modal.className = 'modal ' + (args.animation === 'fade' ? 'fade in' : args.animation);
  modal.id = args.id + 'Modal';
  modal.tabIndex = -1;
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-hidden', 'true');

  modal.style.display = 'block';

  const modalDialog = document.createElement('div');
  modalDialog.className = 'modal-dialog';
  modalDialog.setAttribute('role', 'document');

  const modalContent = document.createElement('div');
  modalContent.className = 'modal-content';

  const modalHeader = document.createElement('div');
  modalHeader.className = 'modal-header';

  const modalTitle = document.createElement('h5');
  modalTitle.className = 'modal-title';
  modalTitle.textContent = args.title;

  const modalBody = document.createElement('div');
  modalBody.className = 'modal-body';

  if (args.body instanceof HTMLElement) {
    modalBody.appendChild(args.body);
  } else {
    modalBody.innerHTML = args.body;
  }

  const modalFooter = document.createElement('div');
  modalFooter.className = 'modal-footer';

  if (args.btnAccept) {
    const btnAccept = document.createElement('button');
    btnAccept.type = 'button';
    btnAccept.id = 'btnAccept';
    btnAccept.textContent = args.txtAccept;
    btnAccept.className = 'btn btn-default';
    once(btnAccept, 'click', args.fnAccept);
    modalFooter.appendChild(btnAccept);
  }

  if (args.btnAbort) {
    const btnAbort = document.createElement('button');
    btnAbort.type = 'button';
    btnAbort.id = 'btnAbort';
    btnAbort.textContent = args.txtAbort;
    btnAbort.className = 'btn btn-danger';
    once(btnAbort, 'click', args.fnAbort);
    modalFooter.appendChild(btnAbort);
  }

  modalHeader.appendChild(modalTitle);
  modalContent.append(modalHeader, modalBody, modalFooter);
  modalDialog.appendChild(modalContent);
  modal.appendChild(modalDialog);
  return modal;
}

function showModal(opts) {
  const modal = constructModal(opts);
  document.body.appendChild(modal);
}

function removeModal(id) {
  const modal = document.getElementById(id + 'Modal');
  if (modal) modal.remove();
}

function relateMeeting(rawData) {
  const data = JSON.parse(unescape(rawData));

  const btnSubmit = document.querySelector('#form_meeting_create input[type=submit]');

  document.getElementById('meeting_title').value = data.ref_id;
  document.getElementById('relate_meeting__ref_id__').value = data.ref_id;
  document.getElementById('relate_meeting__start__').value = data.start;
  document.getElementById('relate_meeting__end__').value = data.end;
  document.getElementById('relate_meeting__timezone__').value = data.timezone;
  document.getElementById('relate_meeting__rel_id__').value = data.rel_id;
  document.getElementById('relate_meeting__rel_data__').value =
    JSON.stringify(data.rel_data);

  btnSubmit.name = 'cmd[meeting_relate]';
  btnSubmit.click();
}

function deleteMeeting(rawData) {
  const data = JSON.parse(unescape(rawData));

  const opts = {
    id: data.modal.id,
    title: data.modal.title,
    body: data.modal.body,
    txtAccept: data.modal.txtAccept,
    txtAbort: data.modal.txtAbort,
    fnAccept: function () {
      const btnSubmit = document.querySelector('#form_meeting_create input[type=submit]');
      document.getElementById('meeting_title').value = data.ref_id;
      document.getElementById('delete_scheduled_meeting__ref_id__').value = data.ref_id;
      document.getElementById('delete_scheduled_meeting__delete_local_only__').value = data.delete_local_only;
      document.getElementById('delete_scheduled_meeting__start__').value = data.start;
      document.getElementById('delete_scheduled_meeting__end__').value = data.end;
      document.getElementById('delete_scheduled_meeting__timezone__').value = data.timezone;
      btnSubmit.name = 'cmd[delete_scheduled_meeting]';
      btnSubmit.click();
    },
    fnAbort: function () {
      removeModal(data.modal.id);
    }
  };

  const args = extend({
    id: 'default',
    title: 'Delete',
    body: 'Are you sure?',
    animation: 'fade',
    btnAccept: true,
    txtAccept: 'OK',
    btnAbort: true,
    txtAbort: 'Abort'
  }, opts);

  showModal(args);
}

function showDetails(rawData) {
  const data = JSON.parse(unescape(rawData));

  const pre = document.createElement('pre');
  pre.textContent = data.modal.body;

  const args = extend({
    id: data.modal.id,
    title: data.modal.title,
    body: pre,
    txtAccept: data.modal.txtAccept,
    fnAccept: function () {
      removeModal(data.modal.id);
    },
    btnAbort: false
  }, {});

  showModal(args);
}

// document.addEventListener('DOMContentLoaded', function () {
//   // entspricht $(document).ready()
// });
